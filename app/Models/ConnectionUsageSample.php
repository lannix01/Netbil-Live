<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConnectionUsageSample extends Model
{
    protected $fillable = [
        'connection_id',
        'recorded_at',
        'uptime_seconds',
        'bytes_in',
        'bytes_out',
        'source',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(Connection::class);
    }
}
