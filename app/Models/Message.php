<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
    'phone',
    'text',
    'sender',
    'status',
    'message_id',
    'gateway_response',
    'sent_at',
];
protected $casts = [
    'gateway_response' => 'array',
    'sent_at' => 'datetime',
];
}
