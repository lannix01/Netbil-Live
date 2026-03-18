<?php

return [
    'default_password' => (string) env('INVENTORY_DEFAULT_PASSWORD', '123456789'),
    'sms' => [
        'enabled' => (bool) env('INVENTORY_SMS_ENABLED', true),
        'gateway' => (string) env('INVENTORY_SMS_GATEWAY', 'advanta'),
    ],
    'router_api' => [
        'enabled' => (bool) env('INVENTORY_ROUTER_API_ENABLED', true),
        'base_url' => (string) env('INVENTORY_ROUTER_API_BASE_URL', 'https://api.skybrix.co.ke/v1'),
        'username' => (string) env('INVENTORY_ROUTER_API_USERNAME', env('PETTY_ONT_USERNAME', '')),
        'password' => (string) env('INVENTORY_ROUTER_API_PASSWORD', env('PETTY_ONT_PASSWORD', '')),
        'timeout_seconds' => (int) env('INVENTORY_ROUTER_API_TIMEOUT_SECONDS', 12),
        'default_page_size' => (int) env('INVENTORY_ROUTER_API_PAGE_SIZE', 20),
        'max_page_size' => (int) env('INVENTORY_ROUTER_API_MAX_PAGE_SIZE', 100),
        'collection_page_limit' => (int) env('INVENTORY_ROUTER_API_COLLECTION_PAGE_LIMIT', 0),
    ],
];
