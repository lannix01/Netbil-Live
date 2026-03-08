<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Services\RouterosService;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire subscriptions that have passed expiry time';

    public function handle(RouterosService $ros)
    {
        $this->info('Checking for expired subscriptions...');
        $subs = Subscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($subs as $s) {
            $this->info("Expiring subscription #{$s->id} ({$s->type})");
            try {
                if ($s->type === 'hotspot' && $s->username) {
                    $ros->removeHotspotUser($s->username);
                }
                if ($s->type === 'pppoe' && $s->username) {
                    $ros->removePppSecret($s->username);
                }
                $s->status = 'expired';
                $s->save();
            } catch (\Throwable $e) {
                Log::error('ExpireSubscriptions error: '.$e->getMessage());
            }
        }

        $this->info('Done.');
    }
}
