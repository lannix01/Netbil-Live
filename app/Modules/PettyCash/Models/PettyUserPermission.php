<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class PettyUserPermission extends Model
{
    protected $table = 'petty_user_permissions';

    protected $fillable = [
        'petty_user_id',
        'permissions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(PettyUser::class, 'petty_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(PettyUser::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(PettyUser::class, 'updated_by');
    }
}
