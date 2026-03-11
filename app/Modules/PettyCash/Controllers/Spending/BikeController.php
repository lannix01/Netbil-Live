<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Bike;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\TabularExport;
use Barryvdh\DomPDF\Facade\Pdf;

class BikeController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $sub = $request->query('sub_type');
        $batchId = $request->query('batch_id');

        $query = Spending::with(['respondent', 'bike', 'batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->when($sub, fn($q) => $q->where('sub_type', $sub))
            //  batch filter must look at allocations (supports split)
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $spendings = $query->paginate(20)->withQueryString();

        // NET total via allocations (amount+fee)
        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'bike')
            ->when($sub, fn($q) => $q->where('petty_spendings.sub_type', $sub))
            ->when($batchId, fn($q) => $q->where('petty_spending_allocations.batch_id', $batchId))
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        $batches = Batch::orderByDesc('id')->limit(50)->get();

        return view('pettycash::spendings.bikes.index', compact('spendings', 'total', 'from', 'to', 'sub', 'batchId', 'batches'));
    }

    public function create(Request $request)
    {
        $allocator = app(FundsAllocatorService::class);

        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();

        $bikes = Bike::orderBy('plate_no')->get();
        $respondents = Respondent::orderBy('name')->get();

        $prefBatchId = $request->query('batch_id');
        $prefBikeId = $request->query('bike_id');

        return view('pettycash::spendings.bikes.create', compact(
            'batches', 'bikes', 'respondents', 'prefBatchId', 'prefBikeId', 'totalBalance'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'sub_type' => ['required', 'in:fuel,maintenance'],
            'bike_id' => ['required', 'integer', 'exists:petty_bikes,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'particulars' => ['nullable', 'string'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        if ($data['sub_type'] === 'maintenance' && empty(trim((string)($data['particulars'] ?? '')))) {
            return back()->withErrors(['particulars' => 'Particulars is required for maintenance.'])->withInput();
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        try {
            return DB::transaction(function () use ($data, $amount, $fee, $allocator) {
                $spending = Spending::create([
                    'batch_id' => null, // allocator will set primary batch
                    'type' => 'bike',
                    'sub_type' => $data['sub_type'],
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => $data['respondent_id'] ?? null,
                    'description' => $data['description'] ?? null,
                    'related_id' => $data['bike_id'],
                    'particulars' => $data['particulars'] ?? null,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int)$data['batch_id'] : null;
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                return redirect()->route('petty.bikes.index')->with('success', 'Bike spending recorded with auto allocation.');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }

    public function byBike(Bike $bike, Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $query = Spending::with(['respondent', 'bike', 'batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->where('related_id', $bike->id)
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->orderByDesc('id');

        $spendings = $query->paginate(20)->withQueryString();

        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'bike')
            ->where('petty_spendings.related_id', $bike->id)
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        return view('pettycash::spendings.bikes.by_bike', compact('bike', 'spendings', 'total', 'from', 'to'));
    }

    public function pdf(Request $request)
    {
        $format = strtolower((string) $request->query('format', 'pdf'));
        $from = $request->query('from');
        $to = $request->query('to');
        $sub = $request->query('sub_type');
        $batchId = $request->query('batch_id');

        $spendings = Spending::with(['respondent', 'bike', 'batch', 'allocations.batch'])
            ->where('type', 'bike')
            ->when($sub, fn($q) => $q->where('sub_type', $sub))
            ->when($batchId, fn($q) => $q->whereHas('allocations', fn($a) => $a->where('batch_id', $batchId)))
            ->when($from, fn($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('date', '<=', $to))
            ->orderByDesc('date')
            ->get();

        if (in_array($format, ['csv', 'excel', 'xls', 'xlsx'], true)) {
            $rows = $spendings->map(function ($s) {
                $amount = (float) $s->amount;
                $fee = (float) ($s->transaction_cost ?? 0);
                $allocationBatches = $s->allocations
                    ->map(fn($a) => $a->batch?->batch_no)
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                return [
                    'date' => $s->date?->format('Y-m-d'),
                    'plate_no' => $s->bike?->plate_no ?? '',
                    'sub_type' => strtoupper((string) ($s->sub_type ?? '')),
                    'reference' => $s->reference ?? '',
                    'amount' => number_format($amount, 2, '.', ''),
                    'transaction_cost' => number_format($fee, 2, '.', ''),
                    'total' => number_format($amount + $fee, 2, '.', ''),
                    'respondent' => $s->respondent?->name ?? '',
                    'description' => $s->description ?? '',
                    'particulars' => $s->particulars ?? '',
                    'primary_batch' => $s->batch?->batch_no ?? '',
                    'allocated_batches' => $allocationBatches,
                ];
            })->all();

            return TabularExport::download(
                $format,
                'pettycash-bike-spendings-' . now()->format('Ymd-His'),
                [
                    'Date' => 'date',
                    'Plate' => 'plate_no',
                    'Subtype' => 'sub_type',
                    'MPESA Ref' => 'reference',
                    'Amount' => 'amount',
                    'Fee' => 'transaction_cost',
                    'Total' => 'total',
                    'Respondent' => 'respondent',
                    'Description' => 'description',
                    'Particulars' => 'particulars',
                    'Primary Batch' => 'primary_batch',
                    'Allocated Batches' => 'allocated_batches',
                ],
                $rows
            );
        }

        $total = (float) DB::table('petty_spending_allocations')
            ->join('petty_spendings', 'petty_spendings.id', '=', 'petty_spending_allocations.spending_id')
            ->where('petty_spendings.type', 'bike')
            ->when($sub, fn($q) => $q->where('petty_spendings.sub_type', $sub))
            ->when($batchId, fn($q) => $q->where('petty_spending_allocations.batch_id', $batchId))
            ->when($from, fn($q) => $q->whereDate('petty_spendings.date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('petty_spendings.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(petty_spending_allocations.amount + petty_spending_allocations.transaction_cost),0) as t')
            ->value('t');

        $pdf = Pdf::loadView('pettycash::reports.bikes_spendings_pdf', [
            'spendings' => $spendings,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'sub' => $sub,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('pettycash-bikes-spendings.pdf');
    }

    // edit/update: keep working (single batch UI) but allocations will still reflect real math if you want later.
    public function edit(Spending $spending)
    {
        abort_unless($spending->type === 'bike', 404);

        $batches = Batch::orderByDesc('id')->limit(50)->get();
        $bikes = Bike::orderBy('plate_no')->get();
        $respondents = Respondent::orderBy('name')->get();

        return view('pettycash::spendings.bikes.edit', compact('spending', 'batches', 'bikes', 'respondents'));
    }

    public function update(Request $request, Spending $spending)
    {
        abort_unless($spending->type === 'bike', 404);

        $data = $request->validate([
            'funding' => ['nullable', 'in:auto,single'], // if edit view doesn't have it, fallback to single
            'batch_id' => ['required', 'integer', 'exists:petty_batches,id'],

            'sub_type' => ['required', 'in:fuel,maintenance'],
            'bike_id' => ['required', 'integer', 'exists:petty_bikes,id'],
            'reference' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'particulars' => ['nullable', 'string'],
        ]);

        if ($data['sub_type'] === 'maintenance' && empty(trim((string)($data['particulars'] ?? '')))) {
            return back()->withErrors(['particulars' => 'Particulars is required for maintenance.'])->withInput();
        }

        $fee = (float)($data['transaction_cost'] ?? 0);
        $amount = (float)$data['amount'];

        $allocator = app(FundsAllocatorService::class);

        try {
            return DB::transaction(function () use ($spending, $data, $amount, $fee, $allocator) {
                $spending->update([
                    'sub_type' => $data['sub_type'],
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => $data['respondent_id'] ?? null,
                    'description' => $data['description'] ?? null,
                    'related_id' => $data['bike_id'],
                    'particulars' => $data['particulars'] ?? null,
                ]);

                // default to single-batch on edit (safe)
                $onlyBatch = (int)$data['batch_id'];
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                return redirect()->route('petty.bikes.index')->with('success', 'Bike spending updated.');
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
    }
}
