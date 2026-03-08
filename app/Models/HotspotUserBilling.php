<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotspotUserBilling extends Model
{
    protected $fillable = [
        'username',
        'customer_id',
        'package_id',
        'rate_per_gb',
        'currency',
        'notify_customer',
    ];

    protected $casts = [
        'rate_per_gb' => 'decimal:2',
        'notify_customer' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
