<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;

class MealController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');

        $listQuery = Spending::with(['batch', 'allocations.batch'])
            ->where('type', 'meal')
            ->where('sub_type', 'lunch')
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $meals = $listQuery->paginate(20)->withQueryString();

        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'meal')
            ->where('petty_spendings.sub_type', 'lunch')
            ->when($batchId, fn($q) => $q->where('petty_spending_allocations.batch_id', $batchId))
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        $batches = Batch::orderByDesc('id')->limit(50)->get();

        return view('pettycash::spendings.meals.index', compact('meals', 'total', 'from', 'to', 'batchId', 'batches'));
    }

    public function create(Request $request)
    {
        $allocator = app(FundsAllocatorService::class);

        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $prefBatchId = $request->query('batch_id');

        return view('pettycash::spendings.meals.create', compact('batches', 'prefBatchId', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],

            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],

            'mass' => ['nullable', 'boolean'],
            'range_from' => ['nullable', 'date'],
            'range_to' => ['nullable', 'date'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        $isMass = (bool)($request->input('mass') ?? false);

        if ($isMass) {
            if (empty($data['range_from']) || empty($data['range_to'])) {
                return back()->withErrors(['range_from' => 'Range dates are required for mass disbursement.'])->withInput();
            }

            $start = Carbon::parse($data['range_from'])->startOfDay();
            $end = Carbon::parse($data['range_to'])->startOfDay();
            if ($start->gt($end)) [$start, $end] = [$end, $start];

            $dates = [];
            $cur = $start->copy();
            while ($cur->lte($end)) {
                $dates[] = $cur->toDateString();
                $cur->addDay();
            }
        } else {
            if (empty($data['date'])) {
                return back()->withErrors(['date' => 'Date is required.'])->withInput();
            }
            $dates = [Carbon::parse($data['date'])->toDateString()];
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        // upfront balance check for AUTO (total)
        if ($data['funding'] === 'auto') {
            $requiredTotal = ($amount + $fee) * count($dates);
            $availableTotal = $allocator->totalNetBalance();
            if ($requiredTotal > $availableTotal) {
                return back()->withErrors([
                    'amount' => 'Insufficient TOTAL balance. Needed: '.number_format($requiredTotal,2).' Available: '.number_format($availableTotal,2)
                ])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($data, $dates, $amount, $fee, $allocator) {
                foreach ($dates as $d) {
                    $sp = Spending::create([
                        'batch_id' => null,
                        'type' => 'meal',
                        'sub_type' => 'lunch',
                        'reference' => $data['reference'],
                        'amount' => $amount,
                        'transaction_cost' => $fee,
                        'date' => $d,
                        'description' => $data['description'] ?? null,
                    ]);

                    $onlyBatch = ($data['funding'] === 'single') ? (int)$data['batch_id'] : null;
                    $allocator->allocateSmallestFirst($sp, $amount, $fee, $onlyBatch);
                }
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.meals.index')->with('success', $isMass
            ? ('Mass lunch disbursement recorded for '.count($dates).' day(s).')
            : 'Meal spending recorded.'
        );
    }

    public function pdf(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');

        $meals = Spending::with(['batch', 'allocations.batch'])
            ->where('type', 'meal')
            ->where('sub_type', 'lunch')
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->get();

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $meals->map(function ($m) {
                $amount = (float) $m->amount;
                $fee = (float) ($m->transaction_cost ?? 0);
                $allocationBatches = $m->allocations
                    ->map(fn($a) => $a->batch?->batch_no)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'date' => $m->date?->format('Y-m-d'),
                    'reference' => $m->reference ?? '',
                    'description' => $m->description ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'primary_batch' => $m->batch?->batch_no ?? '',
                    'allocated_batches' => $allocationBatches,
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-meals-lunch-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'MPESA Ref' => 'reference',
                    'Description' => 'description',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Primary Batch' => 'primary_batch',
                    'Allocated Batches' => 'allocated_batches',
                ],
                $rows
            );
        }

        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'meal')
            ->where('petty_spendings.sub_type', 'lunch')
            ->when($batchId, fn($q) => $q->where('petty_spending_allocations.batch_id', $batchId))
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        $pdf = Pdf::loadView('pettycash::reports.meals_pdf', [
            'meals' => $meals,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'batchId' => $batchId,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-meals-lunch.pdf');
    }

    public function edit(Spending $spending)
    {
        abort_unless($spending->type === 'meal' && $spending->sub_type === 'lunch', 404);

        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();

        return view('pettycash::spendings.meals.edit', compact('spending', 'batches'));
    }

    public function update(Request $request, Spending $spending)
    {
        abort_unless($spending->type === 'meal' && $spending->sub_type === 'lunch', 404);

        $data = $request->validate([
            'batch_id' => ['required', 'integer', 'exists:petty_batches,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $amount = (float) $data['amount'];
        $fee = (float) ($data['transaction_cost'] ?? 0);
        $allocator = app(FundsAllocatorService::class);

        try {
            DB::transaction(function () use ($spending, $data, $amount, $fee, $allocator) {
                $spending->update([
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'description' => $data['description'] ?? null,
                ]);

                $allocator->allocateSmallestFirst($spending, $amount, $fee, (int) $data['batch_id']);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.meals.index')->with('success', 'Meal updated.');
    }
}
