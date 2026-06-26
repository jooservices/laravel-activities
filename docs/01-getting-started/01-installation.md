# Installation

## Composer

```bash
composer require jooservices/laravel-activities
```

## MongoDB connection

Ensure your Laravel app has a `mongodb` connection configured:

```php
'mongodb' => [
    'driver' => 'mongodb',
    'dsn' => env('MONGODB_URI', 'mongodb://127.0.0.1:27017'),
    'database' => env('MONGODB_DATABASE', 'laravel'),
],
```

## Publish config

```bash
php artisan vendor:publish --tag=activities-config
```

## Indexes

```bash
php artisan activities:ensure-indexes
```

Run this after deploy and whenever the activities collection is recreated.
