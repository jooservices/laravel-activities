# Ecosystem boundary

## When to use this package

Use `jooservices/laravel-activities` for **admin UI timeline events** — human-readable product actions tied to a subject (extension, crawl target, user).

Examples: `crawl_target.created`, `crawl.dispatched`, `plugin.settings_updated`.

## When not to use this package

| Concern | Use instead |
|---------|-------------|
| High-volume operational / crawl logs | [`jooservices/laravel-logging`](https://github.com/jooservices/laravel-logging) |
| Plugin compliance / extension lifecycle events | [`jooservices/laravel-events`](https://github.com/jooservices/laravel-events) |
| Security / compliance audit trail | Dedicated audit tables or packages |

## Decision tree

```text
Is this for an admin timeline tab (who did what, when)?
  yes -> laravel-activities
  no  -> Is it high-volume ops/debug output?
          yes -> laravel-logging
          no  -> Is it plugin/extension compliance?
                  yes -> laravel-events
                  no  -> audit or domain-specific store
```

## XCrawlerII mapping

- **Activity tab** on extensions → `laravel-activities`
- **`crawl_logs` replacement** → `laravel-logging` (not activities, not events)
