<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Disallow deleting logs from the user interface.
    |--------------------------------------------------------------------------
    */
    'allow_delete' => false,
    'date_format' => false,

    'logs' => [
        'laravel' => [
            'path' => storage_path('logs/laravel.log'),
            'name' => 'Laravel Logs',
            'level' => 'daily',
        ],

        // Добавьте свои кастомные логи здесь
        'payments' => [
            'path' => storage_path('logs/payments/application-service.log'),
            'name' => 'Payments Service Logs',
            'level' => 'daily',
        ],
    ]
];
