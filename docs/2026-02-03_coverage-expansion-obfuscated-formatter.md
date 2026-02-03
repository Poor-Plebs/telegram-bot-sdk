# Coverage expansion: formatter + PSR-7 helpers + exceptions

## Context
Expanded test coverage to make obfuscation behavior robust and explicit, with a focus on `ObfuscatedMessageFormatter` edge cases and failure modes.

## Added test suites
- `tests/Obfuscation/ObfuscatedMessageFormatterTest.php`
- `tests/Obfuscation/RequestExceptionFactoryTest.php`
- `tests/Obfuscation/StringObfuscatorTest.php`
- `tests/GuzzleHttp/Psr7/MessageTest.php`
- `tests/GuzzleHttp/Psr7/UriTest.php`
- `tests/GuzzleHttp/Psr7/FunctionFailureShimsTest.php`
- `tests/Psr/Log/WrappedLoggerTest.php`
- model suites from the same coverage campaign:
  - `tests/TelegramBot/Models/MessageFactoriesTest.php`
  - `tests/TelegramBot/Models/ResponseAndUpdateFactoriesTest.php`
  - `tests/TelegramBot/Models/ValueObjectsAndUpdatesTest.php`

## Key behaviors covered
- URI/userinfo/query/header/request-body/response-body obfuscation combinations.
- Placeholder semantics with and without response objects.
- Non-loggable response handling and unseekable stream behavior.
- Invalid JSON and invalid regex failure paths.
- RequestException factory branches (no response, 3xx, 4xx, 5xx).
- PSR-7 helper edge cases in query parsing, path replacement, and message formatting.

## Technical note
`tests/GuzzleHttp/Psr7/FunctionFailureShimsTest.php` introduces namespace-scoped function shims for `gzdeflate` and `preg_*` to deterministically test otherwise hard-to-hit error branches.

## Result
- `bin/dc test` passes.
- `bin/dc coverage` reports **100.0%** total line coverage.
