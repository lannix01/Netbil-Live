<?php

return [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),

    // Paystack will redirect to this URL with ?reference=xxxx
    'callback_url' => env('PAYSTACK_PAYMENT_CALLBACK', '/payment/success'),

    // Base URL for Paystack API
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
];
