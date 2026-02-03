<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception\ServerException;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\File;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\InvoiceMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\MessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TelegramResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\WebhookInfo;
use PoorPlebs\TelegramBotSdk\TelegramBot\TelegramBotClient;
use PoorPlebs\TelegramBotSdk\Tests\Support\InMemoryCache;
use Psr\Log\NullLogger;

covers(TelegramBotClient::class);

/**
 * @param array<int,Response> $responses
 * @param array<int,array<string,mixed>> $history
 */
function makeClientWithHistory(array $responses, array &$history): TelegramBotClient
{
    $mockHandler = new MockHandler($responses);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($history));

    return new TelegramBotClient(
        cache: new InMemoryCache(),
        token: '123456:SECRET_TOKEN',
        chatId: 42,
        config: [
            'handler' => $handlerStack,
        ],
    );
}

/**
 * @param array<string,mixed> $payload
 */
function telegramResponse(array $payload): Response
{
    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode($payload, JSON_THROW_ON_ERROR),
    );
}

/**
 * @param array<int,Response|Throwable> $queue
 * @param array<int,array<string,mixed>> $history
 */
function makeClientWithHttpErrorsMiddleware(array $queue, array &$history): TelegramBotClient
{
    $mockHandler = new MockHandler($queue);
    $handlerStack = HandlerStack::create($mockHandler);
    $handlerStack->push(Middleware::history($history));

    $reflection = new ReflectionClass(TelegramBotClient::class);
    $httpErrorsMethod = $reflection->getMethod('httpErrors');
    $httpErrorsMethod->setAccessible(true);
    /** @var callable $httpErrors */
    $httpErrors = $httpErrorsMethod->invoke(null);
    $handlerStack->remove('http_errors');
    $handlerStack->unshift($httpErrors, 'http_errors');

    return new TelegramBotClient(
        cache: new InMemoryCache(),
        token: '123456:SECRET_TOKEN',
        chatId: 42,
        config: [
            'handler' => $handlerStack,
        ],
    );
}

it('builds the file download url with token and normalized path', function (): void {
    $client = new TelegramBotClient(
        cache: new InMemoryCache(),
        token: '123456:SECRET_TOKEN',
        chatId: 42,
    );

    expect($client->getFileDownloadUrl('/documents/example.txt'))
        ->toBe('https://api.telegram.org/file/bot123456:SECRET_TOKEN/documents/example.txt');
});

it('parses getUpdates response into typed update models', function (): void {
    $responseBody = json_encode([
        'ok' => true,
        'result' => [[
            'update_id' => 9001,
            'message' => [
                'message_id' => 10,
                'date' => 1_700_000_000,
                'from' => [
                    'id' => 123,
                    'is_bot' => false,
                    'first_name' => 'Petr',
                ],
                'chat' => [
                    'id' => 123,
                    'type' => 'private',
                    'first_name' => 'Petr',
                ],
                'text' => 'Hello from tests',
                'entities' => [
                    [
                        'type' => 'bot_command',
                        'offset' => 0,
                        'length' => 6,
                    ],
                ],
            ],
        ]],
    ], JSON_THROW_ON_ERROR);

    $mockHandler = new MockHandler([new Response(200, ['Content-Type' => 'application/json'], $responseBody)]);

    $client = new TelegramBotClient(
        cache: new InMemoryCache(),
        token: '123456:SECRET_TOKEN',
        chatId: 42,
        config: [
            'handler' => HandlerStack::create($mockHandler),
        ],
    );

    $updates = $client->getUpdates()->wait();

    expect($updates)->toBeArray()->toHaveCount(1)
        ->and($updates[0])->toBeInstanceOf(MessageUpdate::class)
        ->and($updates[0]->message)->toBeInstanceOf(TextMessage::class)
        ->and($updates[0]->message->text)->toBe('Hello from tests')
        ->and($updates[0]->message->entities)->toHaveCount(1);
});

it('throws when answering a failed pre-checkout query without error message', function (): void {
    $client = new TelegramBotClient(
        cache: new InMemoryCache(),
        token: '123456:SECRET_TOKEN',
        chatId: 42,
    );

    expect(fn (): mixed => $client->answerPreCheckoutQuery('pre_checkout_id', false))
        ->toThrow(UnexpectedValueException::class, 'Must provide an error message for failed pre checkout query.');
});

it('sends answerPreCheckoutQuery payload when ok is false and error message is present', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $response = $client->answerPreCheckoutQuery(
        preCheckoutQueryId: 'pre-checkout-id',
        ok: false,
        errorMessage: 'payment provider timeout',
    )->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(TelegramResponse::class)
        ->and($requestPayload)
        ->toHaveKey('pre_checkout_query_id', 'pre-checkout-id')
        ->toHaveKey('ok', false)
        ->toHaveKey('error_message', 'payment provider timeout');
});

it('sends answerCallbackQuery payload with false and zero values preserved', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $client->answerCallbackQuery(
        callbackQueryId: 'callback-id',
        text: null,
        showAlert: false,
        url: null,
        cacheTime: 0,
    )->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($requestPayload)
        ->toHaveKey('callback_query_id', 'callback-id')
        ->toHaveKey('show_alert', false)
        ->toHaveKey('cache_time', 0)
        ->not->toHaveKey('text')
        ->not->toHaveKey('url');
});

it('serializes allowed updates and merges extra options in getUpdates', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $client->getUpdates(
        offset: 10,
        limit: 50,
        timeout: 0,
        allowedUpdates: ['message', 'callback_query'],
        options: [RequestOptions::TIMEOUT => 10.0],
    )->wait();

    parse_str($history[0]['request']->getUri()->getQuery(), $query);

    expect($query)
        ->toMatchArray([
            'offset' => '10',
            'limit' => '50',
            'timeout' => '0',
            'allowed_updates' => '["message","callback_query"]',
        ])
        ->and($history[0]['options'][RequestOptions::TIMEOUT])->toBe(10.0);
});

it('serializes setWebhook payload and keeps false drop_pending_updates', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $response = $client->setWebhook(
        url: 'https://example.com/webhook',
        ipAddress: null,
        maxConnection: 10,
        allowedUpdates: ['message'],
        dropPendingUpdates: false,
        secretToken: 'secret',
    )->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->ok)->toBeTrue()
        ->and($requestPayload)
        ->toHaveKey('url', 'https://example.com/webhook')
        ->toHaveKey('max_connections', 10)
        ->toHaveKey('allowed_updates', '["message"]')
        ->toHaveKey('drop_pending_updates', false)
        ->toHaveKey('secret_token', 'secret')
        ->not->toHaveKey('ip_address');
});

it('sends deleteMessage payload and returns the raw response', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        new Response(200, ['Content-Type' => 'application/json'], '{"ok":true,"result":true}'),
    ], $history);

    $response = $client->deleteMessage(chatId: 12345, messageId: 777)->wait();
    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(Response::class)
        ->and($requestPayload)->toMatchArray([
            'chat_id' => 12345,
            'message_id' => 777,
        ]);
});

it('serializes deleteWebhook payload and preserves false values', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $response = $client->deleteWebhook(false)->wait();
    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($response)->toBeInstanceOf(TelegramResponse::class)
        ->and($requestPayload)->toMatchArray([
            'drop_pending_updates' => false,
        ]);
});

it('parses getMe into telegram response and allows setting logger', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'description' => 'bot is available',
            'result' => [],
        ]),
    ], $history);

    $client->setLogger(new NullLogger());
    $response = $client->getMe()->wait();

    expect($response)->toBeInstanceOf(TelegramResponse::class)
        ->and($response->ok)->toBeTrue()
        ->and($response->description)->toBe('bot is available')
        ->and((string)$history[0]['request']->getUri())->toContain('/getMe');
});

it('uses method arguments as final values for sendMessage and sendAnimation payloads', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [
                'message_id' => 1,
                'date' => 1_700_000_000,
                'from' => [
                    'id' => 123,
                    'is_bot' => false,
                    'first_name' => 'Petr',
                ],
                'chat' => [
                    'id' => 999,
                    'type' => 'private',
                    'first_name' => 'Petr',
                ],
                'text' => 'from-argument',
            ],
        ]),
        new Response(200, ['Content-Type' => 'application/json'], '{"ok":true,"result":[]}'),
    ], $history);

    $sentMessage = $client->sendMessage('from-argument', [
        'chat_id' => 999,
        'text' => 'from-options',
    ])->wait();

    $client->sendAnimation('animation-from-argument', [
        'animation' => 'animation-from-options',
    ])->wait();

    $sendMessagePayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);
    $sendAnimationPayload = json_decode((string)$history[1]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($sentMessage)->toBeInstanceOf(TextMessage::class)
        ->and($sendMessagePayload)
        ->toHaveKey('chat_id', 999)
        ->toHaveKey('text', 'from-argument')
        ->and($sendAnimationPayload)
        ->toHaveKey('chat_id', 42)
        ->toHaveKey('animation', 'animation-from-argument');
});

it('sends invoice payload and parses invoice response into invoice message', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [
                'message_id' => 2,
                'date' => 1_700_000_000,
                'from' => [
                    'id' => 123,
                    'is_bot' => false,
                    'first_name' => 'Petr',
                ],
                'chat' => [
                    'id' => 42,
                    'type' => 'private',
                    'first_name' => 'Petr',
                ],
                'invoice' => [
                    'title' => 'Order #1',
                    'description' => 'Test order',
                    'start_parameter' => 'start',
                    'currency' => 'USD',
                    'total_amount' => 1999,
                ],
            ],
        ]),
    ], $history);

    $message = $client->sendInvoice([
        'chat_id' => 999,
        'title' => 'Order #1',
    ])->wait();

    $requestPayload = json_decode((string)$history[0]['request']->getBody(), true, flags: JSON_THROW_ON_ERROR);

    expect($message)->toBeInstanceOf(InvoiceMessage::class)
        ->and($requestPayload)
        ->toHaveKey('chat_id', 999)
        ->toHaveKey('title', 'Order #1');
});

it('downloads files as streams in sync and async modes', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        new Response(200, [], 'sync-content'),
        new Response(200, [], 'async-content'),
    ], $history);

    $syncStream = $client->downloadFileStream('/documents/sync.txt');
    $asyncStream = $client->downloadFileStreamAsync('documents/async.txt')->wait();

    expect((string)$syncStream)->toBe('sync-content')
        ->and((string)$asyncStream)->toBe('async-content')
        ->and((string)$history[0]['request']->getUri())->toBe('https://api.telegram.org/file/bot123456:SECRET_TOKEN/documents/sync.txt')
        ->and((string)$history[1]['request']->getUri())->toBe('https://api.telegram.org/file/bot123456:SECRET_TOKEN/documents/async.txt')
        ->and($history[0]['options'][RequestOptions::STREAM])->toBeTrue()
        ->and($history[1]['options'][RequestOptions::STREAM])->toBeTrue();
});

it('parses getFile and getWebhookInfo into typed models', function (): void {
    $history = [];

    $client = makeClientWithHistory([
        telegramResponse([
            'ok' => true,
            'result' => [
                'file_id' => 'FILE_ID',
                'file_unique_id' => 'UNIQUE',
                'file_size' => 7,
                'file_path' => 'documents/a.txt',
            ],
        ]),
        telegramResponse([
            'ok' => true,
            'result' => [
                'url' => 'https://example.com/webhook',
                'has_custom_certificate' => false,
                'pending_update_count' => 0,
                'last_error_date' => 1_700_000_000,
            ],
        ]),
    ], $history);

    $file = $client->getFile('FILE_ID')->wait();
    $webhookInfo = $client->getWebhookInfo()->wait();

    parse_str($history[0]['request']->getUri()->getQuery(), $query);

    expect($file)->toBeInstanceOf(File::class)
        ->and($file->filePath)->toBe('documents/a.txt')
        ->and($query)->toHaveKey('file_id', 'FILE_ID')
        ->and($webhookInfo)->toBeInstanceOf(WebhookInfo::class)
        ->and($webhookInfo->setup())->toBeTrue()
        ->and($webhookInfo->hasError())->toBeTrue()
        ->and($webhookInfo->synchronized())->toBeTrue();
});

it('converts http error responses into obfuscated request exceptions', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new Response(
            500,
            ['Content-Type' => 'application/json'],
            '{"ok":false,"description":"Internal server error"}',
        ),
    ], $history);

    try {
        $client->getMe()->wait();

        expect()->fail('Expected a server exception.');
    } catch (ServerException $exception) {
        expect($exception->getMessage())
            ->toContain('/bot**********/getMe')
            ->not->toContain('SECRET_TOKEN');
    }
});

it('keeps successful responses untouched in custom http error middleware', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $response = $client->getMe()->wait();

    expect($response)->toBeInstanceOf(TelegramResponse::class)
        ->and($response->ok)->toBeTrue();
});

it('bypasses http error conversion when request options disable http_errors', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        telegramResponse([
            'ok' => true,
            'result' => [],
        ]),
    ], $history);

    $updates = $client->getUpdates(options: ['http_errors' => false])->wait();

    expect($updates)->toBeArray()->toBeEmpty()
        ->and($history[0]['options']['http_errors'])->toBeFalse();
});

it('obfuscates token in connect exception messages', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new ConnectException(
            'Connection error for https://api.telegram.org/bot123456:SECRET_TOKEN/getMe',
            new Request('GET', 'https://api.telegram.org/bot123456:SECRET_TOKEN/getMe'),
        ),
    ], $history);

    try {
        $client->getMe()->wait();

        expect()->fail('Expected a connect exception.');
    } catch (ConnectException $exception) {
        expect($exception->getMessage())
            ->toContain('/bot**********/getMe')
            ->not->toContain('SECRET_TOKEN');
    }
});

it('rethrows non-connect exceptions from the http error middleware', function (): void {
    $history = [];

    $client = makeClientWithHttpErrorsMiddleware([
        new RuntimeException('unexpected transport failure'),
    ], $history);

    expect(fn (): mixed => $client->getMe()->wait())
        ->toThrow(RuntimeException::class, 'unexpected transport failure');
});
