<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7 {
    final class FunctionShimState
    {
        public static bool $forceGzdeflateFailure = false;

        public static bool $forcePregReplaceUnknownFailure = false;
    }

    if (!function_exists(__NAMESPACE__ . '\\gzdeflate')) {
        function gzdeflate(string $data, int $level = -1, int $encoding = ZLIB_ENCODING_RAW): string|false
        {
            if (FunctionShimState::$forceGzdeflateFailure) {
                return false;
            }

            return \gzdeflate($data, $level, $encoding);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\preg_replace_callback')) {
        function preg_replace_callback(
            string|array $pattern,
            callable $callback,
            string|array $subject,
            int $limit = -1,
            ?int &$count = null,
            int $flags = 0,
        ): string|array|null {
            if (FunctionShimState::$forcePregReplaceUnknownFailure) {
                return null;
            }

            return \preg_replace_callback($pattern, $callback, $subject, $limit, $count, $flags);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\preg_last_error')) {
        function preg_last_error(): int
        {
            if (FunctionShimState::$forcePregReplaceUnknownFailure) {
                return PREG_NO_ERROR;
            }

            return \preg_last_error();
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\preg_last_error_msg')) {
        function preg_last_error_msg(): string
        {
            if (FunctionShimState::$forcePregReplaceUnknownFailure) {
                return 'No error';
            }

            return \preg_last_error_msg();
        }
    }
}

namespace {
    use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\FunctionShimState;
    use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Message;
    use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Uri;
    use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;

    it('throws when message compression backend fails', function (): void {
        FunctionShimState::$forceGzdeflateFailure = true;

        try {
            expect(fn (): string => Message::compress(str_repeat('x', 100), 1))
                ->toThrow(RuntimeException::class, 'Failed to compress message.');
        } finally {
            FunctionShimState::$forceGzdeflateFailure = false;
        }
    });

    it('throws unknown regex replacement error when preg returns null without error code', function (): void {
        FunctionShimState::$forcePregReplaceUnknownFailure = true;

        try {
            $uri = new Uri('https://api.telegram.org/bot123456:SECRET/getMe');

            expect(fn (): Psr\Http\Message\UriInterface => Uri::withObfuscatedPathSegment(
                $uri,
                '/bot\\d+:[^\\/]+/',
                new StringObfuscator(),
            ))->toThrow(UnexpectedValueException::class, 'Failed to replace path segment. Unknown error');
        } finally {
            FunctionShimState::$forcePregReplaceUnknownFailure = false;
        }
    });
}
