<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PettyUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'petty_users';

    protected $fillable = [
        'name',
        'email',
        'phone_no',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'login_sms_sent_at' => 'datetime',
    ];

    public function apiTokens()
    {
        return $this->hasMany(PettyApiToken::class, 'petty_user_id');
    }

    public function permissionProfile()
    {
        return $this->hasOne(PettyUserPermission::class, 'petty_user_id');
    }
}
