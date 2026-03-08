<?php

namespace App\Services\Sms;

interface SmsGatewayInterface
{
    public function send(string $to, string $message): array;
    public function balance(): array;
}
