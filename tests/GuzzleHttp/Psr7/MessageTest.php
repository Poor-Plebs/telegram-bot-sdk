<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Message;

covers(Message::class);

it('keeps payload unchanged when under compression threshold', function (): void {
    $payload = 'short-payload';

    expect(Message::compress($payload, 1024))->toBe($payload);
});

it('compresses payload above threshold', function (): void {
    $payload = str_repeat('a', 20_000);

    $compressed = Message::compress($payload, 10);

    expect($compressed)
        ->not->toBe($payload)
        ->and(strlen($compressed))->toBeLessThan(strlen($payload));
});

it('formats requests and adds host header when missing', function (): void {
    $request = (new Request('GET', 'https://api.telegram.org/getMe', ['X-Test' => '1'], 'body'))
        ->withoutHeader('Host');

    $formatted = Message::toString($request);

    expect($formatted)
        ->toContain('GET /getMe HTTP/1.1')
        ->toContain('Host: api.telegram.org')
        ->toContain('X-Test: 1')
        ->toContain("\r\n\r\nbody");
});

it('formats responses with one set-cookie header line per cookie', function (): void {
    $response = new Response(
        200,
        [
            'Set-Cookie' => ['a=1', 'b=2'],
            'X-Test' => '1',
        ],
        'response-body',
    );

    $formatted = Message::toString($response);

    expect($formatted)
        ->toContain('HTTP/1.1 200 OK')
        ->toContain('Set-Cookie: a=1')
        ->toContain('Set-Cookie: b=2')
        ->toContain('X-Test: 1')
        ->toContain("\r\n\r\nresponse-body");
});

it('does not move seekable stream pointers when stringifying requests and responses', function (): void {
    $requestPayload = 'request-body';
    $responsePayload = 'response-body';

    $requestStream = Utils::streamFor($requestPayload);
    $responseStream = Utils::streamFor($responsePayload);
    $requestStream->seek(3);
    $responseStream->seek(4);

    $request = new Request('POST', 'https://api.telegram.org/sendMessage', [], $requestStream);
    $response = new Response(200, [], $responseStream);

    Message::toString($request);
    Message::toString($response);

    expect($requestStream->tell())->toBe(3)
        ->and($responseStream->tell())->toBe(4)
        ->and($requestStream->getContents())->toBe(substr($requestPayload, 3))
        ->and($responseStream->getContents())->toBe(substr($responsePayload, 4));
});

it('stringifies non-seekable streams', function (): void {
    $request = new Request(
        'POST',
        'https://api.telegram.org/sendMessage',
        [],
        new GuzzleHttp\Psr7\NoSeekStream(Utils::streamFor('body')),
    );

    expect(Message::toString($request))->toContain("\r\n\r\nbody");
});

it('throws for unknown message types', function (): void {
    $message = new class () implements Psr\Http\Message\MessageInterface {
        public function getBody(): Psr\Http\Message\StreamInterface
        {
            return Utils::streamFor('');
        }

        public function getHeader(string $name): array
        {
            return [];
        }

        public function getHeaderLine(string $name): string
        {
            return '';
        }

        public function getHeaders(): array
        {
            return [];
        }

        public function getProtocolVersion(): string
        {
            return '1.1';
        }

        public function hasHeader(string $name): bool
        {
            return false;
        }

        public function withAddedHeader(string $name, $value): Psr\Http\Message\MessageInterface
        {
            return $this;
        }

        public function withBody(Psr\Http\Message\StreamInterface $body): Psr\Http\Message\MessageInterface
        {
            return $this;
        }

        public function withHeader(string $name, $value): Psr\Http\Message\MessageInterface
        {
            return $this;
        }

        public function withoutHeader(string $name): Psr\Http\Message\MessageInterface
        {
            return $this;
        }

        public function withProtocolVersion(string $version): Psr\Http\Message\MessageInterface
        {
            return $this;
        }
    };

    expect(fn (): string => Message::toString($message))
        ->toThrow(InvalidArgumentException::class, 'Unknown message type');
});
