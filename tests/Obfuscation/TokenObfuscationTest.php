<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\GuzzleObfuscatedFormatter\GuzzleHttp\ObfuscatedMessageFormatter;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\TokenObfuscationMiddleware;

covers(TelegramBotTokenObfuscator::class, TokenObfuscationMiddleware::class);

it('obfuscates telegram bot token strings', function (): void {
    $obfuscator = new TelegramBotTokenObfuscator();

    expect($obfuscator('123456:secret_token'))->toBe('/bot**********');
});

it('obfuscates token and selected body fields in formatted request logs', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{request}'))
        ->setUriParameters([
            TelegramBotTokenObfuscator::TOKEN_REGEX => new TelegramBotTokenObfuscator(),
        ])
        ->setRequestBodyParameters(['chat_id']);

    $request = new Request(
        'POST',
        'https://api.telegram.org/bot123456:SUPER_SECRET/sendMessage',
        ['Content-Type' => 'application/json'],
        json_encode([
            'chat_id' => 123456,
            'text' => 'hello',
        ], JSON_THROW_ON_ERROR),
    );

    $message = $formatter->format($request);

    expect($message)
        ->toContain('/bot**********/sendMessage')
        ->not->toContain('SUPER_SECRET')
        ->not->toContain('"chat_id": 123456');
});

it('obfuscates bot token in request exception messages', function (): void {
    $request = new Request(
        'POST',
        'https://api.telegram.org/bot123456:SUPER_SECRET/sendMessage',
    );

    $response = new Response(
        400,
        ['Content-Type' => 'application/json'],
        '{"ok":false,"description":"Bad Request"}',
    );

    $exception = RequestException::create($request, $response);

    expect(TokenObfuscationMiddleware::obfuscate($exception))->toBe($exception)
        ->and($exception)->toBeInstanceOf(ClientException::class)
        ->and($exception->getResponse())->toBe($response)
        ->and($exception->getMessage())
        ->toContain('/bot**********/sendMessage')
        ->not->toContain('SUPER_SECRET');
});

it('obfuscates bot token along the previous exception chain', function (): void {
    $previous = new Error('Failed to open https://api.telegram.org/bot123456:SUPER_SECRET/getMe');
    $exception = new RuntimeException('Request to /bot123456:SUPER_SECRET/getMe failed', 7, $previous);

    TokenObfuscationMiddleware::obfuscate($exception);

    expect($exception->getMessage())->toBe('Request to /bot**********/getMe failed')
        ->and($exception->getCode())->toBe(7)
        ->and($exception->getPrevious())->toBe($previous)
        ->and($previous->getMessage())->toBe('Failed to open https://api.telegram.org/bot**********/getMe');
});

it('leaves messages without a bot token untouched', function (): void {
    $exception = new RuntimeException('unexpected transport failure');

    expect(TokenObfuscationMiddleware::obfuscate($exception)->getMessage())
        ->toBe('unexpected transport failure');
});
