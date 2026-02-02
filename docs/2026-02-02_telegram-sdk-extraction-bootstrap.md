# Telegram SDK Extraction + Bootstrap

## Scope

Initial extraction of Telegram integration code from `telegram-bots` into this standalone SDK, plus baseline package bootstrapping work.

## What Was Done

- Copied framework-agnostic Telegram integration code into `src/`:
  - `src/TelegramBot/*` (client, enums, models, Telegram-specific exceptions)
  - `src/GuzzleHttp/*` (obfuscated formatter + URI/message helpers)
  - `src/Obfuscator/*`
  - `src/Psr/Log/WrappedLogger.php`
- Excluded Laravel/app-specific code:
  - `TelegramBotHandler` (depends on Laravel `ExceptionHandler`)
- Removed Laravel coupling from extracted code:
  - Replaced `Illuminate\Support\Collection` usage with native arrays in models/client paths
  - Replaced `Illuminate\Support\Str::contains()` with native string checks
  - Removed app-specific cache key utility dependency in client
- Added docs memory system:
  - `docs/README.md`
  - AGENTS instructions now require docs entries for major work/investigations
- Renamed package identity from template to SDK:
  - Composer package name changed to `poor-plebs/telegram-bot-sdk`
  - Namespace changed to `PoorPlebs\\TelegramBotSdk\\*`
- Replaced template examples with SDK-oriented baseline tests:
  - `tests/TelegramBot/TelegramBotClientTest.php`
  - `tests/Obfuscation/TokenObfuscationTest.php`
  - `tests/Support/InMemoryCache.php`

## Outcomes

- Repository now contains SDK-focused source layout and naming.
- Core safety behavior (token obfuscation) and model/client parsing behavior have direct tests.
- Project has an explicit memory mechanism for future agent continuity.

## Follow-up

- Run full CI locally once PHP/Composer are available in the environment.
- Expand tests around additional update shapes (voice, forum topics, invoice/payment flows).
- Finalize public README usage examples for publishing.
