# Workflow reference

All jobs use GitHub-hosted `ubuntu-latest` runners.

| Workflow | Trigger | Purpose |
| --- | --- | --- |
| `ci.yml` | Push and pull request on `master` or `develop` | Package quality gate |
| `commitlint.yml` | Pull request opened, edited, synchronized, or reopened | Validate every commit against the trusted base config |
| `semantic-pr.yml` | Pull request opened, edited, synchronized, or reopened | Validate pull request title |
| `dependabot.yml` | Weekly | Update Composer, GitHub Actions, and Commitlint dependencies |
| `scorecard.yml` | Push to `develop`; scheduled; manual | OpenSSF Scorecard analysis |
| `release.yml` | Tag `v*.*.*` | Validate the release and create a GitHub Release |
