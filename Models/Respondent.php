<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Respondent extends Model
{
    protected $table = 'petty_respondents';

    protected $fillable = [
        'name',
        'phone',
        'category',
        'profile_title',
        'profile_email',
        'profile_location',
        'profile_notes',
        'profile_photo_path',
        'card_public_token',
        'card_file_path',
        'card_generated_at',
        'card_sms_sent_at',
    ];

    protected $casts = [
        'card_generated_at' => 'datetime',
        'card_sms_sent_at' => 'datetime',
    ];
}
