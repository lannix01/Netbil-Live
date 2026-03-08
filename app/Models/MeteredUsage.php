<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MeteredUsage extends Model
{
    protected $fillable = ['subscription_id','recorded_at','bytes_in','bytes_out','meta'];
    protected $casts = ['recorded_at'=>'datetime','meta'=>'array'];
    public function subscription() { return $this->belongsTo(Subscription::class); }
}
