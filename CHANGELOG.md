# Changelog

All notable changes to this project will be documented in this file.

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
