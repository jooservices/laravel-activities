<?php

declare(strict_types=1);

return [
    'connection' => env('ACTIVITIES_DB_CONNECTION', 'mongodb'),
    'collection' => env('ACTIVITIES_COLLECTION', 'activities'),
    'default_limit' => (int) env('ACTIVITIES_DEFAULT_LIMIT', 50),
    'max_limit' => (int) env('ACTIVITIES_MAX_LIMIT', 100),
];
