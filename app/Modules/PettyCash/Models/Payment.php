<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'petty_payments';

   protected $fillable = [
    'spending_id',
    'hostel_id',
    'reference',
    'amount',
    'transaction_cost',
    'batch_id',
    'date',
    'receiver_name',
    'receiver_phone',
    'notes',
    'recorded_by',
];


    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
        'transaction_cost' => 'decimal:2',
    ];

public function batch()
{
    return $this->belongsTo(\App\Modules\PettyCash\Models\Batch::class, 'batch_id');
}

public function spending()
{
    return $this->belongsTo(\App\Modules\PettyCash\Models\Spending::class, 'spending_id');
}

}
