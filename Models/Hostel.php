<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class Hostel extends Model
{
    protected $table = 'petty_hostels';

    protected $fillable = [
        'hostel_name',
        'contact_person',
        'ont_site_id',
        'ont_site_sn',
        'agreement_type',
        'agreement_label',
        'agreement_terminated_at',
        'agreement_termination_reason',
        'agreement_termination_notes',
        'agreement_transfer_hostel_id',
        'meter_no',
        'phone_no',
        'no_of_routers',
        'stake',
        'amount_due',
        'ont_merged',
    ];

    protected $casts = [
        'no_of_routers' => 'integer',
        'amount_due' => 'float',
        'ont_merged' => 'boolean',
        'agreement_terminated_at' => 'datetime',
    ];

    public function pendingCredits()
    {
        return $this->hasMany(HostelPendingCredit::class, 'hostel_id');
    }
}
