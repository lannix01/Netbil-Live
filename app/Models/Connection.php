<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Connection extends Model
{
    protected $fillable = [
        'mac_address',
        'ip_address',
        'package_id',
        'username',
        'started_at',
        'expires_at',
        'ended_at',
        'start_bytes_in',
        'start_bytes_out',
        'bytes_in',
        'bytes_out',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
