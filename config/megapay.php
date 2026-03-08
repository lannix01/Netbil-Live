<?php

return [
    'base_url' => env('MEGAPAY_BASE_URL', 'https://megapay.co.ke/backend/v1'),

    'api_key' => env('MEGAPAY_API_KEY'),
    'email'   => env('MEGAPAY_EMAIL'),

    // MegaPay docs don't show signed webhooks, so we gate with a shared secret token
    'webhook_token' => env('MEGAPAY_WEBHOOK_TOKEN'),

    // Optional: fallback polling if webhook delays/fails
    'status_poll_enabled' => filter_var(env('MEGAPAY_STATUS_POLL_ENABLED', true), FILTER_VALIDATE_BOOL),
];
