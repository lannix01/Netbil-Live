<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\PettyCash\Services\TokenDueNotificationService;
use Illuminate\Support\Carbon;

class PettyTokenReminders extends Command
{
    protected $signature = 'petty:token-reminders {--date=} {--resend}';
    protected $description = 'Generate token due notifications (in-app + email + sms)';

    public function handle(): int
    {
        $svc = app(TokenDueNotificationService::class);

        $date = $this->option('date');
        $today = $date ? Carbon::parse($date)->startOfDay() : null;

        $resend = (bool)$this->option('resend');

        $res = $svc->run($today, $resend);

        $this->info("OK created={$res['created']} sent_email={$res['sent_email']} sent_sms={$res['sent_sms']} skipped={$res['skipped']}");

        if (!empty($res['sms_debug_log'])) {
            $this->line("SMS_DEBUG: " . $res['sms_debug_log']);
        }

        return self::SUCCESS;
    }
}
