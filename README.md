# jooservices/laravel-activities

[![CI](https://github.com/jooservices/laravel-activities/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/jooservices/laravel-activities/actions/workflows/ci.yml)
[![Coverage (develop)](https://codecov.io/gh/jooservices/laravel-activities/branch/develop/graph/badge.svg)](https://codecov.io/gh/jooservices/laravel-activities/branch/develop)
[![OpenSSF Scorecard](https://api.securityscorecards.dev/projects/github.com/jooservices/laravel-activities/badge)](https://securityscorecards.dev/viewer/?uri=github.com/jooservices/laravel-activities)
[![PHP Version](https://img.shields.io/badge/PHP-8.5%2B-blue.svg)](https://www.php.net/)
[![GitHub Release](https://img.shields.io/github/v/release/jooservices/laravel-activities?display_name=tag)](https://github.com/jooservices/laravel-activities/releases)
[![Packagist Version](https://img.shields.io/packagist/v/jooservices/laravel-activities)](https://packagist.org/packages/jooservices/laravel-activities)
[![Total Downloads](https://img.shields.io/packagist/dt/jooservices/laravel-activities)](https://packagist.org/packages/jooservices/laravel-activities)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Append-only MongoDB-backed activity timeline for Laravel 12 and 13 applications.

> **v4.0.0** requires `jooservices/dto` ^3.2, `jooservices/laravel-repository` ^4, and `jooservices/exceptions` ^4. See [UPGRADE.md](UPGRADE.md).

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

- PHP ^8.5
- Laravel 12 or 13
- MongoDB 6+
- `mongodb/laravel-mongodb` ^5.10

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

## Quick start

```php
use App\Models\Post;
use JOOservices\LaravelActivities\Facades\Activity;

$subject = Post::query()->firstOrFail();

Activity::recordFor(
    subject: $subject,
    activity: 'post.updated',
);
```

See [Recording and querying](docs/02-user-guide/01-recording-and-querying.md) for injected services, filters, and pagination.

## Design notes

This package stores **product timeline activities** only. Ops logs belong in
`jooservices/laravel-logging`. Compliance events belong in `jooservices/laravel-events`.

## Documentation

- [Documentation index](docs/README.md)
- [Installation](docs/01-getting-started/01-installation.md)
- [Recording and querying](docs/02-user-guide/01-recording-and-querying.md)
- [Changelog](CHANGELOG.md)
- [`UPGRADE.md`](UPGRADE.md)
- [Workflows](WORKFLOWS.md)

## Development

```bash
composer check
composer ci
```

`composer ci` enforces the 90% minimum coverage gate. See [Contributing](CONTRIBUTING.md) for local setup and contribution guidance.

## Community

- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Code of Conduct](CODE_OF_CONDUCT.md)
- [Support](SUPPORT.md)
- [Governance](GOVERNANCE.md)

## License

MIT
