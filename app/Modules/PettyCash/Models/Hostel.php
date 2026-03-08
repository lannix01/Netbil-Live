<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    protected $table = 'petty_hostels';

    protected $fillable = [
        'hostel_name',
        'meter_no',
        'phone_no',
        'no_of_routers',
        'stake',
        'amount_due',
    ];

    protected $casts = [
        'no_of_routers' => 'integer',
        'amount_due' => 'float',
    ];
}
