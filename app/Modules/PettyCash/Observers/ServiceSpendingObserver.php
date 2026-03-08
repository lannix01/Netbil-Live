<?php

namespace App\Modules\PettyCash\Observers;

use App\Modules\PettyCash\Models\Ledger;
use App\Modules\PettyCash\Models\ServiceSpending;

class ServiceSpendingObserver
{
    public function created(ServiceSpending $service): void
    {
        // Prevent duplicate ledger rows (unique constraint also protects you)
        $exists = Ledger::where('source_type', 'service')
            ->where('source_id', $service->id)
            ->exists();

        if ($exists) return;

        $plate = null;
        try {
            $plate = $service->bike?->plate_number ?? $service->vehicle?->plate_number ?? null;
        } catch (\Throwable $e) {
            $plate = null;
        }

        Ledger::create([
            'date'        => $service->date ?? now()->toDateString(),
            'reference'   => $service->reference ?? ('SRV-' . $service->id),
            'category'    => 'service',
            'description' => trim('Service spending' . ($plate ? ' - ' . $plate : '') . ($service->description ? ' | ' . $service->description : '')),
            'amount'      => (float) $service->amount,
            'direction'   => 'out',
            'source_type' => 'service',
            'source_id'   => $service->id,
            'created_by'  => auth()->id(),
        ]);
    }
}
