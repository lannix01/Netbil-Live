<?php

return [
    'ont_directory' => [
        'enabled' => (bool) env('PETTY_ONT_DIRECTORY_ENABLED', true),
        'strict_validation' => (bool) env('PETTY_ONT_STRICT_VALIDATION', true),
        'endpoint' => (string) env('PETTY_ONT_ENDPOINT', 'https://api.skybrix.co.ke/v1/onts'),
        'username' => (string) env('PETTY_ONT_USERNAME', ''),
        'password' => (string) env('PETTY_ONT_PASSWORD', ''),
        'timeout_seconds' => (int) env('PETTY_ONT_TIMEOUT_SECONDS', 12),
        'cache_ttl_seconds' => (int) env('PETTY_ONT_CACHE_TTL_SECONDS', 300),
    ],

    'api' => [
        'version' => (string) env('PETTY_API_VERSION', 'v1'),

        // 0 or negative means token does not expire automatically
        'token_ttl_days' => (int) env('PETTY_API_TOKEN_TTL_DAYS', 30),
        // 0 disables idle timeout expiration
        'token_idle_ttl_minutes' => (int) env('PETTY_API_TOKEN_IDLE_TTL_MINUTES', 0),
        // Extend token expiry window on each authenticated request
        'token_refresh_on_use' => (bool) env('PETTY_API_TOKEN_REFRESH_ON_USE', false),
        // Max active tokens kept per user (oldest are revoked/deleted)
        'max_active_tokens_per_user' => (int) env('PETTY_API_MAX_ACTIVE_TOKENS_PER_USER', 10),

        // Per-minute request limits
        'rate_limit_per_minute' => (int) env('PETTY_API_RATE_LIMIT_PER_MINUTE', 120),
        'login_rate_limit_per_minute' => (int) env('PETTY_API_LOGIN_RATE_LIMIT_PER_MINUTE', 20),

        // Optional deprecation metadata for clients
        'deprecated' => (bool) env('PETTY_API_DEPRECATED', false),
        // Example: "Wed, 31 Dec 2026 23:59:59 GMT"
        'sunset_at' => env('PETTY_API_SUNSET_AT'),
    ],

    'token_notifications' => [
        'enabled' => true,

        // legacy single-run schedule time (kept for backward compatibility)
        'run_at' => '08:00',

        // schedule checks twice daily (server time)
        'run_times' => ['08:00', '17:00'],

        // Billing rules
        'semester_months' => 4, // change to 6 if needed
        'limit_overdue_spam' => false,

        /**
         * IN-APP reminder buckets (what we create notifications for).
         * We'll later expose these as UI checkboxes (3,2,1,today,overdue).
         */
        'in_app_days' => [3, 2, 1, 0, -1], // -1 means "overdue"

        /**
         * Outbound eligibility buckets.
         * Summary SMS currently sends due-today items only; email can still use due today + overdue.
         */
        'outbound_days' => [0, -1], // 0 = due today, -1 = overdue

        // EMAIL
        'email_enabled' => true,
        'email_recipients' => [
            'nichmut43@gmail.com',
        ],

        // SMS
        'sms_enabled' => true,

        // 'summary' recommended to save cost; uses sms_recipients
        'sms_mode' => 'summary',
        'sms_recipients' => [
            '+254745936445',
        ],
    ],
];
