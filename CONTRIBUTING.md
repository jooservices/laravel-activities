# Contributing

Contributions to `jooservices/laravel-activities` should keep the package aligned
with Laravel 12/13, PHP 8.5, MongoDB persistence, and the repository quality gates.

## Requirements

- PHP 8.5
- Composer
- MongoDB for integration tests
- the MongoDB PHP extension

## Setup

```bash
composer install
```

CaptainHook installs via Composer post-scripts. Never use `--no-verify`.

## Quality gates

```bash
composer lint
composer lint:all
composer lint:fix
composer format:sanity
composer test
composer test:coverage
composer check
composer ci
```

PHPUnit data must use Faker. Class names are `{Subject}Test`.
