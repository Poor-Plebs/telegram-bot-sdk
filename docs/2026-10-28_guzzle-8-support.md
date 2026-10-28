# Guzzle 8 Support

## Scope

Allow `guzzlehttp/guzzle` 8 next to 7 (tracked in Poor-Plebs/telegram-bots#48).
Released together with Guzzle 8 support in the sibling packages:
`guzzle-connect-retry-decider` 1.2.0, `guzzle-obfuscated-formatter` 1.1.0 and
`guzzle-retry-after-middleware` 3.2.0.

## Why The Exception Classes Had To Go

The SDK masked the bot token by replacing Guzzle's `http_errors` middleware
with a copy that used `TelegramBot\GuzzleHttp\Exception\RequestException`,
whose `create()` override relied on Guzzle 7's `obfuscateUri()` hook. Guzzle 8
breaks this in three ways:

- `RequestException::create()` changed its fourth parameter from the handler
  context array to the body summarizer, so no override fits both majors.
- `RequestException` no longer carries responses. They live on the new
  `ResponseException`, whose constructor is final.
- Timeouts after connecting are now `NetworkTimeoutException` instead of
  `ConnectException`, so the old `ConnectException`-only rethrow would have
  leaked the token in those messages.

## Design

`TokenObfuscationMiddleware` wraps Guzzle's stock `http_errors` and rewrites
the message of every rejection (and its previous chain) through reflection.
Rewriting in place keeps exception classes, responses and Guzzle 7 handler
context untouched, so one implementation covers both majors and any future
exception types. Messages without a token are left alone.

## Verification

- `bin/dc ci` and `bin/dc type-coverage` pass on Guzzle 8.3.1.
- Tests also pass on Guzzle 7.16.1 (`composer update 'guzzlehttp/*' -W --with
  'guzzlehttp/guzzle:^7.10'`). PHPStan there only reports unmatched Guzzle 8
  ignores, which `reportUnmatchedIgnoredErrors: false` silences.
