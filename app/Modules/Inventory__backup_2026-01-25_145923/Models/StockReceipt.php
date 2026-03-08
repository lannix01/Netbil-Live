<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockReceipt extends Model
{
    protected $table = 'inventory_stock_receipts';

    protected $fillable = [
        'reference',
        'received_date',
        'supplier_name',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'received_date' => 'date',
    ];

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockReceiptLine::class, 'stock_receipt_id');
    }
}
