<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class AdvantaSmsService
{
    protected string $base;
    protected string $apikey;
    protected string $partnerID;
    protected string $sender;

    public function __construct()
    {
        $this->base = rtrim(config('services.sms.advanta.base_url'), '/');
        $this->apikey = config('services.sms.advanta.api_key');
        $this->partnerID = config('services.sms.advanta.partner_id');
        $this->sender = config('services.sms.advanta.sender');
    }

    /* =====================
       BALANCE
    ===================== */
   public function balance(): array
{
    $res = Http::get($this->base.'/api/services/getbalance', [
        'apikey' => $this->apikey,
        'partnerID' => $this->partnerID,
    ]);

    // If it isn't JSON, fall back to raw
    $json = $res->json();
    if (is_array($json)) return $json;

    return ['raw' => $res->body()];
}


    /* =====================
       SEND SINGLE / MULTI
    ===================== */
    public function send(string $mobile, string $message): array
    {
        $res = Http::post($this->base.'/api/services/sendsms', [
            'apikey' => $this->apikey,
            'partnerID' => $this->partnerID,
            'shortcode' => $this->sender,
            'mobile' => $mobile,
            'message' => $message,
        ]);

        $data = $res->json();

        if (
            !$res->ok() ||
            !isset($data['responses'][0]) ||
            $data['responses'][0]['response-code'] != 200
        ) {
            return [
                'success' => false,
                'raw' => $data
            ];
        }

        return [
            'success' => true,
            'message_id' => $data['responses'][0]['messageid'],
            'network_id' => $data['responses'][0]['networkid'] ?? null,
            'mobile' => $data['responses'][0]['mobile'],
        ];
    }

    /* =====================
       BULK
    ===================== */
    public function sendBulk(array $smsList): array
    {
        $payload = [
            'count' => count($smsList),
            'smslist' => $smsList
        ];

        return Http::post(
            $this->base.'/api/services/sendbulk',
            $payload
        )->json();
    }

    /* =====================
       DLR
    ===================== */
    public function getDlr(string $messageID): array
    {
        return Http::get($this->base.'/api/services/getdlr', [
            'apikey' => $this->apikey,
            'partnerID' => $this->partnerID,
            'messageID' => $messageID,
        ])->json();
    }
}
