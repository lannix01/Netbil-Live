protected function schedule(Schedule $schedule): void
{
$schedule->command('services:expire')->daily();
}