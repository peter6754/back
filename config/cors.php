<?php

return [
    'allowed_headers' => ['Authorization, Content-Type, Accept, X-Requested-With'],
    'allowed_methods' => ['OPTIONS,POST,GET'],
    'paths' => [
        '*'
    ],
    'allowed_origins_patterns' => [],
    'supports_credentials' => false,
    'allowed_origins' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
];
