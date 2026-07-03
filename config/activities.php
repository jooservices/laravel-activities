<?php

declare(strict_types=1);

return [
    'connection' => env('ACTIVITIES_DB_CONNECTION', 'mongodb'),
    'collection' => env('ACTIVITIES_COLLECTION', 'activities'),
    'default_limit' => (int) env('ACTIVITIES_DEFAULT_LIMIT', 50),
    'max_limit' => (int) env('ACTIVITIES_MAX_LIMIT', 100),

    /*
    |--------------------------------------------------------------------------
    | Store driver
    |--------------------------------------------------------------------------
    |
    | Use "mongodb" in production. Set "array" in PHPUnit to use the official
    | in-memory store without a MongoDB server.
    |
    */
    'store' => env('ACTIVITIES_STORE', 'mongodb'),

    'retention' => [
        'enabled' => true,
        'default_days' => 365,
        'chunk_size' => 500,
    ],

    'sanitization' => [
        'enabled' => true,
        'case_sensitive' => false,
        'redacted_value' => '[redacted]',
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'secret',
            'client_secret',
            'api_key',
            'apikey',
            'authorization',
            'cookie',
            'x-api-key',
        ],
    ],

    'export' => [
        'chunk_size' => 500,
    ],
];
