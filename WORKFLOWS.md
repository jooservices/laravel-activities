# Workflow reference

All jobs use GitHub-hosted `ubuntu-latest` runners.

| Workflow | Trigger | Purpose |
| --- | --- | --- |
| `ci.yml` | Push and pull request on `master` or `develop` | Package quality gate |
| `scorecard.yml` | Push to `develop`; scheduled; manual | OpenSSF Scorecard |
| `release.yml` | Tag `v*.*.*` | GitHub Release and package publication |
