<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MpesaService
{
    public function stkPush(string $phone, float $amount, array $metadata = [], string $callbackUrl = null)
    {
        // Implement your provider logic (Safaricom Daraja or sandbox)
        // Return provider response (transaction id, merchantRequestID, etc)
        Log::info('STK push requested', compact('phone','amount','metadata','callbackUrl'));
        return ['status' => 'started', 'checkoutRequestID' => 'SIMULATED_'.uniqid()];
    }

    public function handleWebhook(array $payload)
    {
        // Verify signature if present. Normalize payload to array with phone, amount, metadata
        Log::info('mpesa webhook payload', $payload);
        // Return normalized array if recognized
        return [
            'phone' => $payload['phone'] ?? null,
            'amount' => $payload['amount'] ?? null,
            'package_id' => $payload['metadata']['package_id'] ?? null,
            'merchant_request_id' => $payload['merchantRequestID'] ?? null,
            'result' => $payload,
        ];
    }
}
