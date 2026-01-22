<?php

return [
    'api_key' => env('CINETPAY_API_KEY'),
    'site_id' => env('CINETPAY_SITE_ID'),
    'secret_key' => env('CINETPAY_SECRET_KEY'),

    'notify_url' => env('CINETPAY_NOTIFY_URL'),
    'return_url' => env('CINETPAY_RETURN_URL'),
    'cancel_url' => env('CINETPAY_CANCEL_URL'),

    'currency' => 'XOF',
    'language' => 'fr',

    'base_url' => 'https://api-checkout.cinetpay.com/v2',
    'payment_url' => 'https://api-checkout.cinetpay.com/v2/payment',
    'check_url' => 'https://api-checkout.cinetpay.com/v2/payment/check',
];
