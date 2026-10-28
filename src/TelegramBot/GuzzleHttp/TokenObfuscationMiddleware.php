<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp;

use Error;
use Exception;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;
use Psr\Http\Message\RequestInterface;
use ReflectionProperty;
use Throwable;

/**
 * Masks the telegram bot token in the messages of failed requests so that
 * sentry, logs and other tools do not leak it. Guzzle builds those messages
 * from the request URI, which carries the token in its path.
 *
 * Messages are rewritten in place along the whole previous chain, which keeps
 * exception classes, responses and any handler context intact on every
 * supported Guzzle major.
 */
final class TokenObfuscationMiddleware
{
    public function __invoke(callable $handler): callable
    {
        return static function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            /** @var PromiseInterface $promise */
            $promise = $handler($request, $options);

            return $promise->then(
                null,
                static fn (mixed $reason): PromiseInterface => Create::rejectionFor(
                    $reason instanceof Throwable ? self::obfuscate($reason) : $reason
                ),
            );
        };
    }

    public static function obfuscate(Throwable $exception): Throwable
    {
        $obfuscator = new TelegramBotTokenObfuscator();

        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            $message = (string)preg_replace_callback(
                TelegramBotTokenObfuscator::TOKEN_REGEX,
                static fn (array $matches): string => $obfuscator($matches[0]),
                $current->getMessage(),
            );

            if ($message !== $current->getMessage()) {
                $property = new ReflectionProperty($current instanceof Exception ? Exception::class : Error::class, 'message');
                $property->setValue($current, $message);
            }
        }

        return $exception;
    }
}
