# Skill: php-package-development

## Purpose
Guide implementation work inside `jooservices/laravel-activities`.

## Rules
- Use DTOs for record/query payloads
- Repositories extend `JOOservices\LaravelRepository\Repositories\EloquentRepository`
- Activities are append-only; never add update flows
- Use real MongoDB integration tests; do not mock internal persistence
