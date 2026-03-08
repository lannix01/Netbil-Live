<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;

class OtherController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');

        $listQuery = Spending::with(['batch', 'respondent', 'allocations.batch'])
            ->where('type', 'other')
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $others = $listQuery->paginate(20)->withQueryString();

        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'other')
            ->when($batchId, fn($q) => $q->where('petty_spending_allocations.batch_id', $batchId))
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        $batches = Batch::orderByDesc('id')->limit(50)->get();

        return view('pettycash::spendings.others.index', compact('others', 'total', 'from', 'to', 'batchId', 'batches'));
    }

    public function create(Request $request)
    {
        $allocator = app(FundsAllocatorService::class);

        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $respondents = Respondent::orderBy('name')->get();
        $prefBatchId = $request->query('batch_id');

        return view('pettycash::spendings.others.create', compact('batches', 'respondents', 'prefBatchId', 'totalBalance'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        if ($data['funding'] === 'auto') {
            $required = $amount + $fee;
            if ($required > $allocator->totalNetBalance()) {
                return back()->withErrors([
                    'amount' => 'Insufficient TOTAL balance. Needed: '.number_format($required,2).' Available: '.number_format($allocator->totalNetBalance(),2)
                ])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($data, $amount, $fee, $allocator) {
                $sp = Spending::create([
                    'batch_id' => null,
                    'type' => 'other',
                    'sub_type' => null,
                    'reference' => $data['reference'] ?? null,
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => $data['respondent_id'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int)$data['batch_id'] : null;
                $allocator->allocateSmallestFirst($sp, $amount, $fee, $onlyBatch);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('petty.others.index')->with('success', 'Other spending recorded.');
    }

    public function pdf(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $from = $request->query('from');
        $to = $request->query('to');
        $batchId = $request->query('batch_id');

        $others = Spending::with(['respondent','batch','allocations.batch'])
            ->where('type', 'other')
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->get();

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $others->map(function ($o) {
                $amount = (float) $o->amount;
                $fee = (float) ($o->transaction_cost ?? 0);
                $allocationBatches = $o->allocations
                    ->map(fn($a) => $a->batch?->batch_no)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'date' => $o->date?->format('Y-m-d'),
                    'reference' => $o->reference ?? '',
                    'description' => $o->description ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'respondent' => $o->respondent?->name ?? '',
                    'primary_batch' => $o->batch?->batch_no ?? '',
                    'allocated_batches' => $allocationBatches,
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-others-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'MPESA Ref' => 'reference',
                    'Description' => 'description',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Respondent' => 'respondent',
                    'Primary Batch' => 'primary_batch',
                    'Allocated Batches' => 'allocated_batches',
                ],
                $rows
            );
        }

        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'other')
            ->when($batchId, fn($q) => $q->where('petty_spending_allocations.batch_id', $batchId))
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        $pdf = Pdf::loadView('pettycash::reports.others_pdf', [
            'others' => $others,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'batchId' => $batchId,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('pettycash-others.pdf');
    }
}
