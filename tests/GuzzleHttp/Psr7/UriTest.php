<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Uri;
use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;

covers(Uri::class);

it('parses query strings with duplicate keys and encoded values', function (): void {
    $parsed = Uri::parseQueryString('a=1&a=2&empty&name=John+Doe&encoded=a%26b%3Dc');

    expect($parsed)->toMatchArray([
        'a' => ['1', '2'],
        'empty' => null,
        'name' => 'John Doe',
        'encoded' => 'a&b=c',
    ]);
});

it('returns empty array for empty query string', function (): void {
    expect(Uri::parseQueryString(''))->toBe([]);
});

it('obfuscates matching path segments and throws for invalid regex', function (): void {
    $uri = new Uri('https://api.telegram.org/bot123456:SECRET/getMe');

    $obfuscated = Uri::withObfuscatedPathSegment(
        $uri,
        '/bot\d+:[^\/]+/',
        new StringObfuscator('X', 4),
    );

    expect((string)$obfuscated)->toContain('/XXXX/getMe')
        ->and(fn (): Uri => Uri::withObfuscatedPathSegment($uri, '/[/', new StringObfuscator()))
        ->toThrow(UnexpectedValueException::class, 'Failed to replace path segment.');
});

it('obfuscates selected query parameters while preserving other values', function (): void {
    $uri = new Uri('https://example.test/path?token=abc&token=def&x=1&nested=a%26b%3Dc');

    $obfuscated = Uri::withObfuscatedQueryParameter(
        $uri,
        'token',
        new StringObfuscator('Z', 2),
    );

    expect((string)$obfuscated)
        ->toContain('token=ZZ&token=ZZ')
        ->toContain('x=1')
        ->toContain('nested=a%26b%3Dc')
        ->not->toContain('abc')
        ->not->toContain('def');
});

it('returns original uri when obfuscated query parameter is missing', function (): void {
    $uri = new Uri('https://example.test/path?x=1');

    $obfuscated = Uri::withObfuscatedQueryParameter(
        $uri,
        'token',
        new StringObfuscator('Z', 2),
    );

    expect((string)$obfuscated)->toBe((string)$uri);
});

it('returns original uri when query string is empty', function (): void {
    $uri = new Uri('https://example.test/path');

    $obfuscated = Uri::withObfuscatedQueryParameter(
        $uri,
        'token',
        new StringObfuscator('Z', 2),
    );

    expect((string)$obfuscated)->toBe((string)$uri);
});

it('obfuscates query parameters present without explicit value', function (): void {
    $uri = new Uri('https://example.test/path?token&x=1');

    $obfuscated = Uri::withObfuscatedQueryParameter(
        $uri,
        'token',
        new StringObfuscator('Q', 2),
    );

    expect((string)$obfuscated)
        ->toContain('token=QQ')
        ->toContain('x=1');
});

it('obfuscates only password part in user info', function (): void {
    $withPassword = new Uri('https://alice:secret@example.test/path');
    $withoutPassword = new Uri('https://alice@example.test/path');

    $obfuscatedWithPassword = Uri::withObfuscatedUserInfo($withPassword, new StringObfuscator('P', 3));
    $unchangedWithoutPassword = Uri::withObfuscatedUserInfo($withoutPassword, new StringObfuscator('P', 3));

    expect((string)$obfuscatedWithPassword)->toContain('alice:PPP@example.test')
        ->and((string)$unchangedWithoutPassword)->toContain('alice@example.test');
});
