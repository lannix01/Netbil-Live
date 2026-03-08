<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Models\SpendingAllocation;
use App\Modules\PettyCash\Services\FundsAllocatorService;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpendingController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $allowedTypes = ['bike', 'meal', 'other'];
        $type = strtolower(trim((string) $request->query('type', '')));
        $types = in_array($type, $allowedTypes, true) ? [$type] : $allowedTypes;

        $subType = strtolower(trim((string) $request->query('sub_type', '')));
        $batchId = (int) $request->integer('batch_id', 0);
        $respondentId = (int) $request->integer('respondent_id', 0);
        $bikeId = (int) $request->integer('bike_id', 0);
        $from = $request->query('from');
        $to = $request->query('to');
        $search = trim((string) $request->query('q', ''));

        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $spendings = Spending::query()
            ->with(['batch', 'respondent', 'bike', 'allocations.batch'])
            ->whereIn('type', $types)
            ->when($subType !== '', fn ($query) => $query->where('sub_type', $subType))
            ->when($batchId > 0, fn ($query) => $query->whereHas('allocations', fn ($a) => $a->where('batch_id', $batchId)))
            ->when($respondentId > 0, fn ($query) => $query->where('respondent_id', $respondentId))
            ->when($bikeId > 0, fn ($query) => $query->where('type', 'bike')->where('related_id', $bikeId))
            ->when($from, fn ($query) => $query->whereDate('date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('date', '<=', $to))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('reference', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('particulars', 'like', '%' . $search . '%')
                        ->orWhere('meter_no', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totals = DB::table('petty_spending_allocations as a')
            ->join('petty_spendings as s', 's.id', '=', 'a.spending_id')
            ->whereIn('s.type', $types)
            ->when($subType !== '', fn ($query) => $query->where('s.sub_type', $subType))
            ->when($batchId > 0, fn ($query) => $query->where('a.batch_id', $batchId))
            ->when($respondentId > 0, fn ($query) => $query->where('s.respondent_id', $respondentId))
            ->when($bikeId > 0, fn ($query) => $query->where('s.type', 'bike')->where('s.related_id', $bikeId))
            ->when($from, fn ($query) => $query->whereDate('s.date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('s.date', '<=', $to))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('s.reference', 'like', '%' . $search . '%')
                        ->orWhere('s.description', 'like', '%' . $search . '%')
                        ->orWhere('s.particulars', 'like', '%' . $search . '%')
                        ->orWhere('s.meter_no', 'like', '%' . $search . '%');
                });
            })
            ->selectRaw('
                COALESCE(SUM(a.amount), 0) as principal_total,
                COALESCE(SUM(a.transaction_cost), 0) as transaction_cost_total,
                COALESCE(SUM(a.amount + a.transaction_cost), 0) as net_total
            ')
            ->first();

        return $this->successResponse([
            'spendings' => collect($spendings->items())
                ->map(fn (Spending $spending) => $this->mapSpending($spending))
                ->values(),
            'filters' => [
                'type' => $type,
                'sub_type' => $subType,
                'batch_id' => $batchId > 0 ? $batchId : null,
                'respondent_id' => $respondentId > 0 ? $respondentId : null,
                'bike_id' => $bikeId > 0 ? $bikeId : null,
                'from' => $from,
                'to' => $to,
                'q' => $search,
                'per_page' => $perPage,
            ],
            'summary' => [
                'principal_total' => round((float) ($totals->principal_total ?? 0), 2),
                'transaction_cost_total' => round((float) ($totals->transaction_cost_total ?? 0), 2),
                'net_total' => round((float) ($totals->net_total ?? 0), 2),
            ],
        ], 'Spendings fetched.', 200, [
            'pagination' => $this->paginationMeta($spendings),
        ]);
    }

    public function show(Spending $spending)
    {
        if ($spending->type === 'token') {
            return $this->errorResponse('Use token endpoints for token spendings.', 409);
        }

        $spending->load(['batch', 'respondent', 'bike', 'allocations.batch']);

        return $this->successResponse([
            'spending' => $this->mapSpending($spending),
        ], 'Spending fetched.');
    }

    public function store(Request $request)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'type' => ['required', 'in:bike,meal,other'],
            'sub_type' => ['nullable', 'string', 'max:60'],
            'bike_id' => ['nullable', 'integer', 'exists:petty_bikes,id'],
            'related_id' => ['nullable', 'integer', 'exists:petty_bikes,id'],

            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'respondent_id' => ['nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'particulars' => ['nullable', 'string'],
        ]);

        $normalized = $this->normalizeSpendingPayload($data, null);
        if (isset($normalized['error'])) {
            return $this->errorResponse('Validation failed.', 422, $normalized['error']);
        }

        $funding = $data['funding'];
        $onlyBatch = null;
        if ($funding === 'single') {
            $onlyBatch = !empty($data['batch_id']) ? (int) $data['batch_id'] : null;
            if (!$onlyBatch) {
                return $this->errorResponse('Validation failed.', 422, [
                    'batch_id' => ['Batch is required in single funding mode.'],
                ]);
            }
        }

        $amount = (float) $normalized['amount'];
        $fee = (float) $normalized['transaction_cost'];
        $allocator = app(FundsAllocatorService::class);

        if ($funding === 'auto') {
            $required = $amount + $fee;
            $available = (float) $allocator->totalNetBalance();
            if ($required > $available) {
                return $this->errorResponse('Insufficient total balance.', 422, [
                    'amount' => [
                        'Needed: ' . number_format($required, 2) . '. Available: ' . number_format($available, 2),
                    ],
                ]);
            }
        }

        $spending = null;
        $allocations = [];

        try {
            DB::transaction(function () use ($normalized, $amount, $fee, $allocator, $onlyBatch, &$spending, &$allocations) {
                $spending = Spending::query()->create([
                    'batch_id' => null,
                    'type' => $normalized['type'],
                    'sub_type' => $normalized['sub_type'],
                    'reference' => $normalized['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $normalized['date'],
                    'respondent_id' => $normalized['respondent_id'],
                    'description' => $normalized['description'],
                    'related_id' => $normalized['related_id'],
                    'particulars' => $normalized['particulars'],
                ]);

                $alloc = $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);
                $allocations = $alloc['allocations'] ?? [];
            });
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to record spending.', 422, [
                'spending' => [$e->getMessage()],
            ]);
        }

        $spending->load(['batch', 'respondent', 'bike', 'allocations.batch']);

        return $this->successResponse([
            'spending' => $this->mapSpending($spending),
            'allocations' => collect($allocations)
                ->map(fn (array $row) => $this->mapAllocationRow($row))
                ->values(),
        ], 'Spending recorded and allocated.', 201);
    }

    public function update(Request $request, Spending $spending)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        if ($spending->type === 'token') {
            return $this->errorResponse('Use token endpoints to update token spendings.', 409);
        }

        $data = $request->validate([
            'funding' => ['required', 'in:auto,single'],
            'batch_id' => ['nullable', 'integer', 'exists:petty_batches,id'],

            'sub_type' => ['sometimes', 'nullable', 'string', 'max:60'],
            'bike_id' => ['sometimes', 'nullable', 'integer', 'exists:petty_bikes,id'],
            'related_id' => ['sometimes', 'nullable', 'integer', 'exists:petty_bikes,id'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'date' => ['sometimes', 'required', 'date'],
            'respondent_id' => ['sometimes', 'nullable', 'integer', 'exists:petty_respondents,id'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'particulars' => ['sometimes', 'nullable', 'string'],
        ]);

        $normalized = $this->normalizeSpendingPayload($data, $spending);
        if (isset($normalized['error'])) {
            return $this->errorResponse('Validation failed.', 422, $normalized['error']);
        }

        $funding = $data['funding'];
        $onlyBatch = null;
        if ($funding === 'single') {
            $onlyBatch = !empty($data['batch_id'])
                ? (int) $data['batch_id']
                : ($spending->batch_id ? (int) $spending->batch_id : null);
            if (!$onlyBatch) {
                return $this->errorResponse('Validation failed.', 422, [
                    'batch_id' => ['Batch is required in single funding mode.'],
                ]);
            }
        }

        $amount = (float) $normalized['amount'];
        $fee = (float) $normalized['transaction_cost'];
        $oldTotal = (float) ($spending->amount ?? 0) + (float) ($spending->transaction_cost ?? 0);
        $allocator = app(FundsAllocatorService::class);

        if ($funding === 'auto') {
            $required = $amount + $fee;
            $availableAfterRelease = (float) $allocator->totalNetBalance() + $oldTotal;
            if ($required > $availableAfterRelease) {
                return $this->errorResponse('Insufficient total balance.', 422, [
                    'amount' => [
                        'Needed: ' . number_format($required, 2) . '. Available after release: ' . number_format($availableAfterRelease, 2),
                    ],
                ]);
            }
        }

        $allocations = [];

        try {
            DB::transaction(function () use ($spending, $normalized, $amount, $fee, $allocator, $onlyBatch, &$allocations) {
                $spending->fill([
                    'type' => $normalized['type'],
                    'sub_type' => $normalized['sub_type'],
                    'reference' => $normalized['reference'],
                    'amount' => $amount,
                    'transaction_cost' => $fee,
                    'date' => $normalized['date'],
                    'respondent_id' => $normalized['respondent_id'],
                    'description' => $normalized['description'],
                    'related_id' => $normalized['related_id'],
                    'particulars' => $normalized['particulars'],
                ]);
                $spending->save();

                $alloc = $allocator->allocateSmallestFirst($spending, $amount, $fee, $onlyBatch);
                $allocations = $alloc['allocations'] ?? [];
            });
        } catch (\Throwable $e) {
            return $this->errorResponse('Failed to update spending.', 422, [
                'spending' => [$e->getMessage()],
            ]);
        }

        $spending->refresh()->load(['batch', 'respondent', 'bike', 'allocations.batch']);

        return $this->successResponse([
            'spending' => $this->mapSpending($spending),
            'allocations' => collect($allocations)
                ->map(fn (array $row) => $this->mapAllocationRow($row))
                ->values(),
        ], 'Spending updated.');
    }

    public function destroy(Request $request, Spending $spending)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin'])) {
            return $deny;
        }

        if ($spending->type === 'token') {
            return $this->errorResponse('Use token endpoints to delete token spendings.', 409);
        }

        DB::transaction(function () use ($spending) {
            SpendingAllocation::query()->where('spending_id', $spending->id)->delete();
            $spending->delete();
        });

        return $this->successResponse([], 'Spending deleted.');
    }

    private function normalizeSpendingPayload(array $data, ?Spending $current): array
    {
        $type = $current ? (string) $current->type : (string) ($data['type'] ?? '');
        $subType = array_key_exists('sub_type', $data)
            ? strtolower(trim((string) $data['sub_type']))
            : strtolower((string) ($current?->sub_type ?? ''));

        $amount = array_key_exists('amount', $data)
            ? (float) $data['amount']
            : (float) ($current?->amount ?? 0);
        $fee = array_key_exists('transaction_cost', $data)
            ? (float) ($data['transaction_cost'] ?? 0)
            : (float) ($current?->transaction_cost ?? 0);
        $date = array_key_exists('date', $data)
            ? $data['date']
            : optional($current?->date)->format('Y-m-d');

        $reference = array_key_exists('reference', $data)
            ? $data['reference']
            : $current?->reference;
        $description = array_key_exists('description', $data)
            ? $data['description']
            : $current?->description;
        $respondentId = array_key_exists('respondent_id', $data)
            ? ($data['respondent_id'] ?: null)
            : $current?->respondent_id;
        $particulars = array_key_exists('particulars', $data)
            ? $data['particulars']
            : $current?->particulars;

        $relatedId = null;

        if ($type === 'bike') {
            if (!in_array($subType, ['fuel', 'maintenance'], true)) {
                return [
                    'error' => [
                        'sub_type' => ['For bike spendings, sub_type must be fuel or maintenance.'],
                    ],
                ];
            }

            $relatedId = (int) ($data['bike_id'] ?? $data['related_id'] ?? ($current?->related_id ?? 0));
            if ($relatedId < 1) {
                return [
                    'error' => [
                        'bike_id' => ['Bike is required for bike spendings.'],
                    ],
                ];
            }

            if ($subType === 'maintenance' && trim((string) ($particulars ?? '')) === '') {
                return [
                    'error' => [
                        'particulars' => ['Particulars is required for maintenance spendings.'],
                    ],
                ];
            }
        } elseif ($type === 'meal') {
            $subType = 'lunch';
            $relatedId = null;
            $respondentId = null;
            $particulars = null;
        } elseif ($type === 'other') {
            $subType = null;
            $relatedId = null;
        } else {
            return [
                'error' => [
                    'type' => ['Type must be bike, meal, or other.'],
                ],
            ];
        }

        return [
            'type' => $type,
            'sub_type' => $subType,
            'related_id' => $relatedId,
            'reference' => $reference,
            'amount' => $amount,
            'transaction_cost' => $fee,
            'date' => $date,
            'respondent_id' => $respondentId,
            'description' => $description,
            'particulars' => $particulars,
        ];
    }

    private function mapSpending(Spending $spending): array
    {
        $amount = (float) ($spending->amount ?? 0);
        $transactionCost = (float) ($spending->transaction_cost ?? 0);

        return [
            'id' => $spending->id,
            'type' => $spending->type,
            'sub_type' => $spending->sub_type,
            'reference' => $spending->reference,
            'amount' => $amount,
            'transaction_cost' => $transactionCost,
            'total' => $amount + $transactionCost,
            'date' => optional($spending->date)->format('Y-m-d'),
            'description' => $spending->description,
            'respondent_id' => $spending->respondent_id,
            'respondent_name' => $spending->respondent?->name,
            'bike_id' => $spending->type === 'bike' ? $spending->related_id : null,
            'bike_plate_no' => $spending->type === 'bike' ? $spending->bike?->plate_no : null,
            'batch_id' => $spending->batch_id,
            'batch_no' => $spending->batch?->batch_no,
            'particulars' => $spending->particulars,
            'recorded_by' => $spending->recorded_by,
            'allocations' => $spending->allocations
                ->map(fn ($allocation) => [
                    'id' => $allocation->id,
                    'batch_id' => $allocation->batch_id,
                    'batch_no' => $allocation->batch?->batch_no,
                    'amount' => (float) ($allocation->amount ?? 0),
                    'transaction_cost' => (float) ($allocation->transaction_cost ?? 0),
                    'total' => (float) ($allocation->amount ?? 0) + (float) ($allocation->transaction_cost ?? 0),
                ])
                ->values(),
        ];
    }

    private function mapAllocationRow(array $row): array
    {
        $amount = (float) ($row['amount'] ?? 0);
        $transactionCost = (float) ($row['transaction_cost'] ?? 0);

        return [
            'batch_id' => (int) ($row['batch_id'] ?? 0),
            'amount' => $amount,
            'transaction_cost' => $transactionCost,
            'total' => $amount + $transactionCost,
        ];
    }

    private function denyIfRoleNotIn(Request $request, array $roles)
    {
        $user = $request->attributes->get('pettyUser');
        $role = (string) ($user?->role ?? '');

        if (!in_array($role, $roles, true)) {
            return $this->errorResponse('Forbidden.', 403);
        }

        return null;
    }
}
