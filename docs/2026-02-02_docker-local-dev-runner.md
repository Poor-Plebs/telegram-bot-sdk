# Docker Local Dev Runner

## Scope

Add a simple Docker-based way to run Composer scripts and tests without installing PHP/Composer locally.

## What Was Added

- `Dockerfile`
  - PHP 8.4 CLI image
  - Installs required tooling (`git`, `unzip`, `zip` extension)
  - Adds Composer binary
- `docker-compose.yml`
  - `php` service for local command execution
  - Mounts repository into `/app`
  - Uses host UID/GID mapping to avoid root-owned generated files
- `bin/dc`
  - Thin wrapper to run Composer commands in Docker:
    - `bin/dc test`
    - `bin/dc static`
    - `bin/dc ci`

## Documentation Updates

- README now contains a “Run With Docker” section.
- AGENTS instructions now mention Docker alternatives for common Composer workflows.

## Follow-up

- If contributors need extra PHP extensions later, add them in `Dockerfile`.
