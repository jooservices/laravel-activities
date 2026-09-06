# Upgrade guide

## 1.x → 4.0.0

Runtime: PHP 8.5, Laravel 12/13, `jooservices/dto ^3.2`, `jooservices/laravel-repository ^4`, `jooservices/exceptions ^4`, `mongodb/laravel-mongodb ^5.10`.

### Composer

```bash
composer require jooservices/laravel-activities:^4.0
```

Apps must already satisfy `dto ^3.2` and `laravel-repository ^4`.

### Behaviour changes

1. **Pagination** — default `list()` is cursor mode. `ActivityListDto::$total` and `$lastPage` are `null`. Use `$hasMore` and `$nextCursor`. Clients that passed `page: 2` to select offset must now set `pagination: 'offset'`.
2. **`recordFor()`** — optional trailing `correlationId`, `batchId`, `tenantId`.
3. **Exceptions** — invalid subject/cursor/filter/config throw `JOOservices\LaravelActivities\Exceptions\*` instead of `InvalidArgumentException`.
4. **Context keys** — `contextKey` must match `[A-Za-z][A-Za-z0-9_]*`. Dots and `$` throw. Optional `activities.context_keys.allow`.
5. **Array store** — production `boot()` throws if `ACTIVITIES_STORE=array`. Keep it for `local`/`testing` only.
6. **Export** — `--format=jsonl|csv`. JSONL uses DTO arrays. CSV prefixes `=+-@` cells. Date filters run in Mongo (`--from` / `--to`).
7. **Prune** — `deleteMany`, cutoff in UTC.
8. **`ActivityMapper`** — removed. Use `ActivityDtoFactory::fromModel()`.
9. **Facade** — accessor is `ActivityManager`. `list` / `forSubject` / `forActor` are instance methods.
10. **`tenant_id`** — optional field + filter + index. The app must pass it; there is no global scope.
11. **`ActivityFilterDto` constructor** — new fields before `limit`. Use named arguments.
12. **Test database** — `jooservices_activities_testing`.

### Config merge

Publish or merge `config/activities.php` for `sanitization.value_patterns`, `limits.*`, and `context_keys.allow`.
