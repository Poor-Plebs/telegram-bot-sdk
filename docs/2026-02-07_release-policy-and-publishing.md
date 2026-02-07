# Release Policy And Publishing Setup

## Scope

Aligned package metadata and release workflow for `poor-plebs/telegram-bot-sdk`.

## Changes Made

- Replaced placeholder Composer author data with project owner metadata:
  - `Petr Levtonov <petr@levtonov.com>`
- Removed `CHANGELOG.md` and switched documentation to tag/release-driven notes.
- Documented versioning and release policy:
  - SemVer tags without `v` prefix.
  - Release notes generated from conventional commits and tags.
- Added `.github/workflows/release.yml`:
  - Creates GitHub release automatically on SemVer tag push.
  - Uses tag notes directly as release notes.
- Added `bin/release-tag` helper:
  - Creates annotated SemVer tags (no `v` prefix) from a notes file.
  - Optional `--push` to push the tag to origin.
- Updated distribution file filtering rules:
  - Synchronized `composer.json` `archive.exclude` with `.gitattributes` `export-ignore`.
  - Added excludes for `.vscode` and `vendor` to prevent local archive bloat.
- Updated agent guidance (`AGENTS.md`) so future sessions follow the same release process.

## Verification

- `bin/dc validate --strict --no-check-lock` passed.
- `bin/dc ci` passed after changes.

## Follow-Up

- Publish to package registries manually when needed.
