<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paystackpayment extends Model
{
    protected $table = 'paystackpayments';

    protected $fillable = [
        'user_id',
        'reference',
        'channel',
        'currency',
        'amount',
        'status',
        'gateway',
        'authorization_code',
        'customer_email',
        'purpose',
        'item_code',
        'meta',
        'paid_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];
}
