<?php

namespace App\Modules\PettyCash\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Models\Spending;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        // Oldest -> newest for rollover correctness
        $batches = Batch::query()
            ->orderBy('id')
            ->get()
            ->map(function ($b) {
                $creditedNet = (float) Credit::where('batch_id', $b->id)
                    ->selectRaw('COALESCE(SUM(amount - transaction_cost),0) as t')
                    ->value('t');

                $spentNet = (float) Spending::where('batch_id', $b->id)
                    ->selectRaw('COALESCE(SUM(amount + transaction_cost),0) as t')
                    ->value('t');

                $b->credited_net = $creditedNet;
                $b->spent_net = $spentNet;
                $b->raw_balance = $creditedNet - $spentNet;

                return $b;
            });

        $batches = $this->applyRollover($batches);

        // Newest first in UI
        $batches = $batches->sortByDesc('id')->values();

        // NOTE: you said your view is /credits/batches.blade.php
        return view('pettycash::credits.batches', compact('batches'));
    }

    /**
     * Route uses {id} in your web.php
     * so DO NOT typehint Batch $batch here unless you change the route param to {batch}.
     */
    public function show($id)
    {
        $batch = Batch::findOrFail($id);

        // Fetch explicitly (no relying on relations)
        $credits = Credit::query()
            ->where('batch_id', $batch->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $spendings = Spending::query()
            ->where('batch_id', $batch->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $creditedNet = (float) Credit::where('batch_id', $batch->id)
            ->selectRaw('COALESCE(SUM(amount - transaction_cost),0) as t')
            ->value('t');

        $spentNet = (float) Spending::where('batch_id', $batch->id)
            ->selectRaw('COALESCE(SUM(amount + transaction_cost),0) as t')
            ->value('t');

        // Batch-only clamp display
        $effectiveSpent = min($creditedNet, $spentNet);
        $overdraw = max(0, $spentNet - $creditedNet);
        $balance = max(0, $creditedNet - $spentNet);

        // attach for blade convenience
        $batch->credited_net = $creditedNet;
        $batch->spent_net = $spentNet;

        return view('pettycash::credits.batch_show', compact(
            'batch',
            'credits',
            'spendings',
            'balance',
            'effectiveSpent',
            'overdraw'
        ));
    }

    /**
     * Rollover display:
     * - balances never negative per batch
     * - deficit carries forward
     * - spent shown as "credited - effective_balance" (never exceeds credited)
     */
    private function applyRollover($batches)
    {
        $carry = 0.0; // negative means deficit

        foreach ($batches as $b) {
            $b->carry_in = $carry;

            $available = (float)$b->raw_balance + $carry;

            if ($available < 0) {
                $b->effective_balance = 0.0;
                $b->carry_out = $available; // still negative
                $carry = $available;
            } else {
                $b->effective_balance = $available;
                $b->carry_out = 0.0;
                $carry = 0.0;
            }

            $b->effective_spent = max(0.0, (float)$b->credited_net - (float)$b->effective_balance);
        }

        return $batches;
    }
}
