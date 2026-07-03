# JOOservices Laravel Activities

Append-only MongoDB-backed activity timeline for Laravel 12 and 13 applications.

## Features

- Record subject-scoped activities with optional actor, description, payload, and context
- Query activities by subject or context keys such as `plugin_slug`
- Cursor pagination for large timelines (`cursor` / `nextCursor`)
- Data sanitization for sensitive keys in `data` and `context`
- Retention pruning via `php artisan activities:prune`
- Operational checks via `php artisan activities:doctor`
- JSONL export via `php artisan activities:export`
- Official in-memory store for tests (`ACTIVITIES_STORE=array`)
- DTO-first API using `jooservices/dto`
- Repository layer using `jooservices/laravel-repository`
- Append-only storage (`created_at` only; no `updated_at`)
- MongoDB indexes via `php artisan activities:ensure-indexes`

## Requirements

- PHP 8.5+
- Laravel 12 or 13
- MongoDB 6+
- `mongodb/laravel-mongodb`

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

Record an activity:

```php
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;

final class CrawlTargetAdminService
{
    public function __construct(private readonly ActivityRecorderInterface $activities) {}

    public function createTarget(object $target, object $actor): void
    {
        $this->activities->recordFor(
            subject: $target,
            activity: 'crawl_target.created',
            actor: $actor,
            description: 'Created crawl target',
            data: ['url' => 'https://example.test/new'],
            context: ['plugin_slug' => 'onejav'],
        );
    }
}
```

Query activities:

```php
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;

$timeline = app(ActivityQueryInterface::class)->list(new ActivityFilterDto(
    contextKey: 'plugin_slug',
    contextValue: 'onejav',
    limit: 50,
));
```

## Activity document shape

```text
activities
  _id
  subject_id
  subject_type
  activity
  description
  data
  actor_id
  actor_type
  context
  created_at
```

## Relationship to audit systems

This package stores **product timeline activities** only. Compliance or security audit logs should remain in their own dedicated tables or packages.

## Documentation

- [`docs/README.md`](docs/README.md)
- [`AGENTS.md`](AGENTS.md)

## Quality

```bash
composer ci
```

Coverage gate: 90% minimum (`composer test:coverage`).

## License

MIT
