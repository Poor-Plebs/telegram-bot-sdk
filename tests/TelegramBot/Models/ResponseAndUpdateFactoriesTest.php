<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\CallbackQueryUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedMessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\File;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GenericUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetFileResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetUpdatesResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetWebhookInfoResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\InvoiceMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\MessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\PreCheckoutQueryUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\SendInvoiceResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\SendMessageResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TelegramResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\UpdateFactory;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\WebhookInfo;

covers(
    UpdateFactory::class,
    TelegramResponse::class,
    GetUpdatesResponse::class,
    GetFileResponse::class,
    SendMessageResponse::class,
    SendInvoiceResponse::class,
    GetWebhookInfoResponse::class,
);

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function updateUser(array $overrides = []): array
{
    return array_merge([
        'id' => 100,
        'is_bot' => false,
        'first_name' => 'Petr',
    ], $overrides);
}

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function updateChat(array $overrides = []): array
{
    return array_merge([
        'id' => 100,
        'type' => 'private',
        'first_name' => 'Petr',
    ], $overrides);
}

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function updateMessage(array $overrides = []): array
{
    return array_merge([
        'message_id' => 10,
        'date' => 1_700_000_000,
        'from' => updateUser(),
        'chat' => updateChat(),
    ], $overrides);
}

/**
 * @param array<string,mixed> $payload
 */
function modelResponse(array $payload): Response
{
    return new Response(
        200,
        ['Content-Type' => 'application/json'],
        json_encode($payload, JSON_THROW_ON_ERROR),
    );
}

it('creates all update variants via update factory', function (): void {
    $messageUpdate = UpdateFactory::create([
        'update_id' => 1,
        'message' => updateMessage(['text' => 'message']),
    ]);

    $editedMessageUpdate = UpdateFactory::create([
        'update_id' => 2,
        'edited_message' => updateMessage([
            'edit_date' => 1_700_000_100,
            'text' => 'edited',
        ]),
    ]);

    $callbackQueryUpdate = UpdateFactory::create([
        'update_id' => 3,
        'callback_query' => [
            'id' => 'cbq-id',
            'from' => updateUser(),
            'message' => updateMessage(['text' => 'button']),
            'chat_instance' => 'chat-instance',
            'data' => 'pressed',
        ],
    ]);

    $preCheckoutQueryUpdate = UpdateFactory::create([
        'update_id' => 4,
        'pre_checkout_query' => [
            'id' => 'pre-id',
            'from' => updateUser(),
            'currency' => 'USD',
            'total_amount' => 100,
            'invoice_payload' => 'payload',
        ],
    ]);

    $genericUpdate = UpdateFactory::create([
        'update_id' => 5,
    ]);

    expect($messageUpdate)->toBeInstanceOf(MessageUpdate::class)
        ->and($editedMessageUpdate)->toBeInstanceOf(EditedMessageUpdate::class)
        ->and($callbackQueryUpdate)->toBeInstanceOf(CallbackQueryUpdate::class)
        ->and($preCheckoutQueryUpdate)->toBeInstanceOf(PreCheckoutQueryUpdate::class)
        ->and($genericUpdate)->toBeInstanceOf(GenericUpdate::class)
        ->and($genericUpdate->id)->toBe(5);
});

it('maps mixed update payloads in get updates response', function (): void {
    $response = GetUpdatesResponse::make(modelResponse([
        'ok' => true,
        'result' => [
            [
                'update_id' => 11,
                'message' => updateMessage(['text' => 'msg']),
            ],
            [
                'update_id' => 12,
                'callback_query' => [
                    'id' => 'cbq-id',
                    'from' => updateUser(),
                    'message' => updateMessage(['text' => 'button']),
                    'chat_instance' => 'chat-instance',
                    'data' => 'pressed',
                ],
            ],
            [
                'update_id' => 13,
            ],
        ],
    ]));

    expect($response->result)->toHaveCount(3)
        ->and($response->result[0])->toBeInstanceOf(MessageUpdate::class)
        ->and($response->result[1])->toBeInstanceOf(CallbackQueryUpdate::class)
        ->and($response->result[2])->toBeInstanceOf(GenericUpdate::class)
        ->and($response->parseErrors)->toBeEmpty();
});

it('substitutes a generic update and records the failure when an update fails to parse', function (): void {
    $response = GetUpdatesResponse::make(modelResponse([
        'ok' => true,
        'result' => [
            [
                'update_id' => 21,
                'message' => updateMessage(['text' => 'good']),
            ],
            [
                'update_id' => 22,
                'message' => updateMessage([
                    'text' => 'bad-from',
                    // Malformed `from.id` triggers a TypeError inside User::create
                    // — exercises the per-update try/catch in GetUpdatesResponse::init.
                    'from' => ['id' => 'not-an-int', 'is_bot' => false, 'first_name' => 'X'],
                ]),
            ],
            [
                'update_id' => 23,
                'message' => updateMessage(['text' => 'good-again']),
            ],
        ],
    ]));

    expect($response->result)->toHaveCount(3)
        ->and($response->result[0])->toBeInstanceOf(MessageUpdate::class)
        ->and($response->result[1])->toBeInstanceOf(GenericUpdate::class)
        ->and($response->result[1]->id)->toBe(22)
        ->and($response->result[2])->toBeInstanceOf(MessageUpdate::class)
        ->and($response->parseErrors)->toHaveCount(1)
        ->and($response->parseErrors[0])->toBeInstanceOf(Throwable::class);
});

it('skips updates with no parseable update_id when they fail to parse', function (): void {
    $response = GetUpdatesResponse::make(modelResponse([
        'ok' => true,
        'result' => [
            [
                'update_id' => 31,
                'message' => updateMessage(['text' => 'good']),
            ],
            [
                // update_id present but not an int — we have no integer to
                // advance the offset to, so the update is dropped from the
                // result entirely (but still recorded as a parse error).
                'update_id' => 'not-an-int',
                'message' => updateMessage([
                    'from' => ['id' => 'not-an-int', 'is_bot' => false, 'first_name' => 'X'],
                ]),
            ],
        ],
    ]));

    expect($response->result)->toHaveCount(1)
        ->and($response->result[0])->toBeInstanceOf(MessageUpdate::class)
        ->and($response->result[0]->id)->toBe(31)
        ->and($response->parseErrors)->toHaveCount(1);
});

it('parses telegram response description and handles non-array result payloads', function (): void {
    $withDescription = TelegramResponse::make(modelResponse([
        'ok' => true,
        'description' => 'ok description',
        'result' => [],
    ]));

    $withScalarResult = TelegramResponse::make(modelResponse([
        'ok' => true,
        'description' => 123,
        'result' => true,
    ]));

    expect($withDescription->description)->toBe('ok description')
        ->and($withDescription->result)->toBeArray()
        ->and($withScalarResult->description)->toBeNull()
        ->and($withScalarResult->result)->toBeNull();
});

it('throws for invalid telegram response json', function (): void {
    $response = new Response(200, ['Content-Type' => 'application/json'], '{invalid-json');

    expect(fn (): TelegramResponse => new TelegramResponse($response))
        ->toThrow(JsonException::class);
});

it('parses get-file response into typed file model', function (): void {
    $response = GetFileResponse::make(modelResponse([
        'ok' => true,
        'result' => [
            'file_id' => 'file-id',
            'file_unique_id' => 'file-unique',
            'file_size' => 42,
            'file_path' => 'documents/test.txt',
        ],
    ]));

    expect($response->result)->toBeInstanceOf(File::class)
        ->and($response->result->filePath)->toBe('documents/test.txt');
});

it('parses send-message and send-invoice responses into typed messages', function (): void {
    $sendMessageResponse = SendMessageResponse::make(modelResponse([
        'ok' => true,
        'result' => updateMessage([
            'text' => 'hello',
        ]),
    ]));

    $sendInvoiceResponse = SendInvoiceResponse::make(modelResponse([
        'ok' => true,
        'result' => updateMessage([
            'invoice' => [
                'title' => 'Order #1',
                'description' => 'Test order',
                'start_parameter' => 'start',
                'currency' => 'USD',
                'total_amount' => 1999,
            ],
        ]),
    ]));

    expect($sendMessageResponse->result)->toBeInstanceOf(TextMessage::class)
        ->and($sendMessageResponse->result->text)->toBe('hello')
        ->and($sendInvoiceResponse->result)->toBeInstanceOf(InvoiceMessage::class)
        ->and($sendInvoiceResponse->result->invoice->totalAmount)->toBe(1999);
});

it('parses webhook info timestamps only when values are integers', function (): void {
    $withTimestamps = GetWebhookInfoResponse::make(modelResponse([
        'ok' => true,
        'result' => [
            'url' => 'https://example.com/webhook',
            'has_custom_certificate' => false,
            'pending_update_count' => 0,
            'last_error_date' => 1_700_000_000,
            'last_error_message' => 'network issue',
            'last_synchronization_error_date' => 1_700_000_100,
            'allowed_updates' => ['message'],
        ],
    ]));

    $withoutTimestamps = GetWebhookInfoResponse::make(modelResponse([
        'ok' => true,
        'result' => [
            'url' => '',
            'has_custom_certificate' => false,
            'pending_update_count' => 5,
            'last_error_date' => '1700000000',
            'last_synchronization_error_date' => null,
        ],
    ]));

    expect($withTimestamps->result)->toBeInstanceOf(WebhookInfo::class)
        ->and($withTimestamps->result->lastErrorDate)->not->toBeNull()
        ->and($withTimestamps->result->lastSynchronizationErrorDate)->not->toBeNull()
        ->and($withTimestamps->result->hasError())->toBeTrue()
        ->and($withTimestamps->result->setup())->toBeTrue()
        ->and($withTimestamps->result->synchronized())->toBeTrue()
        ->and($withoutTimestamps->result->lastErrorDate)->toBeNull()
        ->and($withoutTimestamps->result->lastSynchronizationErrorDate)->toBeNull()
        ->and($withoutTimestamps->result->setup())->toBeFalse()
        ->and($withoutTimestamps->result->synchronized())->toBeFalse();
});
