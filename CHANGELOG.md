# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added

- Optional `tenant_id` on records, filters, indexes, prune, and export
- `SubjectReference::fromExternal()` for non-Eloquent identities
- `recordFor()` trailing `correlationId`, `batchId`, and `tenantId`
- Payload limiter (depth, items, strings, 256 KiB document budget)
- Sanitizer value patterns (Bearer / JWT / PEM) and compact/suffix key matching
- `ActivityFilterGuard` for context-key charset and pagination mode
- CSV export with formula-injection prefix (`activities:export --format=csv`)
- `ActivityManager` facade root, `forActor()` helper
- JOO hygiene: Pint `per`, PHPStan max, CaptainHook, Docker, governance docs
- Dev toolchain: PHPUnit `^12|^13`, PHPCS `^4`, Pint `^1.30`, Larastan `^3.11`

### Changed

- Require `jooservices/dto` `^3.2`, `jooservices/laravel-repository` `^4.0`, `jooservices/exceptions` `^4.0`
- Cursor list responses no longer fake `total` / `lastPage`
- Offset pagination is explicit (`pagination: offset`)
- Prune uses `deleteMany`; export streams chunks with Mongo date filters
- Production refuses `ACTIVITIES_STORE=array`
- Writes use UTC (`CarbonImmutable::now('UTC')`)
- Test database renamed to `jooservices_activities_testing`

### Removed

- `ActivityMapper` (replaced by `ActivityDtoFactory::fromModel()`)

## [1.2.0] - 2026-07-26

### Changed

- Require `jooservices/laravel-repository` `^1.7` and use its canonical `JOOservices\LaravelRepository\` namespace.
- Refresh the Composer lock from published packages and update Guzzle to a release without the current security advisories.
- Align CI and release workflow gates with protected `develop` and `master` branches.
- Add standard CI, Codecov, OpenSSF Scorecard, PHP, license, and Packagist badges to the README.

## [1.1.0] - 2026-07-03

### Added

- Cursor pagination via `ActivityFilterDto::$cursor` and `ActivityListDto::$nextCursor`
- `activities:prune` with retention days and optional context filters
- `activities:doctor` for config, binding, MongoDB, and index readiness checks
- `activities:export` JSONL export command
- Data sanitization for `data` and `context` payloads
- Official `ArrayActivityStore` for PHPUnit (`ACTIVITIES_STORE=array`)
- Top-level `correlation_id` and `batch_id` on activity records
- MongoDB indexes for correlation and batch fields
- CI coverage gate at 90% via `scripts/check-coverage.php`

### Changed

- Default `activities:ensure-indexes` now ensures correlation/batch compound indexes

## [1.0.1] - 2026-06-26

### Fixed

- Remove root `version` field from `composer.json` so `composer validate --strict` passes in CI
- Refresh `composer.lock` metadata after composer schema cleanup
- Run PHPUnit with `--no-coverage` by default so CI test jobs execute without a coverage driver
- Align GitHub Actions test matrix with Laravel 12 and 13 dependency resolution
- Add MongoDB service to the release workflow so `composer check` can run integration tests

## [1.0.0] - 2026-06-26

### Added

- Initial release of `jooservices/laravel-activities`
- MongoDB-backed append-only `activities` collection
- `ActivityRecorderInterface` and `ActivityQueryInterface`
- DTOs: `ActivityRecordDto`, `ActivityDto`, `ActivityFilterDto`, `ActivityListDto`
- `ActivityRepository` extending `jooservices/laravel-repository`
- `Activity` facade with `record`, `recordFor`, `list`, and `forSubject`
- `activities:ensure-indexes` Artisan command
- Laravel 12 and 13 support via Orchestra Testbench CI matrix
