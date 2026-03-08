<?php

return [
    'demo' => [
        'pin' => env('NETBIL_DEMO_PIN'),
        'rate_limit_per_minute' => (int) env('NETBIL_DEMO_RATE_LIMIT_PER_MINUTE', 6),
    ],
];
