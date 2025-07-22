<?php

return [
    'default' => env('PAYMENT_DEFAULT', 'robokassa'),

    'robokassa' => [
        'merchant_login' => env('ROBOKASSA_MERCHANT_LOGIN'),
        'password1' => env('ROBOKASSA_PASSWORD1'),
        'password2' => env('ROBOKASSA_PASSWORD2'),

        'password1_test' => env('ROBOKASSA_PASSWORD1_TEST'),
        'password2_test' => env('ROBOKASSA_PASSWORD2_TEST'),
        'isTest' => (int)env('ROBOKASSA_TEST', 0),
    ],

    'unitpay' => [
        'project_id' => env('UNITPAY_PROJECT_ID'),
        'public_key' => env('UNITPAY_PUBLIC_KEY'),
        'secret_key' => env('UNITPAY_SECRET_KEY'),
        'isTest' => env('UNITPAY_TEST_MODE', false),
    ]
];
