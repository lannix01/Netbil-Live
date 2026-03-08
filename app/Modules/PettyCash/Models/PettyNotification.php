<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyNotification extends Model
{
    protected $table = 'petty_notifications';

    protected $fillable = [
        'module',
        'type',
        'channel',
        'title',
        'message',
        'hostel_id',
        'due_date',
        'days_to_due',
        'hostel_name',
        'meter_no',
        'phone_no',
        'is_read',
        'read_at',
        'sent_email_at',
        'sent_sms_at',
        'send_error',
        'dedupe_key',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_email_at' => 'datetime',
        'sent_sms_at' => 'datetime',
    ];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }
}
