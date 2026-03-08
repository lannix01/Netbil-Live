<?php

namespace App\Modules\PettyCash\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Services\BatchService;
use App\Modules\PettyCash\Support\ApiResponder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    use ApiResponder;

    public function index(Request $request)
    {
        $perPageOptions = [15, 25, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 25);
        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $baseQuery = $this->creditBaseQuery($request);

        $credits = (clone $baseQuery)
            ->with('batch')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $totalAmount = (float) (clone $baseQuery)->sum('amount');
        $totalTransactionCost = (float) (clone $baseQuery)->sum('transaction_cost');

        return $this->successResponse([
            'credits' => collect($credits->items())
                ->map(fn (Credit $credit) => $this->mapCredit($credit))
                ->values(),
            'filters' => [
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'batch_id' => $request->query('batch_id'),
                'q' => trim((string) $request->query('q', '')),
                'per_page' => $perPage,
            ],
            'summary' => [
                'total_amount' => round($totalAmount, 2),
                'total_transaction_cost' => round($totalTransactionCost, 2),
                'total_net_amount' => round($totalAmount - $totalTransactionCost, 2),
            ],
        ], 'Credits fetched.', 200, [
            'pagination' => $this->paginationMeta($credits),
        ]);
    }

    public function show(Credit $credit)
    {
        $credit->load('batch');

        return $this->successResponse([
            'credit' => $this->mapCredit($credit),
        ], 'Credit fetched.');
    }

    public function store(Request $request, BatchService $batchService)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->attributes->get('pettyUser');

        $batch = $batchService->createBatchWithCredit($data, $user?->id);
        $credit = Credit::query()
            ->with('batch')
            ->where('batch_id', $batch->id)
            ->orderByDesc('id')
            ->firstOrFail();

        return $this->successResponse([
            'credit' => $this->mapCredit($credit),
        ], 'Credit recorded and batch created.', 201);
    }

    public function update(Request $request, Credit $credit)
    {
        if ($deny = $this->denyIfRoleNotIn($request, ['admin', 'accountant', 'finance'])) {
            return $deny;
        }

        $data = $request->validate([
            'reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'transaction_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'date' => ['sometimes', 'required', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (empty($data)) {
            return $this->errorResponse('No update fields supplied.', 422, [
                'payload' => ['Provide at least one field to update.'],
            ]);
        }

        DB::transaction(function () use ($credit, $data) {
            $credit->update([
                'reference' => array_key_exists('reference', $data) ? $data['reference'] : $credit->reference,
                'amount' => array_key_exists('amount', $data) ? $data['amount'] : $credit->amount,
                'transaction_cost' => array_key_exists('transaction_cost', $data)
                    ? ($data['transaction_cost'] ?? 0)
                    : $credit->transaction_cost,
                'date' => array_key_exists('date', $data) ? $data['date'] : $credit->date,
                'description' => array_key_exists('description', $data) ? $data['description'] : $credit->description,
            ]);

            $batch = Batch::query()->find($credit->batch_id);
            if ($batch) {
                $batch->credited_amount = (float) Credit::query()
                    ->where('batch_id', $batch->id)
                    ->sum('amount');
                $batch->save();
            }
        });

        $credit->refresh()->load('batch');

        return $this->successResponse([
            'credit' => $this->mapCredit($credit),
        ], 'Credit updated.');
    }

    private function creditBaseQuery(Request $request): Builder
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $q = trim((string) $request->query('q', ''));
        $batchId = (int) $request->integer('batch_id', 0);

        return Credit::query()
            ->when($from, fn (Builder $query) => $query->whereDate('date', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('date', '<=', $to))
            ->when($batchId > 0, fn (Builder $query) => $query->where('batch_id', $batchId))
            ->when($q !== '', function (Builder $query) use ($q) {
                $query->where(function (Builder $nested) use ($q) {
                    $nested->where('reference', 'like', '%' . $q . '%')
                        ->orWhere('description', 'like', '%' . $q . '%')
                        ->orWhereHas('batch', fn (Builder $batchQ) => $batchQ->where('batch_no', 'like', '%' . $q . '%'));
                });
            });
    }

    private function mapCredit(Credit $credit): array
    {
        $amount = (float) ($credit->amount ?? 0);
        $transactionCost = (float) ($credit->transaction_cost ?? 0);

        return [
            'id' => $credit->id,
            'batch_id' => $credit->batch_id,
            'batch_no' => $credit->batch?->batch_no,
            'reference' => $credit->reference,
            'amount' => $amount,
            'transaction_cost' => $transactionCost,
            'net_amount' => round($amount - $transactionCost, 2),
            'date' => optional($credit->date)->format('Y-m-d'),
            'description' => $credit->description,
            'created_by' => $credit->created_by,
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
