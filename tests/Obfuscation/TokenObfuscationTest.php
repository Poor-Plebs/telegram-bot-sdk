<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\ObfuscatedMessageFormatter;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception\RequestException;

covers(TelegramBotTokenObfuscator::class, ObfuscatedMessageFormatter::class, RequestException::class);

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

it('obfuscates bot token in custom request exception message', function (): void {
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

    expect($exception->getMessage())
        ->toContain('/bot**********/sendMessage')
        ->not->toContain('SUPER_SECRET');
});
