<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'petty_credits';

    protected $fillable = [
        'batch_id',
        'reference',
        'amount',
        'transaction_cost',
        'date',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
        'transaction_cost' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
}
