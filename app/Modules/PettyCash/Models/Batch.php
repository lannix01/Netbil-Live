<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $table = 'petty_batches';

    protected $fillable = [
        'batch_no',
        'opening_balance',
        'credited_amount',
        'created_by',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'credited_amount' => 'float',
    ];

    public function credits()
{
    return $this->hasMany(Credit::class, 'batch_id');
}

public function spendings()
{
    return $this->hasMany(Spending::class, 'batch_id');
}

}
