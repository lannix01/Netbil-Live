<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovementLine extends Model
{
    protected $table = 'inventory_movement_lines';

    protected $fillable = [
        'movement_id',
        'item_id',
        'qty',
        'item_unit_id',
        'serial_no',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(InventoryMovement::class, 'movement_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class, 'item_unit_id');
    }
}
