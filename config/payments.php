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
    ]
];
