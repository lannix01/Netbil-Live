<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sms' => [
        'amazons' => [
            'base_url' => env('AMAZONS_SMS_BASE_URL'),
            'api_key' => env('AMAZONS_SMS_API_KEY'),
            'partner_id' => env('AMAZONS_SMS_PARTNER_ID'),
            'sender' => env('AMAZONS_SMS_SENDER'),
         ],

         'advanta' => [
            'base_url' => env('ADVANTA_SMS_BASE_URL'),
            'api_key' => env('ADVANTA_SMS_API_KEY'),
            'partner_id' => env('ADVANTA_SMS_PARTNER_ID'),
            'sender' => env('ADVANTA_SMS_SENDER'),
        ],
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
