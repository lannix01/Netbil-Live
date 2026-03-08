<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Message;
use App\Models\Payment;
use App\Services\Sms\AdvantaSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InvoiceNotificationService
{
    private static array $columnCache = [];

    public function __construct(
        private readonly AdvantaSmsService $sms,
        private readonly InvoiceBillingService $billing
    ) {
    }

    public function sendInvoiceIssued(Invoice $invoice): array
    {
        $invoice = $this->billing->ensurePublicToken($invoice);
        $customer = $invoice->customer;
        $planLabel = $this->resolvePlanLabel($invoice);

        $phone = $this->normalizePhone((string)($customer?->phone ?? ''));
        if ($phone === '') {
            return ['success' => false, 'message' => 'Customer phone missing.'];
        }

        $msg = sprintf(
            "Your Internet Account has been billed with %s %.2f for %s due by %s. Use the link below to pay: %s",
            $invoice->currency ?: 'KES',
            (float)($invoice->balance_amount ?: $invoice->total_amount ?: $invoice->amount),
            $planLabel,
            optional($invoice->due_date)->format('Y-m-d') ?: 'N/A',
            $this->billing->publicUrl($invoice)
        );

        return $this->sendSmsAndLog($phone, $msg);
    }

    public function sendReminder(Invoice $invoice): array
    {
        $invoice = $this->billing->ensurePublicToken($invoice);
        $customer = $invoice->customer;
        $planLabel = $this->resolvePlanLabel($invoice);

        $phone = $this->normalizePhone((string)($customer?->phone ?? ''));
        if ($phone === '') {
            return ['success' => false, 'message' => 'Customer phone missing.'];
        }

        $msg = sprintf(
            "Reminder: Your Internet Account has a balance of %s %.2f for %s due by %s. Use the link below to pay: %s",
            $invoice->currency ?: 'KES',
            (float)($invoice->balance_amount ?: 0),
            $planLabel,
            optional($invoice->due_date)->format('Y-m-d') ?: 'N/A',
            $this->billing->publicUrl($invoice)
        );

        $result = $this->sendSmsAndLog($phone, $msg);
        if (($result['success'] ?? false) === true && $this->hasInvoiceColumn('last_reminder_at')) {
            $invoice->last_reminder_at = now();
            $invoice->save();
        }

        return $result;
    }

    public function sendPaymentConfirmation(Invoice $invoice, Payment $payment, ?string $recipientMsisdn = null): array
    {
        $customer = $invoice->customer;
        $phone = $this->normalizePhone((string)($recipientMsisdn ?: ($customer?->phone ?? '')));

        if ($phone === '') {
            return ['success' => false, 'message' => 'Customer phone missing.'];
        }

        $username = (string)($customer?->username ?: ($customer?->name ?: 'customer'));
        $reference = (string)($payment->transaction_code ?: ($payment->reference ?: ($payment->transaction_id ?: 'N/A')));

        $msg = sprintf(
            'Dear customer, Your payment of Amount: %s %.2f for invoice:%s as user:%s has been received. Transaction reference: "%s". Thank you.',
            $invoice->currency ?: 'KES',
            (float)$payment->amount,
            $invoice->invoice_number,
            $username,
            $reference
        );

        return $this->sendSmsAndLog($phone, $msg);
    }

    private function resolvePlanLabel(Invoice $invoice): string
    {
        $customer = $invoice->customer;
        $planName = trim((string)($customer?->package?->name ?? ''));

        if ($planName !== '') {
            return $planName;
        }

        return 'assigned plan';
    }

    private function sendSmsAndLog(string $phone, string $text): array
    {
        try {
            $res = $this->sms->send($phone, $text);
            $success = (bool)($res['success'] ?? false);

            $this->logMessage(
                phone: $phone,
                text: $text,
                success: $success,
                messageId: $res['message_id'] ?? null,
                gatewayResponse: $res
            );

            return $res + ['success' => $success];
        } catch (\Throwable $e) {
            Log::error('Invoice SMS send failed', ['error' => $e->getMessage(), 'phone' => $phone]);

            $this->logMessage(
                phone: $phone,
                text: $text,
                success: false,
                messageId: null,
                gatewayResponse: ['error' => $e->getMessage()]
            );

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function logMessage(
        string $phone,
        string $text,
        bool $success,
        ?string $messageId = null,
        ?array $gatewayResponse = null
    ): void {
        try {
            if (!Schema::hasTable('messages')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $payload = [];
        if ($this->hasMessageColumn('phone')) {
            $payload['phone'] = $phone;
        }
        if ($this->hasMessageColumn('text')) {
            $payload['text'] = $text;
        }
        if ($this->hasMessageColumn('sender')) {
            $payload['sender'] = 'advanta';
        }
        if ($this->hasMessageColumn('status')) {
            $payload['status'] = $success ? 'SENT' : 'FAILED';
        }
        if ($this->hasMessageColumn('message_id')) {
            $payload['message_id'] = $messageId;
        }
        if ($this->hasMessageColumn('gateway_response')) {
            $payload['gateway_response'] = $gatewayResponse;
        }
        if ($this->hasMessageColumn('sent_at')) {
            $payload['sent_at'] = now();
        }

        if (empty($payload)) {
            return;
        }

        try {
            Message::query()->create($payload);
        } catch (\Throwable $e) {
            Log::warning('Unable to persist invoice SMS log', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizePhone(string $value): string
    {
        $p = preg_replace('/[^\d+]/', '', trim($value));
        if (!$p) {
            return '';
        }

        if (str_starts_with($p, '+')) {
            $p = ltrim($p, '+');
        }

        if (preg_match('/^0(7\d{8})$/', $p, $m)) {
            return '254' . $m[1];
        }

        if (preg_match('/^(7\d{8})$/', $p, $m)) {
            return '254' . $m[1];
        }

        return $p;
    }

    private function hasInvoiceColumn(string $column): bool
    {
        $key = 'invoices.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn('invoices', $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }

    private function hasMessageColumn(string $column): bool
    {
        $key = 'messages.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn('messages', $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }
}
