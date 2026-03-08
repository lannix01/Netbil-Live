<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\MegaPayment;
use App\Models\Payment;
use App\Services\InvoiceBillingService;
use App\Services\InvoiceNotificationService;
use App\Services\MegaPayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MegaPayController extends Controller
{
    private static array $columnCache = [];

    public function __construct(
        private readonly InvoiceBillingService $invoiceBilling,
        private readonly InvoiceNotificationService $invoiceNotifier
    ) {
    }

    /**
     * POST /api/megapay/initiate
     * Body: { msisdn, amount, reference?, purpose?, channel?, meta? }
     */
    public function initiate(Request $request, MegaPayClient $client)
    {
        $data = $request->validate([
            'msisdn' => ['required', 'string', 'min:9', 'max:20'],
            'amount' => ['required', 'integer', 'min:1'],

            // optional context
            'reference' => ['nullable', 'string', 'max:64'],
            'purpose' => ['nullable', 'string', 'max:60'],
            'channel' => ['nullable', 'string', 'max:60'],
            'meta' => ['nullable', 'array'],
        ]);

        $msisdn = MegaPayClient::normalizeMsisdn($data['msisdn']);
        $amount = (int) $data['amount'];

        // Idempotent reference
        $reference = $data['reference'] ?: ('NB_' . now()->format('YmdHis') . '_' . Str::upper(Str::random(8)));

        // If reference exists, return existing (prevents double charging on retries)
        $existing = MegaPayment::query()->where('reference', $reference)->first();
        if ($existing) {
            return response()->json([
                'ok' => true,
                'message' => 'Reference already exists (idempotent)',
                'megapayment' => $existing,
            ]);
        }

        $mp = MegaPayment::create([
            'reference' => $reference,
            'purpose' => $data['purpose'] ?? null,
            'channel' => $data['channel'] ?? null,
            'meta' => $data['meta'] ?? null,

            'msisdn' => $msisdn,
            'amount' => $amount,
            'status' => 'pending',
            'initiated_at' => now(),

            // set initiated_by if user is logged in (admin pushing STK from dashboard)
            'initiated_by' => auth()->check() ? auth()->id() : null,
        ]);

        // If this STK push is tied to invoices, persist linkage for reconciliation.
        if (($data['purpose'] ?? null) === 'invoice') {
            $meta = is_array($mp->meta) ? $mp->meta : [];
            $invoiceIds = array_values(array_filter((array)($meta['invoice_ids'] ?? []), fn($id) => is_numeric($id)));

            if (!empty($invoiceIds)) {
                $firstInvoiceId = (int)$invoiceIds[0];
                $firstInvoice = Invoice::find($firstInvoiceId);

                $mp->payable_type = Invoice::class;
                $mp->payable_id = $firstInvoice?->id;
                $mp->customer_id = $firstInvoice?->customer_id ?? $mp->customer_id;
                $mp->meta = array_merge($meta, ['invoice_ids' => array_map('intval', $invoiceIds)]);
                $mp->save();
            }
        }

        try {
            $resp = $client->initiateStk($amount, $msisdn, $reference);

            $mp->transaction_request_id = $resp['transaction_request_id'] ?? null;
            $mp->merchant_request_id    = $resp['MerchantRequestID'] ?? ($resp['merchant_request_id'] ?? null);
            $mp->checkout_request_id    = $resp['CheckoutRequestID'] ?? ($resp['checkout_request_id'] ?? null);

            // message fields vary in examples; keep both
            $mp->response_description = $resp['message'] ?? ($resp['massage'] ?? $mp->response_description);
            $mp->response_code = isset($resp['ResponseCode']) ? (int)$resp['ResponseCode'] : $mp->response_code;

            // If MegaPay immediately indicates an error, mark failed (but keep record)
            if (isset($resp['ResponseCode']) && (int)$resp['ResponseCode'] !== 0) {
                $mp->status = 'failed';
                $mp->failed_at = now();
            }

            $mp->save();

            return response()->json([
                'ok' => true,
                'message' => $mp->response_description ?: 'STK initiated',
                'reference' => $reference,
                'transaction_request_id' => $mp->transaction_request_id,
                'raw' => $resp,
            ]);
        } catch (\Throwable $e) {
            Log::error('MegaPay initiate failed', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            $mp->status = 'failed';
            $mp->response_description = 'Initiate failed: ' . $e->getMessage();
            $mp->failed_at = now();
            $mp->save();

            return response()->json([
                'ok' => false,
                'message' => 'Failed to initiate STK',
                'error' => $e->getMessage(),
                'reference' => $reference,
            ], 500);
        }
    }

    /**
     * POST /api/megapay/webhook?token=...
     */
    public function webhook(Request $request)
    {
        $expected = (string) config('megapay.webhook_token');
        $token = (string) $request->query('token');

        if (!$expected || !hash_equals($expected, $token)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        Log::info('MegaPay webhook received', ['payload' => $payload]);

        $transactionId = $payload['TransactionID'] ?? null;
        $reference = $payload['TransactionReference'] ?? null;

        if (!$transactionId || !$reference) {
            return response()->json(['ok' => false, 'message' => 'Invalid payload'], 400);
        }

        $mp = MegaPayment::query()->where('reference', $reference)->first();

        // If we don't have it (rare), create orphan record for audit
        if (!$mp) {
            $mp = MegaPayment::create([
                'reference' => $reference,
                'msisdn' => (string) ($payload['Msisdn'] ?? ''),
                'amount' => (int) ($payload['TransactionAmount'] ?? 0),
                'status' => 'pending',
                'initiated_at' => now(),
            ]);
        }

        // Idempotency: if already completed, ack and stop
        if ($mp->status === 'completed') {
            return response()->json(['ok' => true, 'message' => 'Already processed']);
        }

        $responseCode = (int) ($payload['ResponseCode'] ?? 9999);
        $desc = (string) ($payload['ResponseDescription'] ?? 'Unknown');

        $mp->raw_webhook = $payload;
        $mp->response_code = $responseCode;
        $mp->response_description = $desc;

        $mp->transaction_id = (string) ($payload['TransactionID'] ?? $mp->transaction_id);
        $mp->merchant_request_id = (string) ($payload['MerchantRequestID'] ?? $mp->merchant_request_id);
        $mp->checkout_request_id = (string) ($payload['CheckoutRequestID'] ?? $mp->checkout_request_id);
        $mp->mpesa_receipt = (string) ($payload['TransactionReceipt'] ?? $mp->mpesa_receipt);
        $mp->transaction_date = (string) ($payload['TransactionDate'] ?? $mp->transaction_date);

        if ($responseCode === 0) {
            $mp->status = 'completed';
            $mp->completed_at = now();
        } else {
            $mp->status = match ($responseCode) {
                1032 => 'cancelled',
                1037 => 'timeout',
                1019 => 'expired',
                1 => 'failed',
                default => 'failed',
            };
            $mp->failed_at = now();
        }

        $mp->save();

        if ($mp->status === 'completed') {
            $this->applyInvoiceSettlement($mp);
        }

        return response()->json(['ok' => true, 'message' => 'Webhook processed']);
    }

    /**
     * GET /api/megapay/status/{reference}
     * Optional: fallback polling
     */
    public function statusByReference(string $reference, MegaPayClient $client)
    {
        $mp = MegaPayment::query()->where('reference', $reference)->firstOrFail();

        if (in_array($mp->status, ['completed', 'failed', 'cancelled', 'timeout', 'expired'], true)) {
            return response()->json(['ok' => true, 'megapayment' => $mp]);
        }

        if (!config('megapay.status_poll_enabled')) {
            return response()->json(['ok' => true, 'megapayment' => $mp, 'polling' => 'disabled']);
        }

        if (!$mp->transaction_request_id) {
            return response()->json(['ok' => true, 'megapayment' => $mp, 'polling' => 'missing_transaction_request_id']);
        }

        try {
            $resp = $client->transactionStatus($mp->transaction_request_id);

            $status = (string) ($resp['TransactionStatus'] ?? '');
            $receipt = $resp['TransactionReceipt'] ?? null;

            if (strcasecmp($status, 'Completed') === 0) {
                $mp->status = 'completed';
                $mp->mpesa_receipt = (string) ($receipt ?? $mp->mpesa_receipt);
                $mp->completed_at = $mp->completed_at ?: now();
                $mp->response_description = (string) ($resp['ResultDesc'] ?? $mp->response_description);
                $mp->save();

                $this->applyInvoiceSettlement($mp);
            }

            return response()->json([
                'ok' => true,
                'megapayment' => $mp,
                'remote' => $resp,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Status poll failed',
                'error' => $e->getMessage(),
                'megapayment' => $mp,
            ], 500);
        }
    }

    private function applyInvoiceSettlement(MegaPayment $megaPayment): void
    {
        try {
            $meta = is_array($megaPayment->meta) ? $megaPayment->meta : [];
            $invoiceIds = array_values(array_filter((array)($meta['invoice_ids'] ?? []), fn($id) => is_numeric($id)));

            if (empty($invoiceIds)) {
                return;
            }

            $invoices = Invoice::query()
                ->with('customer')
                ->whereIn('id', $invoiceIds)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get();

            if ($invoices->isEmpty()) {
                return;
            }

            $remaining = (float)$megaPayment->amount;
            if ($remaining <= 0) {
                return;
            }

            foreach ($invoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $invoice = $this->invoiceBilling->recalculate($invoice);
                $balance = $this->invoiceOutstanding($invoice);

                if ($balance <= 0) {
                    continue;
                }

                $allocation = round(min($balance, $remaining), 2);
                if ($allocation <= 0) {
                    continue;
                }

                $existingQ = Payment::query()->where('amount', $allocation);
                if ($this->hasColumn('payments', 'reference')) {
                    $existingQ->where('reference', $megaPayment->reference);
                } elseif ($this->hasColumn('payments', 'transaction_id')) {
                    $existingQ->where('transaction_id', $megaPayment->transaction_id);
                }
                if ($this->hasColumn('payments', 'invoice_id')) {
                    $existingQ->where('invoice_id', $invoice->id);
                }

                $existing = $existingQ->first();

                if ($existing) {
                    $remaining = round($remaining - $allocation, 2);
                    continue;
                }

                $paymentPayload = [
                    'customer_id' => $invoice->customer_id,
                    'amount' => $allocation,
                    'method' => 'mpesa',
                    'transaction_id' => $megaPayment->transaction_id,
                ];
                foreach ([
                    'invoice_id' => $invoice->id,
                    'reference' => $megaPayment->reference,
                    'transaction_code' => $megaPayment->mpesa_receipt ?: $megaPayment->transaction_id,
                    'currency' => $invoice->currency ?: 'KES',
                    'status' => 'completed',
                    'paid_at' => now(),
                    'meta' => [
                        'megapayment_id' => $megaPayment->id,
                        'transaction_request_id' => $megaPayment->transaction_request_id,
                    ],
                ] as $column => $value) {
                    if ($this->hasColumn('payments', $column)) {
                        $paymentPayload[$column] = $value;
                    }
                }

                $payment = Payment::create($paymentPayload);

                $remaining = round($remaining - $allocation, 2);
                $invoice = $this->invoiceBilling->recalculate($invoice);

                $this->invoiceNotifier->sendPaymentConfirmation($invoice, $payment, (string)$megaPayment->msisdn);
            }
        } catch (\Throwable $e) {
            Log::error('Invoice settlement failed in MegaPay callback', [
                'reference' => $megaPayment->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function invoiceOutstanding(Invoice $invoice): float
    {
        if ($this->hasColumn('invoices', 'balance_amount')) {
            return round((float)($invoice->balance_amount ?? 0), 2);
        }

        $base = $this->hasColumn('invoices', 'total_amount')
            ? (float)($invoice->total_amount ?? 0)
            : (float)($invoice->amount ?? 0);

        if ($this->hasColumn('invoices', 'paid_amount')) {
            return round(max(0, $base - (float)($invoice->paid_amount ?? 0)), 2);
        }

        return round(max(0, $base), 2);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, self::$columnCache)) {
            try {
                self::$columnCache[$key] = Schema::hasColumn($table, $column);
            } catch (\Throwable) {
                self::$columnCache[$key] = false;
            }
        }

        return self::$columnCache[$key];
    }
}
