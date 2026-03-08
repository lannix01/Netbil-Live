<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\PettyTokenReminders::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Existing schedules
        $schedule->command('services:expire')
            ->daily()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->job(new \App\Jobs\CollectMeteredUsage())
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('subscriptions:expire')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // PettyCash token reminders: first run creates daily notifications, later runs resend checks/SMS.
        $runTimes = (array) config('pettycash.token_notifications.run_times', [
            config('pettycash.token_notifications.run_at', '08:00'),
        ]);
        $runTimes = array_values(array_unique(array_filter($runTimes, fn ($t) => is_string($t) && preg_match('/^\d{2}:\d{2}$/', $t))));
        if (empty($runTimes)) {
            $runTimes = ['08:00', '17:00'];
        }

        foreach ($runTimes as $i => $runAt) {
            $command = $i === 0 ? 'petty:token-reminders' : 'petty:token-reminders --resend';
            $schedule->command($command)
                ->dailyAt($runAt)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/petty-token-reminders.log'));
        }
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
