<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception\BadResponseException;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception\RequestException;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception\ServerException;

covers(RequestException::class, BadResponseException::class);

final class RequestExceptionFactoryTest extends BadResponseException
{
    public static function obfuscateUriPublic(Psr\Http\Message\UriInterface $uri): Psr\Http\Message\UriInterface
    {
        return parent::obfuscateUri($uri);
    }
}

it('creates generic request exception when response is missing', function (): void {
    $request = new Request('GET', 'https://api.telegram.org/bot123456:SECRET/getMe');

    $exception = RequestException::create($request);

    expect($exception)->toBeInstanceOf(RequestException::class)
        ->and($exception->getMessage())->toBe('Error completing request');
});

it('creates unsuccessful request exception for non-4xx-5xx responses', function (): void {
    $request = new Request('GET', 'https://api.telegram.org/bot123456:SECRET/getMe');
    $response = new Response(302, [], 'redirect');

    $exception = RequestException::create($request, $response);

    expect($exception)->toBeInstanceOf(RequestException::class)
        ->and($exception->getMessage())->toContain('Unsuccessful request')
        ->and($exception->getMessage())->toContain('/bot**********/getMe')
        ->and($exception->getMessage())->not->toContain('SECRET');
});

it('creates server exception for 5xx responses', function (): void {
    $request = new Request('GET', 'https://api.telegram.org/bot123456:SECRET/getMe');
    $response = new Response(500, [], 'error');

    $exception = RequestException::create($request, $response);

    expect($exception)->toBeInstanceOf(ServerException::class)
        ->and($exception->getMessage())->toContain('Server error');
});

it('obfuscates uri in bad response exception helper', function (): void {
    $uri = new GuzzleHttp\Psr7\Uri('https://api.telegram.org/bot123456:SECRET/getMe');

    $obfuscated = RequestExceptionFactoryTest::obfuscateUriPublic($uri);

    expect((string)$obfuscated)
        ->toContain('/bot**********/getMe')
        ->not->toContain('SECRET');
});
