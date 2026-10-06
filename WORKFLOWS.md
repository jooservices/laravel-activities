# Workflow reference

All jobs use GitHub-hosted `ubuntu-latest` runners.

| Workflow | Trigger | Purpose |
| --- | --- | --- |
| `ci.yml` | Push and pull request on `master` or `develop` | Package quality gate |
| `commitlint.yml` | Pull request opened, edited, synchronized, or reopened | Validate commit messages |
| `semantic-pr.yml` | Pull request opened, edited, or synchronized | Validate pull request title |
| `dependabot.yml` | Weekly | Update Composer and GitHub Actions dependencies |
| `scorecard.yml` | Push to `develop`; scheduled; manual | OpenSSF Scorecard analysis |
| `release.yml` | Tag `v*.*.*` | Validate the release and create a GitHub Release |
