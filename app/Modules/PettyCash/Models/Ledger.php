<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    protected $table = 'pettycash_ledgers';

    protected $fillable = [
        'date',
        'reference',
        'category',
        'description',
        'amount',
        'direction',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
}
