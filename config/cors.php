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
        'recommendations/*',
        'recommendations',
        'payment/*',
        'auth/*'
    ],
    'allowed_headers' => ['Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, cache-control'],
    'allowed_origins' => ['*.tinderone.app', '*.tinderone.ru'],
    'allowed_methods' => ['GET, POST, PUT, DELETE, OPTIONS'],
    'allowed_origins_patterns' => [],
    'supports_credentials' => true,
    'exposed_headers' => [],
    'max_age' => 0,
];
