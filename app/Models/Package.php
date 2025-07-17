<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'name', 'speed', 'validity', 'price', 'description', 'status'
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }
}
