<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class SpendingAllocation extends Model
{
    protected $table = 'petty_spending_allocations';

    protected $fillable = [
        'spending_id',
        'batch_id',
        'amount',
        'transaction_cost',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_cost' => 'decimal:2',
    ];

    public function spending()
    {
        return $this->belongsTo(Spending::class, 'spending_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }
}
