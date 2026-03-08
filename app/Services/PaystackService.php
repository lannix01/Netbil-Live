<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    public function initializeTransaction(array $payload): array
    {
        $base = rtrim(config('paystack.base_url'), '/');
        $url = $base . '/transaction/initialize';

        $res = Http::withToken(config('paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        return [
            'ok' => $res->ok(),
            'status' => $res->status(),
            'body' => $res->json(),
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $base = rtrim(config('paystack.base_url'), '/');
        $url = $base . '/transaction/verify/' . urlencode($reference);

        $res = Http::withToken(config('paystack.secret_key'))
            ->acceptJson()
            ->get($url);

        return [
            'ok' => $res->ok(),
            'status' => $res->status(),
            'body' => $res->json(),
        ];
    }

    public function validateWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (!$signatureHeader) return false;

        // HMAC SHA512 of payload using secret key
        $computed = hash_hmac('sha512', $rawBody, config('paystack.secret_key'));
        return hash_equals($computed, $signatureHeader);
    }
}
