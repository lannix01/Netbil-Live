<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use Carbon\Carbon;

class ExpireServices extends Command
{
    protected $signature = 'services:expire';
    protected $description = 'Marks expired services as expired automatically';

    public function handle(): void
    {
        $today = Carbon::today();

        $expired = Service::where('end_date', '<', $today)
                          ->where('status', '!=', 'expired')
                          ->update(['status' => 'expired']);

        $this->info("✅ $expired services marked as expired.");
    }
}