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
    ];
}
