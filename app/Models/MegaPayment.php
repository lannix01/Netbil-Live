<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MegaPayment extends Model
{
    protected $table = 'megapayments';

    protected $fillable = [
        'reference',
        'purpose',
        'channel',

        'payable_type',
        'payable_id',

        'customer_id',
        'initiated_by',

        'msisdn',
        'amount',

        'transaction_request_id',
        'merchant_request_id',
        'checkout_request_id',

        'status',
        'response_code',
        'response_description',

        'mpesa_receipt',
        'transaction_id',
        'transaction_date',

        'raw_webhook',
        'meta',

        'initiated_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'raw_webhook' => 'array',
        'meta' => 'array',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
