<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'inventory_departments';

    protected $fillable = ['name', 'code'];

    public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'department_id');
    }
}
