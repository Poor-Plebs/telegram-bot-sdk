<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception;

use GuzzleHttp\BodySummarizer;
use GuzzleHttp\BodySummarizerInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Uri;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Throwable;

/**
 * Overwritten to obfuscate the telegram bot token in the path if the exception
 * factory is used so that sentry, logs and other tools do not leak the token.
 */
class RequestException extends GuzzleRequestException
{
    /**
     * Factory method to create a new exception with a normalized error message
     * and obfuscated URI.
     *
     * @param array<mixed> $handlerContext
     */
    public static function create(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        ?Throwable $previous = null,
        array $handlerContext = [],
        ?BodySummarizerInterface $bodySummarizer = null
    ): GuzzleRequestException {
        if (!$response instanceof ResponseInterface) {
            return new self(
                'Error completing request',
                $request,
                null,
                $previous,
                $handlerContext
            );
        }

        $level = (int)floor($response->getStatusCode() / 100);
        if ($level === 4) {
            $label = 'Client error';
            $className = ClientException::class;
        } elseif ($level === 5) {
            $label = 'Server error';
            $className = ServerException::class;
        } else {
            $label = 'Unsuccessful request';
            $className = self::class;
        }

        // Obfuscate the URI to prevent token leakage
        $uri = static::obfuscateUri($request->getUri());

        $message = sprintf(
            '%s: `%s %s` resulted in a `%s %s` response',
            $label,
            $request->getMethod(),
            $uri->__toString(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        $summary = ($bodySummarizer ?? new BodySummarizer())->summarize($response);

        if ($summary !== null) {
            $message .= ":\n{$summary}\n";
        }

        return new $className($message, $request, $response, $previous, $handlerContext);
    }

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
