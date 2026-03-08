<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Bike extends Model
{
    protected $table = 'petty_bikes';

    protected $fillable = [
        'plate_no',
        'model',
        'status',

        // service tracking
        'last_service_date',
        'next_service_due_date',

        // flags
        'is_unroadworthy',
        'unroadworthy_notes',
        'unroadworthy_at',
        'flagged_at',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'next_service_due_date' => 'date',
        'is_unroadworthy' => 'boolean',
        'unroadworthy_at' => 'datetime',
        'flagged_at' => 'datetime',
    ];
}
