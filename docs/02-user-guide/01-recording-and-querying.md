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
    correlationId: $correlationId,
    tenantId: $tenantId,
);
```

Non-Eloquent identities:

```php
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Support\SubjectReference;

$ref = SubjectReference::fromExternal('stripe.customer', $customerId);

$recorder->record(new ActivityRecordDto(
    subjectType: $ref->type,
    subjectId: $ref->id,
    activity: 'billing.updated',
    tenantId: $tenantId,
));
```

## Query

Default list mode is **cursor**. Do not read `$total` unless you asked for offset.

```php
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;

$page = $this->activityQuery->list(new ActivityFilterDto(
    contextKey: 'plugin_slug',
    contextValue: $slug,
    tenantId: $tenantId,
    limit: 50,
    cursor: $request->input('cursor'),
));

$nextCursor = $page->nextCursor;
$hasMore = $page->hasMore;
```

Offset (when a total is required):

```php
$list = $this->activityQuery->list(new ActivityFilterDto(
    pagination: 'offset',
    page: 2,
    limit: 50,
    tenantId: $tenantId,
));
```

## Test store

Set `ACTIVITIES_STORE=array` in PHPUnit (`local` / `testing` only). Production refuses this driver.

## Commands

| Command | Purpose |
|---------|---------|
| `activities:ensure-indexes` | Create MongoDB indexes |
| `activities:prune` | Delete activities older than retention days |
| `activities:doctor` | Validate config, bindings, and MongoDB readiness |
| `activities:export` | Export JSONL or CSV (`--format=csv`) |
