<?php

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class ItemGroup extends Model
{
    protected $table = 'inventory_item_groups';

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}