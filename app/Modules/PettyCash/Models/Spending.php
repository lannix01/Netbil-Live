<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Spending extends Model
{
    protected $table = 'petty_spendings';

    protected $fillable = [
        'batch_id',
        'type',
        'sub_type',
        'reference',
        'meter_no',          // ✅ added
        'amount',
        'transaction_cost',
        'date',
        'respondent_id',
        'description',
        'related_id',
        'particulars',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'transaction_cost' => 'decimal:2',
    ];

    public function hostel()
    {
        return $this->belongsTo(Hostel::class, 'related_id');
    }

    public function respondent()
    {
        return $this->belongsTo(\App\Modules\PettyCash\Models\Respondent::class, 'respondent_id');
    }

    public function bike()
    {
        return $this->belongsTo(\App\Modules\PettyCash\Models\Bike::class, 'related_id');
    }

    public function batch()
    {
        return $this->belongsTo(\App\Modules\PettyCash\Models\Batch::class, 'batch_id');
    }

    public function allocations()
    {
        return $this->hasMany(\App\Modules\PettyCash\Models\SpendingAllocation::class, 'spending_id');
    }

    public function getNetTotalAttribute(): float
    {
        return (float)$this->amount + (float)($this->transaction_cost ?? 0);
    }
}
