<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class SmsHelper
{
    public static function send($phone, $message)
    {
        $response = Http::post(config('smsgateway.url'), [
            'apikey'     => config('smsgateway.apikey'),
            'partnerID'  => config('smsgateway.partner_id'),
            'message'    => $message,
            'shortcode'  => config('smsgateway.sender_id'),
            'mobile'     => $phone,
        ]);

        return $response->json();
    }
}