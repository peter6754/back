<?php

return [
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'Payment Service API',
            ],
            'routes' => [
                'api' => 'api/docs',
                'docs' => 'docs',
            ],
            'paths' => [
                /*
                 * File name of the generated json documentation file
                 */
                'docs_json' => 'tinderone-docs.json',

                /*
                 * Absolute paths to directory containing
                 */
                'annotations' => [
                    base_path('app'),
                ]
            ]
        ]
    ],
    'defaults' => [
        'routes' => [
            /*
             * Route for Oauth2 authentication callback.
             */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Route for accessing parsed swagger annotations.
             */
            'docs' => 'docs'
        ],

        'paths' => [
            /*
             * Edit to set the api's base path
             */
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            /*
             * Absolute path to location where parsed annotations will be stored
             */
            'docs' => storage_path('api-docs'),

            /*
             * Absolute path to directories that should be excluded from scanning
             * @deprecated Please use `scanOptions.exclude`
             * `scanOptions.exclude` overwrites this
             */
            'excludes' => [],
        ],

        /*
         * API security definitions. Will be generated into documentation file.
        */
        'securityDefinitions' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'securityScheme' => 'bearerAuth',
                    'bearerFormat' => 'JWT',
                    'scheme' => 'bearer',
                    'type' => 'http'
                ]
            ]
        ],

        /*
         * Set this to `true` in development mode so that docs would be regenerated on each request
         * Set this to `false` to disable swagger generation on production
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),

        /*
         * Edit to trust the proxy's ip address - needed for AWS Load Balancer
         * string[]
         */
        'proxy' => false,

        /*
         * Configs plugin allows to fetch external configs instead of passing them to SwaggerUIBundle.
         * See more at: https://github.com/swagger-api/swagger-ui#configs-plugin
         */
        'additional_config_url' => null,

        /*
         * Apply a sort to the operation list of each API. It can be 'alpha' (sort by paths alphanumerically),
         * 'method' (sort by HTTP method).
         * Default is the order returned by the server unchanged.
         */
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),

        /*
         * Pass the validatorUrl parameter to SwaggerUi init on the JS side.
         * A null value here disables validation.
         */
        'validator_url' => null,

        /*
         * Swagger UI configuration parameters
         */
        'ui' => [
            'display' => [
                'doc_expansion' => 'list'
            ],
        ],

        /*
         * Constants which can be used in annotations
         */
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', '/'),
        ],
    ],
];
