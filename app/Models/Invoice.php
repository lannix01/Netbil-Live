<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'invoice_number',
        'invoice_status',
        'status',
        'currency',
        'issued_at',
        'due_date',
        'amount',
        'subtotal_amount',
        'tax_percent',
        'tax_amount',
        'penalty_percent',
        'penalty_amount',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'notes',
        'public_token',
        'public_token_expires_at',
        'last_reminder_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_date' => 'date',
        'public_token_expires_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'amount' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'penalty_percent' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
