<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\Sms\AdvantaSmsService;
use App\Services\Sms\AmazonsSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function handle(): void
    {
        $msg = Message::find($this->messageId);
        if (!$msg) return;

        $gatewayName = $msg->sender ?? 'advanta';

        $gateway = match ($gatewayName) {
            'amazons' => app(AmazonsSmsService::class),
            default   => app(AdvantaSmsService::class),
        };

        $res = $gateway->send($msg->phone, $msg->text);

        if (!($res['success'] ?? false)) {
            $msg->status = 'FAILED';
            $msg->gateway_response = array_merge((array) $msg->gateway_response, ['job' => $res]);
            $msg->save();
            return;
        }

        $msg->status = 'SENT';
        $msg->message_id = $res['message_id'] ?? $msg->message_id;
        $msg->gateway_response = array_merge((array) $msg->gateway_response, ['job' => $res]);
        $msg->save();
    }
}
