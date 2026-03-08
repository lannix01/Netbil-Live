<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name',
        'mk_profile',
        'mikrotik_profile',
        'rate_limit',
        'auto_create_profile',
        'speed',
        'duration',
        'duration_minutes',
        'data_limit',
        'price',
        'category',
        'description',
        'status',
        'is_active',
    ];

    protected $casts = [
        'auto_create_profile' => 'boolean',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];


    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
