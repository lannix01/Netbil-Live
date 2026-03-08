<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class BikeService extends Model
{
    protected $table = 'petty_bike_services';

    protected $fillable = [
        'bike_id',
        'service_date',
        'reference',
        'amount',
        'transaction_cost',
        'work_done',
        'next_due_date',
        'recorded_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_due_date' => 'date',
        'amount' => 'decimal:2',
        'transaction_cost' => 'decimal:2',
    ];

    public function bike()
    {
        return $this->belongsTo(Bike::class, 'bike_id');
    }
}
