<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\MeteredUsage;
use App\Services\RouterosService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class CollectMeteredUsage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(RouterosService $ros)
    {
        // query router for active hotspot/ppp accounting
        $rows = $ros->getHotspotActive(); // implement to return bytes if available

        foreach ($rows as $r) {
            // attempt to map to subscription by username or mac
            $sub = Subscription::where('username', $r['user'] ?? $r['username'] ?? null)
                ->orWhere('mac_address', $r['mac-address'] ?? null)
                ->where('type', 'metered')
                ->first();

            if (!$sub) continue;

            MeteredUsage::create([
                'subscription_id' => $sub->id,
                'recorded_at' => now(),
                'bytes_in' => $r['bytes-in'] ?? ($r['rx'] ?? 0),
                'bytes_out' => $r['bytes-out'] ?? ($r['tx'] ?? 0),
                'meta' => $r
            ]);
        }

        Log::info('CollectMeteredUsage finished - rows: '.count($rows));
    }
}
