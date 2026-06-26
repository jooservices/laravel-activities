# JOOservices Laravel Activities Repository Instructions

This repository is a Laravel package named `jooservices/laravel-activities`.

## Core intent

- Provide append-only MongoDB-backed activity timelines for Laravel apps
- Keep audit/compliance systems separate from product activities
- Use DTOs for record/query payloads and `jooservices/laravel-repository` for persistence access
- Target PHP 8.5+ and Laravel 12 or 13

## Package rules

- Canonical namespace: `JOOservices\LaravelActivities\`
- Activities are append-only; do not add `updated_at`
- `subject_id` and `subject_type` identify the affected object
- `activity` is the machine verb; `description` is human-readable UI copy
- `data` and `context` are JSON objects, never plain text columns
- Inject `ActivityRecorderInterface` and `ActivityQueryInterface`; avoid service location in application controllers

## Quality rules

- formatting authority: `Pint`
- structural checks: `PHPCS`
- static analysis: `PHPStan`
- maintainability checks: `PHPMD`
- tests: `PHPUnit` with real MongoDB integration tests

## Required commands

- `composer lint`
- `composer lint:all`
- `composer lint:fix`
- `composer test`
- `composer test:coverage`
- `composer check`
- `composer ci`

## MongoDB notes

- MongoDB must be available for integration tests and CI
- Run `activities:ensure-indexes` after deploy or migration
- Index on `(subject_type, subject_id, created_at)` and `(context.plugin_slug, created_at)`

## Git workflow

- `master` is the release branch
- `develop` is the integration branch
- Tag releases as `vX.Y.Z`

## Read these skills for non-trivial work

- `.github/skills/repo-quality-foundation/SKILL.md`
- `.github/skills/php-package-development/SKILL.md`
- `.github/skills/documentation-sync/SKILL.md`
