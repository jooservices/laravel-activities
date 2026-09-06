# jooservices/laravel-activities

This file adds project-only rules. Workspace root `AGENTS.md` remains canonical
for identity, GitHub account, branch model, commit/PR language, runtime policy,
and the general quality gate.

- PHP `^8.5`, Laravel package: `laravel/framework` `^12|^13`, MongoDB via `mongodb/laravel-mongodb` `^5.7`
- Runtime deps: `jooservices/dto` `^3.2`, `jooservices/laravel-repository` `^4`, `jooservices/exceptions` `^4`
- Namespace **must** be `JOOservices\LaravelActivities\` (uppercase `OO`)
- Store append-only admin UI timeline rows only. No dashboards, ops logging, or event sourcing
- Persistence flows through `ActivityPayloadPreparer` → `ActivityRepository` → `Activity`
- `ActivityRepository` is internal
- Recording is synchronous and returns `ActivityDto`. No queue and no domain events in this package
- Array store is a consumer PHPUnit double for `local`/`testing` only. Production `boot()` refuses it
- Package tests hit real MongoDB. Do not add `Activity::fake()`
- Pint `per`. PHPStan level `max` with Larastan and strict-rules
- CaptainHook is required (`composer run post-install-cmd`); never `--no-verify`

## Package rules

- Activities are append-only; do not add `updated_at`
- `subject_id` and `subject_type` identify the affected object
- `activity` is the machine verb; `description` is human-readable UI copy
- `data` and `context` are JSON objects
- Sanitize then limit on every write
- Inject `ActivityRecorderInterface` and `ActivityQueryInterface` in app services
- Facade accessor is `ActivityManager`

## Quality gate

- `composer check` is the local normal gate; `composer ci` is the coverage gate
- PHPUnit class names are `{Subject}Test`; test data uses Faker
