# Architecture overview

## Purpose

`jooservices/laravel-activities` stores append-only product timeline events in MongoDB.

## Boundaries

- **Activities**: UX/admin timeline events (`crawl_target.created`, `crawl.dispatched`)
- **Audit**: compliance/security events stay in dedicated audit tables or packages
- **Operational logs**: high-volume crawl or HTTP logs stay outside this package

## Flow

`Service -> ActivityRecorderInterface -> ActivityRepository -> MongoDB activities collection`

Query flow:

`Controller/Service -> ActivityQueryInterface -> ActivityRepository -> ActivityDto list`

## Storage

```text
activities
  subject_id, subject_type
  activity, description
  data, context
  actor_id, actor_type
  created_at
```

No `updated_at` field is stored.
