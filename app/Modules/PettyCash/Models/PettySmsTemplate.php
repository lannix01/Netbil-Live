<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class PettySmsTemplate extends Model
{
    protected $table = 'petty_sms_templates';

    protected $fillable = [
        'name',
        'body',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
