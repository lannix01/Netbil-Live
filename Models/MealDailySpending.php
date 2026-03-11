<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class MealDailySpending extends Model
{
    protected $table = 'petty_meal_daily_spendings';

    protected $fillable = [
        'respondent_id',
        'spending_date',
        'amount',
        'notes',
        'meal_payment_id',
        'recorded_by',
    ];

    protected $casts = [
        'spending_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function respondent()
    {
        return $this->belongsTo(Respondent::class, 'respondent_id');
    }

    public function payment()
    {
        return $this->belongsTo(MealPayment::class, 'meal_payment_id');
    }

    public function respondents()
    {
        return $this->belongsToMany(
            Respondent::class,
            'petty_meal_daily_respondents',
            'meal_daily_spending_id',
            'respondent_id'
        )->withTimestamps();
    }
}
