<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemDeployment extends Model
{
    protected $table = 'inventory_item_deployments';

    protected $fillable = [
        'technician_id',
        'item_id',
        'qty',
        'site_code',
        'site_name',
        'reference',
        'notes',
        'created_by',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'technician_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
