<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class AmazonsSmsService implements SmsGatewayInterface
{
    public function send(string $to, string $message): array
    {
        return Http::post(
            config('services.sms.amazons.base_url') . '/api/services/sendsms/',
            [
                'apikey'    => config('services.sms.amazons.api_key'),
                'partnerID' => config('services.sms.amazons.partner_id'),
                'message'   => $message,
                'shortcode' => config('services.sms.amazons.sender'),
                'mobile'    => $to,
            ]
        )->json();
    }

    public function balance(): array
    {
        return Http::post(
            config('services.sms.amazons.base_url') . '/api/services/getbalance/',
            [
                'apikey'    => config('services.sms.amazons.api_key'),
                'partnerID' => config('services.sms.amazons.partner_id'),
            ]
        )->json();
    }
}
