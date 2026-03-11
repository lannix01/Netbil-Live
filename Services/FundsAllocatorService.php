<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Spending;
use App\Modules\PettyCash\Models\SpendingAllocation;
use Illuminate\Support\Facades\DB;

class FundsAllocatorService
{
    public function totalNetBalance(): float
    {
        $credits = DB::table('petty_credits')
            ->selectRaw('batch_id, COALESCE(SUM(amount - COALESCE(transaction_cost,0)),0) as credited_net')
            ->groupBy('batch_id');

        $allocs = DB::table('petty_spending_allocations')
            ->selectRaw('batch_id, COALESCE(SUM(amount + COALESCE(transaction_cost,0)),0) as spent_net')
            ->groupBy('batch_id');

        $total = (float) Batch::query()
            ->leftJoinSub($credits, 'c', fn($j) => $j->on('petty_batches.id', '=', 'c.batch_id'))
            ->leftJoinSub($allocs, 'a', fn($j) => $j->on('petty_batches.id', '=', 'a.batch_id'))
            ->selectRaw('COALESCE(SUM(COALESCE(c.credited_net,0) - COALESCE(a.spent_net,0)),0) as t')
            ->value('t');

        return round($total, 2);
    }

    /**
     * Returns batches with NET available balance, ordered smallest-first.
     */
    public function batchesWithNetAvailable(?int $onlyBatchId = null)
    {
        $credits = DB::table('petty_credits')
            ->selectRaw('batch_id, COALESCE(SUM(amount - COALESCE(transaction_cost,0)),0) as credited_net')
            ->groupBy('batch_id');

        $allocs = DB::table('petty_spending_allocations')
            ->selectRaw('batch_id, COALESCE(SUM(amount + COALESCE(transaction_cost,0)),0) as spent_net')
            ->groupBy('batch_id');

        $q = Batch::query()
            ->leftJoinSub($credits, 'c', fn($j) => $j->on('petty_batches.id', '=', 'c.batch_id'))
            ->leftJoinSub($allocs, 'a', fn($j) => $j->on('petty_batches.id', '=', 'a.batch_id'))
            ->select('petty_batches.*')
            ->selectRaw('COALESCE(c.credited_net,0) - COALESCE(a.spent_net,0) as available_balance');

        if ($onlyBatchId) $q->where('petty_batches.id', $onlyBatchId);

        return $q->orderBy('available_balance', 'asc')->orderBy('petty_batches.id', 'asc')->get();
    }

    /**
     * Allocate principal first, then fee, across smallest balances first.
     * Also writes allocations to DB for the spending.
     */
    public function allocateSmallestFirst(Spending $spending, float $amount, float $fee, ?int $onlyBatchId = null): array
    {
        $amount = round($amount, 2);
        $fee = round($fee, 2);

        if ($amount <= 0) throw new \InvalidArgumentException('Amount must be > 0');
        if ($fee < 0) throw new \InvalidArgumentException('Fee must be >= 0');

        return DB::transaction(function () use ($spending, $amount, $fee, $onlyBatchId) {
            $requiredTotal = round($amount + $fee, 2);
            $batches = $this->batchesWithNetAvailable($onlyBatchId);
            $availableTotal = round((float) $batches->sum(fn ($b) => (float) $b->available_balance), 2);

            if ($requiredTotal > $availableTotal) {
                throw new \RuntimeException(
                    'Insufficient TOTAL balance. Needed: '
                    . number_format($requiredTotal, 2)
                    . ' Available: '
                    . number_format($availableTotal, 2)
                );
            }

            $needPrincipal = $amount;
            $needFee = $fee;
            $rows = [];

            foreach ($batches as $b) {
                $avail = (float)$b->available_balance;
                if ($avail <= 0) continue;

                // principal first
                $takePrincipal = min($avail, $needPrincipal);
                $avail -= $takePrincipal;
                $needPrincipal -= $takePrincipal;

                // fee after principal
                $takeFee = 0.0;
                if ($needPrincipal <= 0 && $needFee > 0 && $avail > 0) {
                    $takeFee = min($avail, $needFee);
                    $needFee -= $takeFee;
                }

                if ($takePrincipal > 0 || $takeFee > 0) {
                    $rows[] = [
                        'batch_id' => (int)$b->id,
                        'amount' => round($takePrincipal, 2),
                        'transaction_cost' => round($takeFee, 2),
                    ];
                }

                if ($needPrincipal <= 0 && $needFee <= 0) break;
            }

            if ($needPrincipal > 0 || $needFee > 0) {
                $missing = $needPrincipal + $needFee;
                throw new \RuntimeException('Insufficient total balance. Missing: ' . number_format($missing, 2));
            }

            // replace allocations (supports edit/update)
            SpendingAllocation::where('spending_id', $spending->id)->delete();

            foreach ($rows as $r) {
                SpendingAllocation::create([
                    'spending_id' => $spending->id,
                    'batch_id' => $r['batch_id'],
                    'amount' => $r['amount'],
                    'transaction_cost' => $r['transaction_cost'],
                ]);
            }

            // optional primary batch for quick display
            $spending->batch_id = $rows[0]['batch_id'] ?? null;
            $spending->save();

            return ['allocations' => $rows];
        });
    }
}
