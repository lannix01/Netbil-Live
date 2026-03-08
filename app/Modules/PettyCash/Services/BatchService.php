<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Batch;
use App\Modules\PettyCash\Models\Credit;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BatchService
{
    public function createBatchWithCredit(array $data, ?int $userId = null): Batch
    {
        return DB::transaction(function () use ($data, $userId) {

            // New format: PC-Jan12, PC-Jan12-A, PC-Jan12-B...
            $batchNo = $this->generateBatchNo($data['date']);

            $batch = Batch::create([
                'batch_no' => $batchNo,
                'opening_balance' => $data['amount'],
                'credited_amount' => $data['amount'],
                'created_by' => $userId,
            ]);

            Credit::create([
                'batch_id' => $batch->id,
                'reference' => $data['reference'] ?? null,
                'amount' => $data['amount'],
                'transaction_cost' => $data['transaction_cost'] ?? 0,
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'created_by' => $userId,
            ]);

            return $batch;
        });
    }

    /**
     * Generates a human readable batch number:
     * - First batch on a date: PC-Jan12
     * - Second: PC-Jan12-A
     * - Third:  PC-Jan12-B
     *
     * Notes:
     * - Uses month+day only (no year). If you want year included, tell me and I’ll adjust.
     * - Requires batch_no to be UNIQUE in DB for safety.
     */
    private function generateBatchNo(string $date): string
    {
        $d = Carbon::parse($date);

        // Base like "PC-Jan12"
        $base = 'PC-' . $d->format('M') . $d->format('d');

        // How many already exist for this same base (PC-Jan12, PC-Jan12-A, ...)
        $count = Batch::where('batch_no', 'like', $base . '%')->count();

        if ($count === 0) {
            return $base;
        }

        // Next suffix (A, B, C...)
        // If you ever exceed 26 in a day, we can switch to AA/AB logic, but realistically you won’t.
        $suffix = chr(64 + $count + 1);

        return $base . '-' . $suffix;
    }

    /**
     * Kept for backward compatibility (not used now).
     * If any old code calls nextBatchNo(), we return the new format using today's date.
     */
    public function nextBatchNo(): string
    {
        return $this->generateBatchNo(now()->toDateString());
    }
}