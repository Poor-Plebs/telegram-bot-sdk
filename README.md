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

## Project Memory

Major work and investigations are tracked in `docs/` using dated files:

- `yyyy-mm-dd_[descriptive_file_name].md`

See `docs/README.md` for conventions.
