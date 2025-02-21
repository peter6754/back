<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */
    'paths' => [
//        'recommendations/*',
//        'recommendations',
//        'payment/*',
//        'auth/*',
//        'user/*',
        '*'
    ],
    'allowed_headers' => ['Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, cache-control, Login-Token'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        '*.tinderone.app',
        '*.tinderone.ru'
    ],
    'allowed_methods' => ['OPTIONS, DELETE, POST, GET, PUT, PATCH'],
    'allowed_origins_patterns' => [],
    'supports_credentials' => false,
    'exposed_headers' => [],
    'max_age' => 86400
];
