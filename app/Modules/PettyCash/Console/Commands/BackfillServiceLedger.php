<?php

namespace App\Modules\PettyCash\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\PettyCash\Models\Ledger;
use App\Modules\PettyCash\Models\ServiceSpending;

class BackfillServiceLedger extends Command
{
    protected $signature = 'pettycash:backfill-service-ledger {--dry-run}';
    protected $description = 'Create missing ledger entries for existing service spending records';

    public function handle(): int
    {
        if (!class_exists(ServiceSpending::class)) {
            $this->error("ServiceSpending model not found. Check namespace/path.");
            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        $q = ServiceSpending::query();

        $countAll = (clone $q)->count();
        $this->info("Found {$countAll} service spending records.");

        $created = 0;
        $skipped = 0;

        $q->orderBy('id')->chunk(200, function ($rows) use (&$created, &$skipped, $dry) {
            foreach ($rows as $service) {
                $exists = Ledger::where('source_type', 'service')
                    ->where('source_id', $service->id)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $plate = null;
                try {
                    $plate = $service->bike?->plate_number ?? $service->vehicle?->plate_number ?? null;
                } catch (\Throwable $e) {
                    $plate = null;
                }

                $payload = [
                    'date'        => $service->date ?? now()->toDateString(),
                    'reference'   => $service->reference ?? ('SRV-' . $service->id),
                    'category'    => 'service',
                    'description' => trim('Service spending' . ($plate ? ' - ' . $plate : '') . ($service->description ? ' | ' . $service->description : '')),
                    'amount'      => (float) $service->amount,
                    'direction'   => 'out',
                    'source_type' => 'service',
                    'source_id'   => $service->id,
                    'created_by'  => null,
                ];

                if ($dry) {
                    $created++;
                    continue;
                }

                Ledger::create($payload);
                $created++;
            }
        });

        $this->info("Done. Created: {$created}. Skipped(existing): {$skipped}." . ($dry ? " (dry-run)" : ""));
        return self::SUCCESS;
    }
}
