<?php

declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\MessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\TelegramBotClient;
use PoorPlebs\TelegramBotSdk\Tests\Support\InMemoryCache;

covers(TelegramBotClient::class);

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
