<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class InventoryUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'inventory_users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'inventory_role',
        'inventory_enabled',
        'inventory_force_password_change',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
    'inventory_enabled' => 'boolean',
    'inventory_force_password_change' => 'boolean',
    'inventory_password_changed_at' => 'datetime',
];


    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
