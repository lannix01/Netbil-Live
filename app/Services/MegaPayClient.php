<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class MegaPayClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $email;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('megapay.base_url'), '/');
        $this->apiKey  = (string) config('megapay.api_key');
        $this->email   = (string) config('megapay.email');

        if (!$this->apiKey || !$this->email) {
            throw new \RuntimeException('MegaPay config missing: MEGAPAY_API_KEY or MEGAPAY_EMAIL');
        }
    }

    public function initiateStk(int $amountKes, string $msisdn, string $reference): array
    {
        $payload = [
            'api_key' => $this->apiKey,
            'email' => $this->email,
            'amount' => $amountKes,
            'msisdn' => $msisdn,
            'reference' => $reference,
        ];

        $resp = Http::asJson()
            ->timeout(20)
            ->post($this->baseUrl . '/initiatestk', $payload);

        if (!$resp->successful()) {
            throw new RequestException($resp);
        }

        return $resp->json() ?? [];
    }

    public function transactionStatus(string $transactionRequestId): array
    {
        $payload = [
            'api_key' => $this->apiKey,
            'email' => $this->email,
            'transaction_request_id' => $transactionRequestId,
        ];

        $resp = Http::asJson()
            ->timeout(20)
            ->post($this->baseUrl . '/transactionstatus', $payload);

        if (!$resp->successful()) {
            throw new RequestException($resp);
        }

        return $resp->json() ?? [];
    }

    /**
     * Normalize Kenyan phone formats into 2547XXXXXXXX
     */
    public static function normalizeMsisdn(string $msisdn): string
    {
        $msisdn = preg_replace('/\s+/', '', $msisdn ?? '');
        $msisdn = preg_replace('/[^\d+]/', '', $msisdn);

        // 07XXXXXXXX -> 2547XXXXXXXX
        if (preg_match('/^0(7\d{8})$/', $msisdn, $m)) {
            return '254' . $m[1];
        }

        // 7XXXXXXXX -> 2547XXXXXXXX
        if (preg_match('/^(7\d{8})$/', $msisdn, $m)) {
            return '254' . $m[1];
        }

        // +2547XXXXXXXX -> 2547XXXXXXXX
        if (str_starts_with($msisdn, '+')) {
            $msisdn = ltrim($msisdn, '+');
        }

        return $msisdn;
    }
}
