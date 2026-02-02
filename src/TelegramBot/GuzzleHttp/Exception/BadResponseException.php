<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception;

use GuzzleHttp\Exception\BadResponseException as GuzzleBadResponseException;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Uri;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;
use Psr\Http\Message\UriInterface;

/**
 * Overwritten to obfuscate the telegram bot token in the path if the exception
 * factory is used so that sentry, logs and other tools do not leak the token.
 */
class BadResponseException extends GuzzleBadResponseException
{
    protected static function obfuscateUri(UriInterface $uri): UriInterface
    {
        $uri = Uri::withObfuscatedPathSegment(
            $uri,
            TelegramBotTokenObfuscator::TOKEN_REGEX,
            new TelegramBotTokenObfuscator()
        );

        return $uri;
    }
}
