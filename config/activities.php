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
    | Use "mongodb" in production. Set "array" in PHPUnit (local/testing only)
    | to use the official in-memory store without a MongoDB server.
    | Production boots refuse "array" and unknown drivers.
    |
    */
    'store' => env('ACTIVITIES_STORE', 'mongodb'),

    'retention' => [
        'enabled' => true,
        'default_days' => 365,
    ],

    'sanitization' => [
        'enabled' => true,
        'case_sensitive' => false,
        'redacted_value' => '[redacted]',
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'access_token',
            'accessToken',
            'refresh_token',
            'secret',
            'client_secret',
            'api_key',
            'apiKey',
            'apikey',
            'authorization',
            'cookie',
            'set-cookie',
            'x-api-key',
            'private_key',
        ],
        'sensitive_patterns' => [],
        'value_patterns' => [
            '/(?i)^Bearer\s+[A-Za-z0-9\-._~+\/=]+$/',
            '/(?i)^eyJ[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/',
            '/-----BEGIN [A-Z ]*PRIVATE KEY-----/',
        ],
    ],

    'limits' => [
        'enabled' => true,
        'max_string_length' => 5000,
        'max_array_items' => 200,
        'max_depth' => 8,
        'max_document_bytes' => 262144,
        'truncate_marker' => '[truncated]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Context-key query allowlist
    |--------------------------------------------------------------------------
    |
    | Empty allow = charset-only ([A-Za-z][A-Za-z0-9_]*). Dots and $ are
    | always rejected. Restrict to known keys in production if the key comes
    | from a query string.
    |
    */
    'context_keys' => [
        'allow' => [],
    ],

    'export' => [
        'chunk_size' => 500,
    ],
];
