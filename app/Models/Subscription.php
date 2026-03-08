<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'customer_id','package_id','type','username','password','mac_address','mk_profile',
        'starts_at','expires_at','price_paid','status','meta'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'meta' => 'array'
    ];

    public function package() { return $this->belongsTo(Package::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
