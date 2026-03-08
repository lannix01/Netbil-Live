<?php

namespace App\Modules\PettyCash\Models;

use Illuminate\Database\Eloquent\Model;

class PettyNotificationSetting extends Model
{
    protected $table = 'petty_notification_settings';

    protected $fillable = [
        'sms_gateway',
        'sms_enabled',
        'low_balance_threshold',
        'low_credit_threshold',
        'updated_by',
    ];

    protected $casts = [
        'sms_enabled' => 'boolean',
        'low_balance_threshold' => 'float',
        'low_credit_threshold' => 'float',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'sms_gateway' => 'advanta',
            'sms_enabled' => true,
            'low_balance_threshold' => 0,
            'low_credit_threshold' => 0,
        ]);
    }
}
