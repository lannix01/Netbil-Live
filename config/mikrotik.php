<?php

return [
    'host' => env('MIKROTIK_HOST', '10.90.0.106'),
    'user' => env('MIKROTIK_USER', env('MIKROTIK_USERNAME', 'user1')),
    'pass' => env('MIKROTIK_PASS', env('MIKROTIK_PASSWORD', '11111111')),
    'port' => (int) env('MIKROTIK_PORT', 8728),
    'timeout' => (int) env('MIKROTIK_TIMEOUT', 3),
];
