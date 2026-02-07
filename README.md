# poor-plebs/telegram-bot-sdk

[![CI](https://github.com/Poor-Plebs/telegram-bot-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/Poor-Plebs/telegram-bot-sdk/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/Poor-Plebs/telegram-bot-sdk/branch/main/graph/badge.svg)](https://codecov.io/gh/Poor-Plebs/telegram-bot-sdk)

Framework-agnostic PHP SDK for Telegram Bot API integration.

It provides:

- `TelegramBotClient` for API calls (`getUpdates`, `sendMessage`, `setWebhook`, etc.)
- Typed Telegram update/message models
- Token-safe logging and exception message obfuscation utilities

## Requirements

- PHP 8.4+

## Install

```bash
composer require poor-plebs/telegram-bot-sdk
```

## Quick Start

```php
<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\TelegramBot\TelegramBotClient;
use Psr\SimpleCache\CacheInterface;

/** @var CacheInterface $cache */
$client = new TelegramBotClient(
    cache: $cache,
    token: '123456:YOUR_BOT_TOKEN',
    chatId: 123456789,
);

$client->sendMessage('Hello from SDK')->wait();
```

## Development

```bash
composer lint
composer cs
composer static
composer test
```

## Run With Docker (No Local PHP/Composer)

Build and use the local dev container:

```bash
docker compose build php
```

Run any composer command in Docker:

```bash
bin/dc install
bin/dc test
bin/dc static
bin/dc ci
```

## Versioning And Releases

- Versioning follows SemVer (`MAJOR.MINOR.PATCH`) with tags without a `v` prefix (for example `1.2.0`).
- `CHANGELOG.md` is intentionally not used; release notes are generated from conventional commits and tags.
- Keep `composer.json` `archive.exclude` and `.gitattributes` `export-ignore` aligned so dist artifacts stay clean.

### GitHub Release Flow

```bash
# run quality gates
bin/dc ci

# create release notes file for the tag
mkdir -p .github/release-notes
cat > .github/release-notes/1.0.0.md <<'EOF'
1.0.0

- Added: ...
- Changed: ...
- Fixed: ...
EOF

# create and push annotated SemVer tag without "v"
bin/release-tag 1.0.0 .github/release-notes/1.0.0.md --push

# create GitHub release from tag notes
gh release create 1.0.0 --notes-from-tag
```

### Automated GitHub Release (GitHub Actions)

This repository includes `.github/workflows/release.yml`:

- Trigger: push a SemVer tag without `v` prefix (`1.2.3`, `1.2.3-rc.1`).
- Action: create a GitHub release using the tag message as release notes.

## Project Memory

Major work and investigations are tracked in `docs/` using dated files:

- `yyyy-mm-dd_[descriptive_file_name].md`

See `docs/README.md` for conventions.
