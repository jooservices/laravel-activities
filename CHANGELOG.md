# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2026-06-26

### Fixed

- Remove root `version` field from `composer.json` so `composer validate --strict` passes in CI
- Refresh `composer.lock` metadata after composer schema cleanup

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
