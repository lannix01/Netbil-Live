<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Commands
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduling
|--------------------------------------------------------------------------
| Keep only what you actually need.
| (Avoid runInBackground on scheduled closures / callback events.)
*/

// Keep if you still use it
Schedule::command('services:expire')
    ->daily()
    ->withoutOverlapping()
    ->runInBackground();

// PettyCash token reminders (core): first run creates daily notifications, later runs resend checks/SMS.
$pettyRunTimes = (array) config('pettycash.token_notifications.run_times', [
    config('pettycash.token_notifications.run_at', '08:00'),
]);
$pettyRunTimes = array_values(array_unique(array_filter($pettyRunTimes, fn ($t) => is_string($t) && preg_match('/^\d{2}:\d{2}$/', $t))));
if (empty($pettyRunTimes)) {
    $pettyRunTimes = ['08:00', '17:00'];
}

foreach ($pettyRunTimes as $i => $runAt) {
    $command = $i === 0 ? 'petty:token-reminders' : 'petty:token-reminders --resend';
    Schedule::command($command)
        ->dailyAt($runAt)
        ->withoutOverlapping()
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/petty-token-reminders.log'));
}
