<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class HostelPendingCredit extends Model
{
    protected $table = 'petty_hostel_pending_credits';

    protected $fillable = [
        'hostel_id',
        'amount',
        'reference',
        'notes',
        'status',
        'payment_id',
        'created_by',
        'sorted_by',
        'sorted_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'sorted_at' => 'datetime',
    ];

    public function hostel()
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
