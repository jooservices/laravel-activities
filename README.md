# JOOservices Laravel Activities

[![codecov](https://codecov.io/gh/jooservices/laravel-activities/branch/master/graph/badge.svg)](https://codecov.io/gh/jooservices/laravel-activities)
[![CI](https://github.com/jooservices/laravel-activities/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/jooservices/laravel-activities/actions/workflows/ci.yml)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/laravel-activities/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/laravel-activities)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Packagist Version](https://img.shields.io/packagist/v/jooservices/laravel-activities)](https://packagist.org/packages/jooservices/laravel-activities)

Append-only MongoDB-backed activity timeline for Laravel 12 and 13 applications.

## Features

- Record subject-scoped activities with optional actor, tenant, description, payload, and context
- Query by subject, actor, tenant, activity prefix, correlation, and date range
- Honest cursor pagination (`hasMore` / `nextCursor`) and explicit offset mode
- Sanitization plus payload limits for `data` and `context`
- Retention pruning via `php artisan activities:prune` (`deleteMany`)
- JSONL and CSV export via `php artisan activities:export`
- Official in-memory store for consumer tests (`ACTIVITIES_STORE=array`, not production)
- DTO-first API using `jooservices/dto` ^3
- Repository layer using `jooservices/laravel-repository` ^4

## Requirements

- PHP 8.5+
- Laravel 12 or 13
- MongoDB 6+
- `mongodb/laravel-mongodb` ^5.7

## Installation

```bash
composer require jooservices/laravel-activities
```

Publish config:

```bash
php artisan vendor:publish --tag=activities-config
```

Ensure indexes:

```bash
php artisan activities:ensure-indexes
```

## Usage

```php
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;

$activities->recordFor(
    subject: $target,
    activity: 'crawl_target.created',
    actor: $actor,
    description: 'Created crawl target',
    data: ['url' => $url],
    context: ['plugin_slug' => $slug],
    correlationId: $correlationId,
    tenantId: $tenantId,
);
```

Query:

```php
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;

$timeline = $query->list(new ActivityFilterDto(
    contextKey: 'plugin_slug',
    contextValue: $slug,
    tenantId: $tenantId,
    limit: 50,
));

$next = $timeline->nextCursor;
$hasMore = $timeline->hasMore;
```

Offset pagination (when a total is required):

```php
$list = $query->list(new ActivityFilterDto(
    pagination: 'offset',
    page: 2,
    limit: 50,
));
```

## Relationship to audit systems

This package stores **product timeline activities** only. Ops logs belong in
`jooservices/laravel-logging`. Compliance events belong in `jooservices/laravel-events`.

## Documentation

- [`docs/README.md`](docs/README.md)
- [`UPGRADE.md`](UPGRADE.md)
- [`AGENTS.md`](AGENTS.md)

## Quality

```bash
composer ci
```

Coverage gate: 90% minimum (`composer test:coverage`).

## License

MIT
