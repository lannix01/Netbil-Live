<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class MealPayment extends Model
{
    protected $table = 'petty_meal_payments';

    protected $fillable = [
        'spending_id',
        'respondent_id',
        'batch_id',
        'range_from',
        'range_to',
        'days_count',
        'amount',
        'transaction_cost',
        'reference',
        'date',
        'receiver_name',
        'receiver_phone',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'range_from' => 'date',
        'range_to' => 'date',
        'date' => 'date',
        'days_count' => 'integer',
        'amount' => 'decimal:2',
        'transaction_cost' => 'decimal:2',
    ];

    public function spending()
    {
        return $this->belongsTo(Spending::class, 'spending_id');
    }

    public function respondent()
    {
        return $this->belongsTo(Respondent::class, 'respondent_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function dailySpendings()
    {
        return $this->hasMany(MealDailySpending::class, 'meal_payment_id');
    }
}
