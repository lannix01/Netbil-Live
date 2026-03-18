<?php

namespace App\Modules\PettyCash\Services;

use App\Modules\PettyCash\Models\Credit;
use App\Modules\PettyCash\Models\Hostel;
use App\Modules\PettyCash\Models\Payment;
use App\Modules\PettyCash\Models\BikeService;
use App\Modules\PettyCash\Models\PettyNotification;
use App\Modules\PettyCash\Models\PettyNotificationAdminContact;
use App\Modules\PettyCash\Models\PettyNotificationSetting;
use App\Modules\PettyCash\Models\PettySmsTemplateUsage;
use App\Modules\PettyCash\Models\Spending;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class TokenDueNotificationService
{
    public function run(?Carbon $today = null, bool $resend = false): array
    {
        $today = ($today ?: Carbon::today())->copy()->startOfDay();

        $cfg = config('pettycash.token_notifications', []);
        if (!($cfg['enabled'] ?? true)) {
            return ['created' => 0, 'sent_email' => 0, 'sent_sms' => 0, 'skipped' => 0, 'sms_debug_log' => null];
        }

        $semesterMonths = (int)($cfg['semester_months'] ?? 4);
        $limitOverdueSpam = (bool)($cfg['limit_overdue_spam'] ?? false);

        $inAppDays = array_values((array)($cfg['in_app_days'] ?? [3, 2, 1, 0, -1]));
        $outboundDays = array_values((array)($cfg['outbound_days'] ?? [0, -1]));

        $runtime = $this->runtimeSettings($cfg);
        $balances = $this->currentBalanceMetrics();

        $emailEnabled = (bool)($cfg['email_enabled'] ?? true);
        $emailRecipients = array_values(array_filter((array)($cfg['email_recipients'] ?? [])));

        $created = 0;
        $sentEmail = 0;
        $sentSms = 0;
        $skipped = 0;

        $smsDebug = [];
        $dueRows = $this->collectDueRows($today, $semesterMonths);

        $summaryContext = $this->buildSummaryContext(
            $dueRows,
            $today,
            $balances,
            $runtime['low_balance_threshold'],
            $runtime['low_credit_threshold']
        );

        // Summary SMS buckets (summary mode uses due-today only).
        $summaryBuckets = [
            'token_due_today' => [],
            'token_overdue' => [],
        ];

        // Per-hostel queue.
        $perHostelSmsQueue = [];

        foreach ($dueRows as $row) {
            /** @var Hostel $hostel */
            $hostel = $row['hostel'];
            /** @var Carbon $due */
            $due = $row['due'];
            $daysToDue = (int)$row['days_to_due'];
            $dayKey = (int)$row['day_key'];

            // IN-APP creation filter.
            if (!$this->isAllowedByDays($dayKey, $inAppDays)) {
                $skipped++;
                continue;
            }

            if ($limitOverdueSpam && $daysToDue < 0 && $today->dayOfWeekIso !== 1) {
                // Monday only.
                $skipped++;
                continue;
            }

            $type = $this->typeForDayKey($dayKey);
            if (!$type) {
                $skipped++;
                continue;
            }

            $title = $this->titleForType($type, (string)$hostel->hostel_name);
            $message = $this->messageForType($type, $hostel, $due, $daysToDue);

            $dedupeKey = $this->dedupeKey($type, (int)$hostel->id, $due->format('Y-m-d'), $today->format('Y-m-d'));

            $notif = PettyNotification::query()->firstOrCreate(
                ['dedupe_key' => $dedupeKey],
                [
                    'module' => 'pettycash',
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'hostel_id' => $hostel->id,
                    'due_date' => $due->format('Y-m-d'),
                    'days_to_due' => $daysToDue,
                    'hostel_name' => $hostel->hostel_name,
                    'meter_no' => $hostel->meter_no,
                    'phone_no' => $hostel->phone_no,
                    'is_read' => false,
                ]
            );

            $isNew = $notif->wasRecentlyCreated;
            if ($isNew) {
                $created++;
            }

            // Outbound eligibility for follow-up channels.
            $outboundEligible = $this->isAllowedByDays($dayKey, $outboundDays);
            if (!$outboundEligible && !$resend) {
                continue;
            }

            if ($resend && !$outboundEligible) {
                continue;
            }

            // Send email/SMS only if (new OR resend) AND outboundEligible.
            if (!($resend || $isNew)) {
                continue;
            }

            if ($emailEnabled && !empty($emailRecipients)) {
                try {
                    $this->sendInternalEmail($emailRecipients, $notif);
                    $notif->sent_email_at = now();
                    $notif->save();
                    $sentEmail++;
                } catch (\Throwable $e) {
                    $notif->send_error = trim(($notif->send_error ?? '') . "\nEMAIL: " . $e->getMessage());
                    $notif->save();
                }
            }

            if (!$runtime['sms_enabled']) {
                continue;
            }

            if ($runtime['sms_mode'] === 'per_hostel') {
                if (!empty($hostel->phone_no)) {
                    $context = $this->contextForHostelEvent($hostel, $due, $daysToDue, $summaryContext);
                    $context['name'] = (string)$hostel->hostel_name;
                    $context['role'] = 'hostel';
                    $context['phone'] = (string)($hostel->phone_no ?? '');

                    $template = $runtime['template_map'][$type] ?? null;
                    $messageText = $this->renderTemplate($template, $context);
                    if (trim($messageText) === '') {
                        $messageText = $this->smsMessage($notif);
                    }

                    $perHostelSmsQueue[] = [
                        'phone' => (string)$hostel->phone_no,
                        'message' => $messageText,
                        'notif_id' => $notif->id,
                    ];
                }
            } else {
                if ($dayKey === 0) {
                    $summaryBuckets['token_due_today'][] = $this->summaryRow($notif, $hostel, $due, $daysToDue);
                } elseif ($dayKey < 0) {
                    $summaryBuckets['token_overdue'][] = $this->summaryRow($notif, $hostel, $due, $daysToDue);
                }
            }
        }

        if ($runtime['sms_enabled']) {
            if ($runtime['sms_mode'] === 'per_hostel') {
                $sentSms += $this->sendManySms($perHostelSmsQueue, $runtime['sms_gateway'], $smsDebug);
            } else {
                $sentSms += $this->sendSummarySms(
                    $runtime['sms_recipients'],
                    $summaryBuckets,
                    $runtime['template_map'],
                    $summaryContext,
                    $runtime['sms_gateway'],
                    $smsDebug
                );
            }
        }

        // Low balance and low credit alerts to admin contacts.
        [$addedCreated, $addedSms] = $this->dispatchLowLevelAlerts(
            $today,
            $runtime,
            $summaryContext,
            $balances,
            $resend,
            $smsDebug
        );
        $created += $addedCreated;
        $sentSms += $addedSms;

        if (!empty($smsDebug)) {
            Log::info('[PettyTokenReminders SMS]', ['date' => $today->format('Y-m-d'), 'debug' => $smsDebug]);
        }

        return [
            'created' => $created,
            'sent_email' => $sentEmail,
            'sent_sms' => $sentSms,
            'skipped' => $skipped,
            'sms_debug_log' => !empty($smsDebug) ? json_encode($smsDebug) : null,
        ];
    }

    public function runTomorrowShortfallCheck(bool $notifyAdmins = false, ?Carbon $today = null): array
    {
        $today = ($today ?: Carbon::today())->copy()->startOfDay();
        $cfg = config('pettycash.token_notifications', []);
        $semesterMonths = (int)($cfg['semester_months'] ?? 4);

        $runtime = $this->runtimeSettings($cfg);
        $balances = $this->currentBalanceMetrics();
        $dueRows = $this->collectDueRows($today, $semesterMonths);

        $summaryContext = $this->buildSummaryContext(
            $dueRows,
            $today,
            $balances,
            (float)($runtime['low_balance_threshold'] ?? 0),
            (float)($runtime['low_credit_threshold'] ?? 0)
        );

        $dueTomorrowTotal = (float)($summaryContext['due_tomorrow_total_amount'] ?? 0);
        $availableBalance = (float)($balances['balance'] ?? 0);
        $shortfall = max(0, $dueTomorrowTotal - $availableBalance);
        $hasShortfall = $shortfall > 0.0001;

        $summaryContext['due_tomorrow_total_amount'] = number_format($dueTomorrowTotal, 2, '.', '');
        $summaryContext['expected_amount_tomorrow'] = $summaryContext['due_tomorrow_total_amount'];
        $summaryContext['due_tomorrow_shortfall'] = number_format($shortfall, 2, '.', '');
        $summaryContext['shortfall_amount'] = $summaryContext['due_tomorrow_shortfall'];

        $result = [
            'checked_at' => now()->format('Y-m-d H:i:s'),
            'has_shortfall' => $hasShortfall,
            'notify_requested' => $notifyAdmins,
            'available_balance' => $availableBalance,
            'due_tomorrow_total' => $dueTomorrowTotal,
            'shortfall' => $shortfall,
            'sent_sms' => 0,
        ];

        if (!$hasShortfall) {
            return $result;
        }

        $fallbackMessage =
            'Dear {{name}}, petty cash balance is {{balance}}. ' .
            'Expected amount due tomorrow is {{due_tomorrow_total_amount}}. ' .
            'Shortfall is {{due_tomorrow_shortfall}}. Please top up to settle pending token bills.';

        $notif = PettyNotification::query()->firstOrCreate(
            [
                'dedupe_key' => 'pettycash:due_tomorrow_shortfall:run=' . $today->format('Y-m-d'),
            ],
            [
                'module' => 'pettycash',
                'type' => 'due_tomorrow_shortfall',
                'title' => 'Due tomorrow exceeds available balance',
                'message' => strtr($fallbackMessage, [
                    '{{name}}' => 'Admin',
                    '{{balance}}' => number_format($availableBalance, 2, '.', ''),
                    '{{due_tomorrow_total_amount}}' => number_format($dueTomorrowTotal, 2, '.', ''),
                    '{{due_tomorrow_shortfall}}' => number_format($shortfall, 2, '.', ''),
                ]),
                'is_read' => false,
            ]
        );

        $result['notification_created'] = (bool)$notif->wasRecentlyCreated;
        if (!$notifyAdmins) {
            return $result;
        }

        if (!$runtime['sms_enabled'] || empty($runtime['sms_recipients'])) {
            return $result;
        }

        $template = $runtime['template_map']['due_tomorrow_shortfall'] ?? null;
        $smsDebug = [];
        $queue = [];

        foreach ($runtime['sms_recipients'] as $recipient) {
            $context = $this->contextWithRecipient($summaryContext, $recipient);
            $message = $this->renderTemplate($template, $context);
            if (trim($message) === '') {
                $message = $this->renderTemplate($fallbackMessage, $context);
            }

            $queue[] = [
                'phone' => (string)$recipient['phone'],
                'message' => $message,
                'notif_id' => $notif->id,
            ];
        }

        $sent = $this->sendManySms($queue, (string)$runtime['sms_gateway'], $smsDebug);
        $result['sent_sms'] = $sent;
        $result['sms_debug_log'] = !empty($smsDebug) ? json_encode($smsDebug) : null;

        if ($sent > 0) {
            $notif->sent_sms_at = now();
            $notif->save();
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $cfg
     * @return array<string,mixed>
     */
    private function runtimeSettings(array $cfg): array
    {
        $smsMode = (string)($cfg['sms_mode'] ?? 'summary');
        if (!in_array($smsMode, ['summary', 'per_hostel'], true)) {
            $smsMode = 'summary';
        }

        $settings = null;
        if (Schema::hasTable('petty_notification_settings')) {
            $settings = PettyNotificationSetting::current();
        }

        $smsGateway = strtolower((string)($settings?->sms_gateway ?? 'advanta'));
        if (!in_array($smsGateway, ['advanta', 'amazons'], true)) {
            $smsGateway = 'advanta';
        }

        $smsEnabledConfig = (bool)($cfg['sms_enabled'] ?? true);
        $smsEnabledDb = $settings ? (bool)$settings->sms_enabled : true;
        $smsEnabled = $smsEnabledConfig && $smsEnabledDb;

        $smsRecipients = [];
        if (Schema::hasTable('petty_notification_admin_contacts')) {
            $smsRecipients = PettyNotificationAdminContact::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['name', 'role', 'phone_no'])
                ->map(function ($c) {
                    return [
                        'name' => (string)$c->name,
                        'role' => (string)($c->role ?? ''),
                        'phone' => (string)$c->phone_no,
                    ];
                })
                ->all();
        }

        if (empty($smsRecipients)) {
            $cfgRecipients = array_values(array_filter((array)($cfg['sms_recipients'] ?? [])));
            foreach ($cfgRecipients as $phone) {
                $smsRecipients[] = [
                    'name' => 'Admin',
                    'role' => 'admin',
                    'phone' => (string)$phone,
                ];
            }
        }

        // Deduplicate recipients by normalized phone.
        $seen = [];
        $uniqueRecipients = [];
        foreach ($smsRecipients as $recipient) {
            $phone = $this->normalizePhone((string)($recipient['phone'] ?? ''));
            if ($phone === '' || isset($seen[$phone])) {
                continue;
            }
            $seen[$phone] = true;
            $recipient['phone'] = $phone;
            $uniqueRecipients[] = $recipient;
        }

        $templateMap = [];
        if (Schema::hasTable('petty_sms_template_usages') && Schema::hasTable('petty_sms_templates')) {
            $templateMap = PettySmsTemplateUsage::query()
                ->with('template')
                ->get()
                ->mapWithKeys(function (PettySmsTemplateUsage $usage) {
                    return [$usage->event_key => $usage->template?->body];
                })
                ->all();
        }

        return [
            'sms_mode' => $smsMode,
            'sms_gateway' => $smsGateway,
            'sms_enabled' => $smsEnabled,
            'sms_recipients' => $uniqueRecipients,
            'template_map' => $templateMap,
            'low_balance_threshold' => (float)($settings?->low_balance_threshold ?? 0),
            'low_credit_threshold' => (float)($settings?->low_credit_threshold ?? 0),
        ];
    }

    private function dispatchLowLevelAlerts(
        Carbon $today,
        array $runtime,
        array $summaryContext,
        array $balances,
        bool $resend,
        array &$smsDebug
    ): array {
        $created = 0;
        $sentSms = 0;

        if (empty($runtime['sms_recipients'])) {
            return [$created, $sentSms];
        }

        if (!$runtime['sms_enabled']) {
            return [$created, $sentSms];
        }

        $lowBalanceThreshold = (float)($runtime['low_balance_threshold'] ?? 0);
        if ($lowBalanceThreshold > 0 && $balances['balance'] <= $lowBalanceThreshold) {
            [$c, $s] = $this->triggerLowAlert(
                'low_balance',
                'Petty cash balance is low',
                'Current balance is ' . number_format($balances['balance'], 2) . '. Threshold is ' . number_format($lowBalanceThreshold, 2) . '.',
                $today,
                $runtime,
                $summaryContext,
                $resend,
                $smsDebug
            );
            $created += $c;
            $sentSms += $s;
        }

        $lowCreditThreshold = (float)($runtime['low_credit_threshold'] ?? 0);
        if ($lowCreditThreshold > 0 && $balances['credit_balance'] <= $lowCreditThreshold) {
            [$c, $s] = $this->triggerLowAlert(
                'low_credit',
                'Petty cash credit pool is low',
                'Current credit pool is ' . number_format($balances['credit_balance'], 2) . '. Threshold is ' . number_format($lowCreditThreshold, 2) . '.',
                $today,
                $runtime,
                $summaryContext,
                $resend,
                $smsDebug
            );
            $created += $c;
            $sentSms += $s;
        }

        return [$created, $sentSms];
    }

    private function triggerLowAlert(
        string $event,
        string $title,
        string $fallbackMessage,
        Carbon $today,
        array $runtime,
        array $summaryContext,
        bool $resend,
        array &$smsDebug
    ): array {
        $notif = PettyNotification::query()->firstOrCreate(
            [
                'dedupe_key' => 'pettycash:' . $event . ':run=' . $today->format('Y-m-d'),
            ],
            [
                'module' => 'pettycash',
                'type' => $event,
                'title' => $title,
                'message' => $fallbackMessage,
                'is_read' => false,
            ]
        );

        $created = $notif->wasRecentlyCreated ? 1 : 0;

        if (!($resend || $notif->wasRecentlyCreated)) {
            return [$created, 0];
        }

        $template = $runtime['template_map'][$event] ?? null;

        $queue = [];
        foreach ($runtime['sms_recipients'] as $recipient) {
            $context = $this->contextWithRecipient($summaryContext, $recipient);

            $message = $this->renderTemplate($template, $context);
            if (trim($message) === '') {
                $message = $fallbackMessage;
            }

            $queue[] = [
                'phone' => (string)$recipient['phone'],
                'message' => $message,
                'notif_id' => $notif->id,
            ];
        }

        $sent = $this->sendManySms($queue, $runtime['sms_gateway'], $smsDebug);

        if ($sent > 0) {
            $notif->sent_sms_at = now();
            $notif->save();
        }

        return [$created, $sent];
    }

    /**
     * @param array<int,array{phone:string,message:string,notif_id?:int}> $queue
     */
    private function sendManySms(array $queue, string $gateway, array &$smsDebug): int
    {
        if (empty($queue)) {
            return 0;
        }

        $service = $this->smsService($gateway);
        $sent = 0;

        foreach ($queue as $row) {
            $phone = $this->normalizePhone((string)($row['phone'] ?? ''));
            $message = (string)($row['message'] ?? '');
            $notifId = $row['notif_id'] ?? null;

            if ($phone === '' || trim($message) === '') {
                $smsDebug[] = 'skipped invalid SMS payload';
                continue;
            }

            try {
                $res = $service->send($phone, $message);
                if (!$this->isSmsSuccess((array)$res)) {
                    throw new \RuntimeException(json_encode($res));
                }

                if ($notifId) {
                    PettyNotification::whereKey($notifId)->update(['sent_sms_at' => now()]);
                }

                $sent++;
            } catch (\Throwable $e) {
                $smsDebug[] = "send failed {$phone}: " . $e->getMessage();
            }
        }

        return $sent;
    }

    private function smsService(string $gateway): object
    {
        return match (strtolower($gateway)) {
            'amazons' => app(AmazonsSmsService::class),
            default => app(AdvantaSmsService::class),
        };
    }

    /**
     * @param array<string,mixed> $response
     */
    private function isSmsSuccess(array $response): bool
    {
        if (array_key_exists('success', $response)) {
            return (bool)$response['success'];
        }

        if (isset($response['responses'][0]['response-code'])) {
            return (string)$response['responses'][0]['response-code'] === '200';
        }

        if (isset($response['response-code'])) {
            return (string)$response['response-code'] === '200';
        }

        if (isset($response['status'])) {
            return in_array(strtolower((string)$response['status']), ['ok', 'success', 'sent'], true);
        }

        return false;
    }

    /**
     * @param array<int,array{name:string,role:string,phone:string}> $recipients
     * @param array<string,array<int,array<string,mixed>>> $buckets
     * @param array<string,string|null> $templateMap
     * @param array<string,string> $summaryContext
     */
    private function sendSummarySms(
        array $recipients,
        array $buckets,
        array $templateMap,
        array $summaryContext,
        string $gateway,
        array &$smsDebug
    ): int {
        if (empty($recipients)) {
            $smsDebug[] = 'summary mode but no sms recipients';
            return 0;
        }

        $totalSent = 0;
        foreach (['token_due_today', 'token_overdue'] as $eventKey) {
            $items = $buckets[$eventKey] ?? [];
            if (empty($items)) {
                continue;
            }

            $defaultText = $this->buildSummaryText($eventKey, $items);
            if (trim($defaultText) === '') {
                continue;
            }

            $template = $templateMap[$eventKey] ?? null;
            $queue = [];
            foreach ($recipients as $recipient) {
                $context = $this->contextWithRecipient($summaryContext, $recipient);

                $message = $this->renderTemplate($template, $context);
                if (trim($message) === '') {
                    $message = $defaultText;
                }

                $queue[] = [
                    'phone' => (string)$recipient['phone'],
                    'message' => $message,
                ];
            }

            $sent = $this->sendManySms($queue, $gateway, $smsDebug);
            $totalSent += $sent;

            if ($sent > 0) {
                $ids = array_values(array_unique(array_column($items, 'notif_id')));
                if (!empty($ids)) {
                    PettyNotification::whereIn('id', $ids)->update(['sent_sms_at' => now()]);
                }
            }
        }

        if ($totalSent === 0) {
            $smsDebug[] = 'summary empty (no due today/overdue)';
        }

        return $totalSent;
    }

    /**
     * @param array<int,array{hostel:Hostel,due:Carbon,days_to_due:int,day_key:int}> $dueRows
     * @return array<string,string>
     */
    private function buildSummaryContext(
        array $dueRows,
        Carbon $today,
        array $balances,
        float $lowBalanceThreshold,
        float $lowCreditThreshold
    ): array {
        $todayRows = array_values(array_filter($dueRows, fn($r) => (int)$r['day_key'] === 0));
        $tomorrowRows = array_values(array_filter($dueRows, fn($r) => (int)$r['day_key'] === 1));
        $twoDaysRows = array_values(array_filter($dueRows, fn($r) => (int)$r['day_key'] === 2));
        $threeDaysRows = array_values(array_filter($dueRows, fn($r) => (int)$r['day_key'] === 3));
        $overdueRows = array_values(array_filter($dueRows, fn($r) => (int)$r['day_key'] < 0));

        $names = function (array $rows): string {
            $list = [];
            foreach ($rows as $row) {
                /** @var Hostel $hostel */
                $hostel = $row['hostel'];
                $list[] = (string)$hostel->hostel_name;
            }
            return implode(', ', $list);
        };

        $sumAmounts = function (array $rows): float {
            $sum = 0.0;
            foreach ($rows as $row) {
                /** @var Hostel $hostel */
                $hostel = $row['hostel'];
                $sum += (float)($hostel->amount_due ?? 0);
            }
            return $sum;
        };

        $todayAmount = $sumAmounts($todayRows);
        $tomorrowAmount = $sumAmounts($tomorrowRows);

        return [
            'name' => '',
            'role' => '',
            'phone' => '',
            'hostel_name' => '',
            'meter_no' => '',
            'hostel_phone' => '',
            'amount_due' => '',
            'due_date' => '',
            'days_to_due' => '',
            'balance' => number_format((float)$balances['balance'], 2, '.', ''),
            'credit_balance' => number_format((float)$balances['credit_balance'], 2, '.', ''),
            'low_balance_threshold' => number_format($lowBalanceThreshold, 2, '.', ''),
            'low_credit_threshold' => number_format($lowCreditThreshold, 2, '.', ''),
            'total_hostels' => (string)count($dueRows),
            'due_today_count' => (string)count($todayRows),
            'due_tomorrow_count' => (string)count($tomorrowRows),
            'due_tomorrow_total_amount' => number_format($tomorrowAmount, 2, '.', ''),
            'expected_amount_tomorrow' => number_format($tomorrowAmount, 2, '.', ''),
            'due_tomorrow_shortfall' => number_format(0, 2, '.', ''),
            'shortfall_amount' => number_format(0, 2, '.', ''),
            'due_2_days_count' => (string)count($twoDaysRows),
            'due_3_days_count' => (string)count($threeDaysRows),
            'overdue_count' => (string)count($overdueRows),
            'due_today_list' => $names($todayRows),
            'due_tomorrow_list' => $names($tomorrowRows),
            'due_2_days_list' => $names($twoDaysRows),
            'due_3_days_list' => $names($threeDaysRows),
            'overdue_list' => $names($overdueRows),
            'total_due_today_amount' => number_format($todayAmount, 2, '.', ''),
            'amounts' => number_format($todayAmount, 2, '.', ''),
            'generated_at' => $today->format('Y-m-d H:i'),
        ];
    }

    /**
     * @param array<string,string> $summaryContext
     * @param array{name:string,role:string,phone:string} $recipient
     * @return array<string,string>
     */
    private function contextWithRecipient(array $summaryContext, array $recipient): array
    {
        $ctx = $summaryContext;
        $ctx['name'] = (string)($recipient['name'] ?? 'Admin');
        $ctx['role'] = (string)($recipient['role'] ?? '');
        $ctx['phone'] = (string)($recipient['phone'] ?? '');

        return $ctx;
    }

    /**
     * @param array<string,string> $summaryContext
     * @return array<string,string>
     */
    private function contextForHostelEvent(Hostel $hostel, Carbon $due, int $daysToDue, array $summaryContext): array
    {
        $ctx = $summaryContext;
        $ctx['hostel_name'] = (string)($hostel->hostel_name ?? '');
        $ctx['meter_no'] = (string)($hostel->meter_no ?? '');
        $ctx['hostel_phone'] = (string)($hostel->phone_no ?? '');
        $ctx['amount_due'] = number_format((float)($hostel->amount_due ?? 0), 2, '.', '');
        $ctx['due_date'] = $due->format('Y-m-d');
        $ctx['days_to_due'] = (string)$daysToDue;

        return $ctx;
    }

    /**
     * @param array<string,mixed> $values
     */
    private function renderTemplate(?string $template, array $values): string
    {
        if (!is_string($template) || trim($template) === '') {
            return '';
        }

        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $m) use ($values) {
            $k = $m[1] ?? '';
            return (string)($values[$k] ?? '');
        }, $template) ?? '';
    }

    /**
     * @return array<string,float>
     */
    private function currentBalanceMetrics(): array
    {
        $creditedNet = (float) Credit::query()
            ->selectRaw('COALESCE(SUM(amount - transaction_cost), 0) as t')
            ->value('t');

        $spentNet = (float) Spending::query()
            ->selectRaw('COALESCE(SUM(amount + transaction_cost), 0) as t')
            ->value('t');

        $serviceSpentNet = (float) BikeService::query()
            ->selectRaw('COALESCE(SUM(amount + transaction_cost), 0) as t')
            ->value('t');

        $balance = $creditedNet - $spentNet - $serviceSpentNet;

        try {
            $creditBalance = (float) app(FundsAllocatorService::class)->totalNetBalance();
        } catch (\Throwable $e) {
            $creditBalance = $balance;
        }

        return [
            'credited_net' => $creditedNet,
            'spent_net' => $spentNet,
            'service_spent_net' => $serviceSpentNet,
            'balance' => $balance,
            'credit_balance' => $creditBalance,
        ];
    }

    private function isAllowedByDays(int $dayKey, array $allowed): bool
    {
        if ($dayKey < 0) {
            $dayKey = -1;
        }

        return in_array($dayKey, $allowed, true);
    }

    private function typeForDayKey(int $dayKey): ?string
    {
        if ($dayKey === 3) {
            return 'token_due_3';
        }

        if ($dayKey === 2) {
            return 'token_due_2';
        }

        if ($dayKey === 1) {
            return 'token_due_1';
        }

        if ($dayKey === 0) {
            return 'token_due_today';
        }

        if ($dayKey < 0) {
            return 'token_overdue';
        }

        return null;
    }

    private function summaryRow(PettyNotification $notif, Hostel $hostel, Carbon $due, int $daysToDue): array
    {
        return [
            'notif_id' => $notif->id,
            'hostel_name' => $hostel->hostel_name,
            'meter_no' => $hostel->meter_no,
            'phone_no' => $hostel->phone_no,
            'due_date' => $due->format('Y-m-d'),
            'days_to_due' => $daysToDue,
            'amount_due' => (float)($hostel->amount_due ?? 0),
        ];
    }

    private function computeDueDate(string $stake, Carbon $last, int $semesterMonths): Carbon
    {
        return $stake === 'semester'
            ? $last->copy()->addMonthsNoOverflow($semesterMonths)->startOfDay()
            : $last->copy()->addMonthNoOverflow()->startOfDay();
    }

    private function titleForType(string $type, string $hostelName): string
    {
        return match ($type) {
            'token_due_3' => "Token due in 3 days — {$hostelName}",
            'token_due_2' => "Token due in 2 days — {$hostelName}",
            'token_due_1' => "Token due tomorrow — {$hostelName}",
            'token_due_today' => "Token due today — {$hostelName}",
            'token_overdue' => "Token overdue — {$hostelName}",
            default => "Token notice — {$hostelName}",
        };
    }

    private function messageForType(string $type, Hostel $hostel, Carbon $due, int $daysToDue): string
    {
        $base =
            "Hostel: {$hostel->hostel_name}\n" .
            'Meter: ' . ($hostel->meter_no ?? '-') . "\n" .
            'Phone: ' . ($hostel->phone_no ?? '-') . "\n" .
            "Due date: {$due->format('Y-m-d')}\n" .
            'Amount due: ' . number_format((float)$hostel->amount_due, 2);

        return match ($type) {
            'token_due_3' => "Reminder: due in 3 days.\n\n{$base}",
            'token_due_2' => "Reminder: due in 2 days.\n\n{$base}",
            'token_due_1' => "Reminder: due tomorrow.\n\n{$base}",
            'token_due_today' => "Reminder: due today.\n\n{$base}",
            'token_overdue' => 'Action: overdue by ' . abs($daysToDue) . " day(s).\n\n{$base}",
            default => $base,
        };
    }

    private function dedupeKey(string $type, int $hostelId, string $dueDate, string $runDate): string
    {
        return "pettycash:{$type}:hostel={$hostelId}:due={$dueDate}:run={$runDate}";
    }

    private function sendInternalEmail(array $recipients, PettyNotification $notif): void
    {
        Mail::raw($notif->message ?: $notif->title, function ($m) use ($recipients, $notif) {
            $m->to($recipients)->subject($notif->title);
        });
    }

    private function smsMessage(PettyNotification $notif): string
    {
        $parts = [$notif->title];

        if ($notif->due_date) {
            $parts[] = 'Due: ' . $notif->due_date->format('Y-m-d');
        }

        if (!empty($notif->meter_no)) {
            $parts[] = 'Meter: ' . $notif->meter_no;
        }

        return implode(' | ', $parts);
    }

    /**
     * @param array<string,array<int,array<string,mixed>>> $buckets
     */
    private function buildSummaryText(string $eventKey, array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $lines = [match ($eventKey) {
            'token_overdue' => 'OVERDUE TOKENS:',
            default => 'TOKENS DUE TODAY:',
        }];
        $total = 0.0;

        foreach ($items as $item) {
            $amount = (float)($item['amount_due'] ?? 0);
            $total += $amount;
            $lines[] = $item['hostel_name'] . ' (Ksh ' . $this->formatSmsAmount($amount) . ')';
        }

        $footerLabel = $eventKey === 'token_overdue'
            ? 'TOTAL OVERDUE AMOUNT'
            : 'ESTIMATED DAY SPENDING';
        $lines[] = $footerLabel . ': Ksh ' . $this->formatSmsAmount($total);

        return implode("\n", $lines);
    }

    /**
     * @return array<int,array{hostel:Hostel,due:Carbon,days_to_due:int,day_key:int}>
     */
    private function collectDueRows(Carbon $today, int $semesterMonths): array
    {
        $lastPaySub = Payment::query()
            ->selectRaw('hostel_id, MAX(date) as last_payment_date')
            ->groupBy('hostel_id');

        $hostelsQuery = Hostel::query()
            ->leftJoinSub($lastPaySub, 'lp', function ($join) {
                $join->on('lp.hostel_id', '=', 'petty_hostels.id');
            })
            ->select('petty_hostels.*', 'lp.last_payment_date');

        if (Schema::hasColumn('petty_hostels', 'agreement_terminated_at')) {
            $hostelsQuery->whereNull('petty_hostels.agreement_terminated_at');
        }

        $hostels = $hostelsQuery->get();

        $rows = [];
        foreach ($hostels as $h) {
            if (empty($h->last_payment_date)) {
                continue;
            }

            $last = Carbon::parse($h->last_payment_date)->startOfDay();
            $due = $this->computeDueDate((string)($h->stake ?? 'monthly'), $last, $semesterMonths);
            $daysToDue = $today->diffInDays($due, false);
            $dayKey = $daysToDue < 0 ? -1 : $daysToDue;

            $rows[] = [
                'hostel' => $h,
                'due' => $due,
                'days_to_due' => $daysToDue,
                'day_key' => $dayKey,
            ];
        }

        return $rows;
    }

    private function formatSmsAmount(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $phone = preg_replace('/\s+/', '', $phone) ?: '';

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (preg_match('/^07\d{8}$/', $phone)) {
            $phone = '254' . substr($phone, 1);
        }

        return $phone;
    }
}
