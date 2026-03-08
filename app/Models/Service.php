<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'package_id',
        'start_date',
        'end_date',
        'status',
        'mac_address',
        'rate_per_gb',
        'billing_unit',
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
