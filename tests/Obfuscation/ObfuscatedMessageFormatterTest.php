<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\ObfuscatedMessageFormatter;
use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;

covers(ObfuscatedMessageFormatter::class);

it('obfuscates uri, query, headers, and json body fields across request and response', function (): void {
    $formatter = (new ObfuscatedMessageFormatter("{uri}\n{request}\n---\n{response}"))
        ->setUriParameters([
            TelegramBotTokenObfuscator::TOKEN_REGEX => new TelegramBotTokenObfuscator(),
        ])
        ->setQueryParameters(['token'])
        ->setRequestHeaders(['Authorization'])
        ->setResponseHeaders(['Set-Cookie'])
        ->setRequestBodyParameters(['secret'])
        ->setResponseBodyParameters(['secret']);

    $request = new Request(
        'POST',
        'https://alice:password@api.telegram.org/bot123456:SUPER_SECRET/sendMessage?token=q1&x=1&token=q2',
        [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer sensitive-token',
        ],
        json_encode([
            'secret' => 'request-secret',
            'payload' => ['secret' => 'nested-request-secret'],
            'text' => 'hello',
        ], JSON_THROW_ON_ERROR),
    );

    $response = new Response(
        200,
        [
            'Content-Type' => 'application/json',
            'Set-Cookie' => ['session=session-secret', 'refresh=refresh-secret'],
        ],
        json_encode([
            'ok' => true,
            'secret' => 'response-secret',
        ], JSON_THROW_ON_ERROR),
    );

    $formatted = $formatter->format($request, $response);

    expect($formatted)
        ->toContain('/bot**********/sendMessage')
        ->toContain('alice:**********@api.telegram.org')
        ->toContain('token=**********')
        ->toContain('x=1')
        ->toContain('Authorization: **********')
        ->toContain('Set-Cookie: **********')
        ->toContain('"secret": "**********"')
        ->not->toContain('SUPER_SECRET')
        ->not->toContain('sensitive-token')
        ->not->toContain('session-secret')
        ->not->toContain('response-secret');
});

it('does not try to obfuscate non-json request bodies', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{req_body}'))
        ->setRequestBodyParameters(['secret']);

    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage',
        ['Content-Type' => 'application/x-www-form-urlencoded'],
        'secret=raw-secret&x=1',
    );

    expect($formatter->format($request))->toContain('secret=raw-secret');
});

it('throws when json request body is invalid and body obfuscation is enabled', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{request}'))
        ->setRequestBodyParameters(['secret']);

    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage',
        ['Content-Type' => 'application/json'],
        '{invalid-json',
    );

    expect(fn (): string => $formatter->format($request))
        ->toThrow(JsonException::class);
});

it('throws when json response body is invalid and response obfuscation is enabled', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{response}'))
        ->setResponseBodyParameters(['secret']);

    $request = new Request('GET', 'https://api.telegram.org/getMe');
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        '{invalid-json',
    );

    expect(fn (): string => $formatter->format($request, $response))
        ->toThrow(JsonException::class);
});

it('hides non-loggable response body and emits sentinel in res_body placeholder', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{response}|{res_body}');

    $request = new Request('GET', 'https://api.telegram.org/getMe');
    $response = new Response(
        200,
        ['Content-Type' => 'application/octet-stream', 'X-Test' => '1'],
        'BINARY-SECRET',
    );

    $formatted = $formatter->format($request, $response);

    expect($formatted)
        ->toContain('HTTP/1.1 200 OK')
        ->toContain('X-Test: 1')
        ->toContain('RESPONSE_NOT_LOGGEABLE')
        ->not->toContain('BINARY-SECRET');
});

it('logs response headers without body for non-loggable content and keeps set-cookie lines separate', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{response}');

    $request = new Request('GET', 'https://api.telegram.org/getMe');
    $response = new Response(
        200,
        [
            'Content-Type' => 'application/octet-stream',
            'Set-Cookie' => ['a=1', 'b=2'],
        ],
        'binary-body',
    );

    $formatted = $formatter->format($request, $response);

    expect($formatted)
        ->toContain('Set-Cookie: a=1')
        ->toContain('Set-Cookie: b=2')
        ->not->toContain('binary-body');
});

it('returns sentinel for unseekable but loggable response bodies', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{res_body}');

    $request = new Request('GET', 'https://api.telegram.org/getMe');
    $response = new Response(
        200,
        ['Content-Type' => 'text/plain'],
        new NoSeekStream(Utils::streamFor('plain-text-body')),
    );

    expect($formatter->format($request, $response))->toBe('RESPONSE_NOT_LOGGEABLE');
});

it('logs response body when content-type header is missing', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{res_body}');

    $request = new Request('GET', 'https://api.telegram.org/getMe');
    $response = new Response(200, [], 'body-without-content-type');

    expect($formatter->format($request, $response))->toBe('body-without-content-type');
});

it('supports dynamic headers and null placeholders when response is missing', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{req_header_X-Test}|{res_header_Content-Type}|{code}|{phrase}|{error}|{unknown}|{res_headers}');

    $request = new Request('GET', 'https://api.telegram.org/getMe', ['X-Test' => 'abc']);
    $error = new RuntimeException('transport failed');

    expect($formatter->format($request, null, $error))
        ->toBe('abc|NULL|NULL|NULL|transport failed||NULL');
});

it('formats scalar placeholders and memoizes repeated placeholder lookups', function (): void {
    $formatter = new ObfuscatedMessageFormatter(
        '{method}|{version}|{target}|{req_version}|{uri}|{url}|{host}|{code}|{phrase}|{res_body}|{response}|{ts}|{date_iso_8601}|{date_common_log}|{ts}',
    );

    $request = new Request('GET', 'https://api.telegram.org/getMe?x=1');
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode(['ok' => true], JSON_THROW_ON_ERROR),
    );

    $formatted = $formatter->format($request, $response);
    $parts = explode('|', $formatted);

    expect($parts[0])->toBe('GET')
        ->and($parts[1])->toBe('1.1')
        ->and($parts[2])->toBe('/getMe?x=1')
        ->and($parts[3])->toBe('1.1')
        ->and($parts[4])->toContain('https://api.telegram.org/getMe?x=1')
        ->and($parts[5])->toContain('https://api.telegram.org/getMe?x=1')
        ->and($parts[6])->toBe('api.telegram.org')
        ->and($parts[7])->toBe('200')
        ->and($parts[8])->toBe('OK')
        ->and($parts[9])->toContain('"ok":true')
        ->and($parts[10])->toContain('HTTP/1.1 200 OK')
        ->and($parts[11])->toBe($parts[14]);
});

it('formats request and response header placeholders including protocol metadata', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{req_headers}|{res_headers}|{res_version}|{hostname}');

    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage?x=1',
        ['X-Req' => 'abc'],
        'body',
    );
    $response = new Response(
        201,
        ['X-Res' => 'def'],
        'created',
    );

    $formatted = $formatter->format($request, $response);
    $parts = explode('|', $formatted);

    expect($parts[0])->toContain('POST /sendMessage?x=1 HTTP/1.1')
        ->and($parts[0])->toContain('X-Req: abc')
        ->and($parts[1])->toContain('HTTP/1.1 201 Created')
        ->and($parts[1])->toContain('X-Res: def')
        ->and($parts[2])->toBe('1.1')
        ->and($parts[3])->not->toBe('');
});

it('returns empty response placeholder and null res_body when response is missing', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{response}|{res_body}');
    $request = new Request('GET', 'https://api.telegram.org/getMe');

    expect($formatter->format($request))->toBe('|NULL');
});

it('renders null response version when response is missing', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{res_version}');
    $request = new Request('GET', 'https://api.telegram.org/getMe');

    expect($formatter->format($request))->toBe('NULL');
});

it('validates parameter definitions and accepts stringable numeric values', function (): void {
    $formatter = new ObfuscatedMessageFormatter('{uri}');

    expect(fn (): ObfuscatedMessageFormatter => $formatter->setQueryParameters(['token' => 'invalid-obfuscator']))
        ->toThrow(UnexpectedValueException::class, 'Assoc array value must be instance')
        ->and(fn (): ObfuscatedMessageFormatter => $formatter->setQueryParameters([new StringObfuscator()]))
        ->toThrow(UnexpectedValueException::class, 'Numeric array value must be a string or stringifyable');

    $stringableParameter = new class () implements Stringable {
        public function __toString(): string
        {
            return 'token';
        }
    };

    $formatted = (new ObfuscatedMessageFormatter('{uri}'))
        ->setQueryParameters([$stringableParameter])
        ->format(new Request('GET', 'https://api.telegram.org/getMe?token=secret&x=1'));

    expect($formatted)
        ->toContain('token=**********')
        ->toContain('x=1')
        ->not->toContain('token=secret');
});

it('supports custom obfuscators in associative parameter maps', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{req_header_Authorization}|{res_header_X-Token}'))
        ->setRequestHeaders([
            'Authorization' => new StringObfuscator('X', 3),
        ])
        ->setResponseHeaders([
            'X-Token' => new StringObfuscator('Y', 2),
        ]);

    $request = new Request('GET', 'https://api.telegram.org/getMe', ['Authorization' => 'Bearer abc']);
    $response = new Response(200, ['X-Token' => 'response-token']);

    expect($formatter->format($request, $response))->toBe('XXX|YY');
});

it('obfuscates json bodies while ignoring numeric array keys during traversal', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{req_body}|{res_body}'))
        ->setRequestBodyParameters(['secret'])
        ->setResponseBodyParameters(['secret']);

    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage',
        ['Content-Type' => 'application/json'],
        json_encode([
            'secret' => 'request-secret',
            'list' => ['a', 'b'],
        ], JSON_THROW_ON_ERROR),
    );

    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode([
            'secret' => 'response-secret',
            'items' => ['x', 'y'],
        ], JSON_THROW_ON_ERROR),
    );

    $formatted = $formatter->format($request, $response);

    expect($formatted)
        ->toContain('"secret": "**********"')
        ->toContain('"list": [')
        ->toContain('"items": [')
        ->not->toContain('request-secret')
        ->not->toContain('response-secret');
});

it('leaves non-json response body untouched when response body obfuscation is configured', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{response}'))
        ->setResponseBodyParameters(['secret']);

    $request = new Request('GET', 'https://api.telegram.org/getMe');
    $response = new Response(
        200,
        ['Content-Type' => 'application/xml'],
        '<secret>visible</secret>',
    );

    $formatted = $formatter->format($request, $response);

    expect($formatted)->not->toContain('**********');
});

it('throws when uri obfuscation regex is invalid', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{uri}'))
        ->setUriParameters([
            '/[/' => new StringObfuscator(),
        ]);

    $request = new Request('GET', 'https://api.telegram.org/bot123:token/getMe');

    expect(fn (): string => $formatter->format($request))
        ->toThrow(UnexpectedValueException::class, 'Failed to replace path segment.');
});

it('does not move seekable request and response stream pointers during formatting', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{request}|{response}|{req_body}|{res_body}'))
        ->setRequestBodyParameters(['secret'])
        ->setResponseBodyParameters(['secret']);

    $requestPayload = json_encode([
        'secret' => 'request-secret',
        'text' => 'hello',
    ], JSON_THROW_ON_ERROR);
    $responsePayload = json_encode([
        'secret' => 'response-secret',
        'ok' => true,
    ], JSON_THROW_ON_ERROR);

    $requestBody = Utils::streamFor($requestPayload);
    $responseBody = Utils::streamFor($responsePayload);
    $requestBody->seek(5);
    $responseBody->seek(7);

    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage',
        ['Content-Type' => 'application/json'],
        $requestBody,
    );
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        $responseBody,
    );

    $formatter->format($request, $response);

    expect($requestBody->tell())->toBe(5)
        ->and($responseBody->tell())->toBe(7)
        ->and($requestBody->getContents())->toBe(substr($requestPayload, 5))
        ->and($responseBody->getContents())->toBe(substr($responsePayload, 7));
});

it('supports non-seekable streams in req-body and json obfuscation paths', function (): void {
    $formatter = (new ObfuscatedMessageFormatter('{req_body}|{request}|{response}'))
        ->setRequestBodyParameters(['secret'])
        ->setResponseBodyParameters(['secret']);

    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage',
        ['Content-Type' => 'application/json'],
        new NoSeekStream(Utils::streamFor(json_encode([
            'secret' => 'request-secret',
            'text' => 'hello',
        ], JSON_THROW_ON_ERROR))),
    );
    $response = new Response(
        200,
        ['Content-Type' => 'application/json'],
        new NoSeekStream(Utils::streamFor(json_encode([
            'secret' => 'response-secret',
            'ok' => true,
        ], JSON_THROW_ON_ERROR))),
    );

    $formatted = $formatter->format($request, $response);

    expect($formatted)
        ->toContain('"secret": "**********"')
        ->not->toContain('request-secret')
        ->not->toContain('response-secret');
});
