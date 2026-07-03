# Recording and querying activities

## Record

Inject the recorder in a service:

```php
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;

$this->activities->recordFor(
    subject: $crawlTarget,
    activity: 'crawl_target.updated',
    actor: $user,
    description: 'Updated crawl target schedule',
    data: ['schedule_enabled' => true],
    context: ['plugin_slug' => $plugin->slug],
);
```

## Query by extension context

```php
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;

$list = $this->activityQuery->list(new ActivityFilterDto(
    contextKey: 'plugin_slug',
    contextValue: 'onejav',
    limit: 50,
));
```

## Query by subject

```php
$list = $this->activityQuery->forSubject($plugin);
```

## Cursor pagination

Pass `cursor` from a previous `ActivityListDto::$nextCursor` to fetch the next page:

```php
$page = $this->activityQuery->list(new ActivityFilterDto(
    subjectType: Plugin::class,
    subjectId: (string) $plugin->getKey(),
    limit: 50,
    cursor: $request->input('cursor'),
));

$nextCursor = $page->nextCursor;
```

## Test store

Set `ACTIVITIES_STORE=array` (or `activities.store=array`) to use the official in-memory store in PHPUnit — no MongoDB required.

## Commands

| Command | Purpose |
|---------|---------|
| `activities:ensure-indexes` | Create MongoDB indexes |
| `activities:prune` | Delete activities older than retention days |
| `activities:doctor` | Validate config, bindings, and MongoDB readiness |
| `activities:export` | Export activities to JSONL |

Each list item is an `ActivityDto` with ISO8601 `createdAt`.
