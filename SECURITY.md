# Security Policy

## Supported versions

The latest stable release of `jooservices/laravel-activities` is supported for
security fixes.

## Reporting a vulnerability

Do not open public GitHub issues for suspected vulnerabilities.

Report privately to [admin@jooservices.com](mailto:admin@jooservices.com) with:

- a clear summary
- affected package version
- impact
- reproduction details when available

## Scope

This policy covers sanitization and payload limits for `data` / `context`,
store-driver fail-closed behaviour, export (JSONL/CSV), prune, indexes, and CI
configuration.

The package does not implement application authorization or tenant policy. Apps
must pass `tenant_id` when they need isolation.
