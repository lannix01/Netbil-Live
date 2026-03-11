<?php

namespace App\Modules\PettyCash\Controllers\Spending;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\MealDailySpending;
use App\Modules\PettyCash\Models\MealPayment;
use App\Modules\PettyCash\Models\Respondent;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MealDailyController extends Controller
{
    public function index(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return redirect()
                ->route('petty.meals.index')
                ->with('error', 'Meal Daily Bills is not ready yet. Please run migrations first.');
        }

        $respondentId = $request->integer('respondent_id') ?: null;
        $from = $request->query('from');
        $to = $request->query('to');
        $status = strtolower(trim((string) $request->query('status', 'all')));
        if (!in_array($status, ['all', 'paid', 'unpaid'], true)) {
            $status = 'all';
        }

        $baseQuery = MealDailySpending::query()
            ->when($from, fn ($q) => $q->whereDate('spending_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('spending_date', '<=', $to))
            ->when($status === 'paid', fn ($q) => $q->whereNotNull('meal_payment_id'))
            ->when($status === 'unpaid', fn ($q) => $q->whereNull('meal_payment_id'))
            ->when($respondentId, function ($q) use ($respondentId) {
                $q->where(function ($w) use ($respondentId) {
                    $w->where('respondent_id', $respondentId)
                        ->orWhereHas('respondents', fn ($r) => $r->where('petty_respondents.id', $respondentId));
                });
            });

        $dailySpendings = (clone $baseQuery)
            ->with(['respondent:id,name', 'respondents:id,name', 'payment:id,date,reference'])
            ->orderByDesc('spending_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $totalLogged = round((float) (clone $baseQuery)->sum('amount'), 2);
        $totalUnpaid = round((float) (clone $baseQuery)->whereNull('meal_payment_id')->sum('amount'), 2);

        $payments = MealPayment::query()
            ->with([
                'respondent:id,name',
                'batch:id,batch_no',
                'dailySpendings:id,meal_payment_id,respondent_id,spending_date,amount',
                'dailySpendings.respondent:id,name',
                'dailySpendings.respondents:id,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $respondents = Respondent::query()
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $allocator = app(FundsAllocatorService::class);
        $batches = $allocator->batchesWithNetAvailable();
        $totalBalance = $allocator->totalNetBalance();
        $serviceSpentNet = $this->serviceSpentNetTotal();
        $actualBalance = round($totalBalance - $serviceSpentNet, 2);

        $selectedDailyIds = $this->normalizeIdList((array) old('daily_ids', []));
        $selectionStats = [
            'entries_count' => 0,
            'days_count' => 0,
            'amount' => 0.0,
            'range_from' => null,
            'range_to' => null,
            'people' => [],
        ];

        if (!empty($selectedDailyIds)) {
            $selectionStats = $this->selectedDailyStats($selectedDailyIds);
        }

        return view('pettycash::spendings.meals.daily', compact(
            'dailySpendings',
            'payments',
            'respondents',
            'respondentId',
            'from',
            'to',
            'status',
            'totalLogged',
            'totalUnpaid',
            'batches',
            'totalBalance',
            'serviceSpentNet',
            'actualBalance',
            'selectionStats',
            'selectedDailyIds'
        ));
    }

    public function calculate(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return response()->json([
                'ok' => false,
                'message' => 'Meal Daily Bills is not ready yet. Run migrations first.',
            ], 503);
        }

        $data = $request->validate([
            'daily_ids' => ['required', 'array', 'min:1'],
            'daily_ids.*' => ['integer', 'exists:petty_meal_daily_spendings,id'],
        ]);

        $ids = $this->normalizeIdList((array) ($data['daily_ids'] ?? []));
        if (empty($ids)) {
            return response()->json([
                'ok' => false,
                'message' => 'No valid records selected.',
            ], 422);
        }

        $stats = $this->selectedDailyStats($ids);

        return response()->json([
            'ok' => true,
            'entries_count' => $stats['entries_count'],
            'days_count' => $stats['days_count'],
            'amount' => $stats['amount'],
            'range_from' => $stats['range_from'],
            'range_to' => $stats['range_to'],
            'people' => $stats['people'],
            'has_rows' => $stats['entries_count'] > 0,
        ]);
    }

    public function storeDaily(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return back()
                ->withErrors(['spending_date' => 'Meal Daily Bills is not ready yet. Please run migrations first.'])
                ->withInput();
        }

        $data = $request->validate([
            'spending_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
            'involved_respondent_ids' => ['nullable', 'array'],
            'involved_respondent_ids.*' => ['integer', 'exists:petty_respondents,id'],
        ]);

        $row = MealDailySpending::query()->create([
            'respondent_id' => null,
            'spending_date' => $data['spending_date'],
            'amount' => round((float) $data['amount'], 2),
            'notes' => $data['notes'] ?? null,
            'recorded_by' => auth('petty')->id(),
        ]);

        $involvedIds = $this->normalizeIdList((array) ($data['involved_respondent_ids'] ?? []));
        if (!empty($involvedIds)) {
            $row->respondents()->sync($involvedIds);
        }

        return redirect()
            ->route('petty.meals.daily.index', [
                'from' => $data['spending_date'],
                'to' => $data['spending_date'],
            ])
            ->with('success', 'Daily meal bill saved.');
    }

    public function storePayment(Request $request)
    {
        if (!$this->mealDailyTablesReady()) {
            return back()
                ->withErrors(['daily_ids' => 'Meal Daily Bills is not ready yet. Please run migrations first.'])
                ->withInput();
        }

        $data = $request->validate([
            'daily_ids' => ['required', 'array', 'min:1'],
            'daily_ids.*' => ['integer', 'exists:petty_meal_daily_spendings,id'],

            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'date' => ['required', 'date'],
            'reference' => ['required', 'string', 'max:255'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'receiver_phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['funding'] === 'single' && empty($data['batch_id'])) {
            return back()->withErrors(['batch_id' => 'Batch is required in Single Batch mode.'])->withInput();
        }

        $selectedIds = $this->normalizeIdList((array) ($data['daily_ids'] ?? []));
        if (empty($selectedIds)) {
            return back()->withErrors(['daily_ids' => 'No valid daily records selected.'])->withInput();
        }

        $fee = round((float) ($data['transaction_cost'] ?? 0), 2);
        $allocator = app(FundsAllocatorService::class);

        try {
            DB::transaction(function () use ($data, $selectedIds, $fee, $allocator) {
                $rows = $this->selectedDailyRows($selectedIds, true);
                if ($rows->count() !== count($selectedIds)) {
                    throw new \RuntimeException('Some selected records are already paid or unavailable. Refresh and try again.');
                }

                $stats = $this->statsFromRows($rows);
                $amount = $stats['amount'];
                if ($amount <= 0) {
                    throw new \RuntimeException('Selected records do not have a payable amount.');
                }

                if ($data['funding'] === 'auto') {
                    $required = $amount + $fee;
                    $available = $this->actualAvailableBalance($allocator);
                    if ($required > $available) {
                        throw new \RuntimeException(
                            'Insufficient available balance. Needed: ' . number_format($required, 2)
                            . ' Available: ' . number_format($available, 2)
                        );
                    }
                }

                $description = trim((string) ($data['description'] ?? ''));
                if ($description === '') {
                    $description = 'Meal payment for ' . $stats['entries_count'] . ' record(s)';
                    if (!empty($stats['range_from']) && !empty($stats['range_to'])) {
                        $description .= ' (' . $stats['range_from'] . ' to ' . $stats['range_to'] . ')';
                    }
                }

                $spending = Spending::query()->create([
                    'batch_id' => null,
                    'type' => 'meal',
                    'sub_type' => 'daily_payment',
                    'reference' => $data['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $data['date'],
                    'respondent_id' => null,
                    'description' => $description,
                ]);

                $onlyBatch = ($data['funding'] === 'single') ? (int) $data['batch_id'] : null;
                $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);

                $payment = MealPayment::query()->create([
                    'spending_id' => $spending->id,
                    'respondent_id' => null,
                    'batch_id' => $spending->batch_id,
                    'range_from' => $stats['range_from'] ?? $data['date'],
                    'range_to' => $stats['range_to'] ?? $data['date'],
                    'days_count' => $stats['days_count'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'reference' => $data['reference'],
                    'date' => $data['date'],
                    'receiver_name' => $data['receiver_name'] ?? null,
                    'receiver_phone' => $data['receiver_phone'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => auth('petty')->id(),
                ]);

                MealDailySpending::query()
                    ->whereIn('id', $rows->pluck('id')->all())
                    ->update(['meal_payment_id' => $payment->id]);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['daily_ids' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('petty.meals.daily.index')
            ->with('success', 'Meal payment recorded and balance updated.');
    }

    /**
     * @param array<int,mixed> $ids
     * @return array<int,int>
     */
    private function normalizeIdList(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            $v = (int) $id;
            if ($v > 0) {
                $normalized[$v] = $v;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param array<int,int> $ids
     */
    private function selectedDailyRows(array $ids, bool $lockForUpdate = false): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        $query = MealDailySpending::query()
            ->with(['respondent:id,name', 'respondents:id,name'])
            ->whereIn('id', $ids)
            ->whereNull('meal_payment_id')
            ->orderBy('spending_date')
            ->orderBy('id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * @param array<int,int> $ids
     * @return array{entries_count:int,days_count:int,amount:float,range_from:?string,range_to:?string,people:array<int,string>}
     */
    private function selectedDailyStats(array $ids): array
    {
        $rows = $this->selectedDailyRows($ids, false);

        return $this->statsFromRows($rows);
    }

    /**
     * @return array{entries_count:int,days_count:int,amount:float,range_from:?string,range_to:?string,people:array<int,string>}
     */
    private function statsFromRows(Collection $rows): array
    {
        $entriesCount = (int) $rows->count();
        $amount = round((float) $rows->sum('amount'), 2);

        $dates = $rows
            ->pluck('spending_date')
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->values();

        $rangeFrom = $dates->isNotEmpty() ? $dates->min() : null;
        $rangeTo = $dates->isNotEmpty() ? $dates->max() : null;
        $daysCount = (int) $dates->unique()->count();

        $peopleMap = [];
        foreach ($rows as $row) {
            if (!empty($row->respondent?->name)) {
                $peopleMap[$row->respondent->name] = $row->respondent->name;
            }

            foreach ($row->respondents as $person) {
                if (!empty($person->name)) {
                    $peopleMap[$person->name] = $person->name;
                }
            }
        }

        return [
            'entries_count' => $entriesCount,
            'days_count' => $daysCount,
            'amount' => $amount,
            'range_from' => $rangeFrom,
            'range_to' => $rangeTo,
            'people' => array_values($peopleMap),
        ];
    }

    private function mealDailyTablesReady(): bool
    {
        static $ready = null;

        if ($ready === null) {
            $ready = Schema::hasTable('petty_meal_daily_spendings')
                && Schema::hasTable('petty_meal_payments')
                && Schema::hasTable('petty_meal_daily_respondents');
        }

        return $ready;
    }

    private function serviceSpentNetTotal(): float
    {
        if (!Schema::hasTable('petty_bike_services')) {
            return 0.0;
        }

        $total = (float) DB::table('petty_bike_services')
            ->selectRaw('COALESCE(SUM(amount + COALESCE(transaction_cost,0)),0) as t')
            ->value('t');

        return round($total, 2);
    }

    private function actualAvailableBalance(FundsAllocatorService $allocator): float
    {
        $net = (float) $allocator->totalNetBalance();
        $serviceSpent = $this->serviceSpentNetTotal();

        return round($net - $serviceSpent, 2);
    }
}
