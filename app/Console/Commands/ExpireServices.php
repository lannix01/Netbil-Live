<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use Carbon\Carbon;

class ExpireServices extends Command
{
    protected $signature = 'services:expire';
    protected $description = 'Marks expired services as expired automatically';

    public function handle()
{
    $subs = Subscription::where('status','active')
         ->whereNotNull('expires_at')
         ->where('expires_at','<',now())->get();

    foreach($subs as $s) {
        $s->status = 'expired';
        $s->save();

        // remove or disable mikrotik credential
        if($s->type === 'hotspot') {
            $this->ros->removeHotspotUser($s->username);
        } elseif($s->type === 'pppoe') {
            $this->ros->removePppSecret($s->username);
        }
    }
}

}