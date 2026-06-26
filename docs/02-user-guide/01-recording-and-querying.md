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

Each list item is an `ActivityDto` with ISO8601 `createdAt`.
