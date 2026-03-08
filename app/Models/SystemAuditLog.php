<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_email',
        'actor_role',
        'event',
        'action',
        'description',
        'method',
        'path',
        'route_name',
        'status_code',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
        'status_code' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
