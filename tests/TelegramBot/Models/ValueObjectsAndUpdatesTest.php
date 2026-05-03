<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\TelegramBot\Enums\ChatType;
use PoorPlebs\TelegramBotSdk\TelegramBot\Enums\MessageEntityType;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\AbstractUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\CallbackQuery;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\CallbackQueryUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\Chat;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedMessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\File;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicClosed;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicCreated;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicEdited;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicReopened;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GeneralForumTopicHidden;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GeneralForumTopicUnhidden;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\Invoice;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\Message;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\MessageEntity;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\MessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\OrderInfo;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\PreCheckoutQuery;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\PreCheckoutQueryUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ShippingAddress;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\SuccessfulPayment;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\Voice;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\WebhookInfo;

covers(
    AbstractUpdate::class,
    MessageUpdate::class,
    EditedMessageUpdate::class,
    CallbackQueryUpdate::class,
    PreCheckoutQueryUpdate::class,
    CallbackQuery::class,
    PreCheckoutQuery::class,
    MessageEntity::class,
    Chat::class,
    File::class,
    Voice::class,
    Invoice::class,
    ShippingAddress::class,
    OrderInfo::class,
    SuccessfulPayment::class,
    ForumTopicCreated::class,
    ForumTopicEdited::class,
    ForumTopicClosed::class,
    ForumTopicReopened::class,
    GeneralForumTopicHidden::class,
    GeneralForumTopicUnhidden::class,
    WebhookInfo::class,
);

final class ValueObjectsAndUpdatesTest extends AbstractUpdate
{
}

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function voUser(array $overrides = []): array
{
    return array_merge([
        'id' => 200,
        'is_bot' => false,
        'first_name' => 'Petr',
    ], $overrides);
}

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function voChat(array $overrides = []): array
{
    return array_merge([
        'id' => 300,
        'type' => 'private',
        'first_name' => 'Petr',
    ], $overrides);
}

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function voMessage(array $overrides = []): array
{
    return array_merge([
        'message_id' => 20,
        'date' => 1_700_000_000,
        'from' => voUser(),
        'chat' => voChat(),
    ], $overrides);
}

it('creates concrete updates from abstract base', function (): void {
    $update = new ValueObjectsAndUpdatesTest(123);

    expect($update)->toBeInstanceOf(AbstractUpdate::class)
        ->and($update->id)->toBe(123);
});

it('creates chat and message entity models', function (): void {
    $chat = Chat::create([
        'id' => 300,
        'type' => 'supergroup',
        'title' => 'SDK Group',
        'username' => 'sdk_group',
        'first_name' => 'SDK',
        'last_name' => 'Team',
        'is_forum' => true,
        'is_direct_messages' => false,
    ]);

    $entity = MessageEntity::create([
        'type' => 'bold',
        'offset' => 1,
        'length' => 4,
    ]);

    expect($chat->type)->toBe(ChatType::SUPERGROUP)
        ->and($chat->isForum)->toBeTrue()
        ->and($chat->isDirectMessages)->toBeFalse()
        ->and($entity->type)->toBe(MessageEntityType::BOLD)
        ->and($entity->offset)->toBe(1);
});

it('creates a message entity for every documented telegram bot api type', function (string $telegramType, MessageEntityType $expected): void {
    $entity = MessageEntity::create([
        'type' => $telegramType,
        'offset' => 0,
        'length' => 5,
    ]);

    expect($entity->type)->toBe($expected);
})->with([
    'mention' => ['mention', MessageEntityType::MENTION],
    'hashtag' => ['hashtag', MessageEntityType::HASHTAG],
    'cashtag' => ['cashtag', MessageEntityType::CASHTAG],
    'bot_command' => ['bot_command', MessageEntityType::BOT_COMMAND],
    'url' => ['url', MessageEntityType::URL],
    'email' => ['email', MessageEntityType::EMAIL],
    'phone_number' => ['phone_number', MessageEntityType::PHONE_NUMBER],
    'bold' => ['bold', MessageEntityType::BOLD],
    'italic' => ['italic', MessageEntityType::ITALIC],
    'underline' => ['underline', MessageEntityType::UNDERLINE],
    'strikethrough' => ['strikethrough', MessageEntityType::STRIKETHROUGH],
    'spoiler' => ['spoiler', MessageEntityType::SPOILER],
    'blockquote' => ['blockquote', MessageEntityType::BLOCKQUOTE],
    'expandable_blockquote' => ['expandable_blockquote', MessageEntityType::EXPANDABLE_BLOCKQUOTE],
    'code' => ['code', MessageEntityType::CODE],
    'pre' => ['pre', MessageEntityType::PRE],
    'text_link' => ['text_link', MessageEntityType::TEXT_LINK],
    'text_mention' => ['text_mention', MessageEntityType::TEXT_MENTION],
    'custom_emoji' => ['custom_emoji', MessageEntityType::CUSTOM_EMOJI],
]);

it('returns null from tryCreate for entity types the sdk does not know yet', function (): void {
    MessageEntity::flushUnknownTypeAccumulator();

    $entity = MessageEntity::tryCreate([
        'type' => 'some_future_telegram_type',
        'offset' => 0,
        'length' => 1,
    ]);

    expect($entity)->toBeNull();
});

it('records unknown entity types into the accumulator and drains them on flush', function (): void {
    // Discard anything left from prior tests so we observe only this batch.
    MessageEntity::flushUnknownTypeAccumulator();

    MessageEntity::tryCreate(['type' => 'some_future_type', 'offset' => 0, 'length' => 1]);
    MessageEntity::tryCreate(['type' => 'another_future_type', 'offset' => 5, 'length' => 2]);
    MessageEntity::tryCreate(['type' => 'bold', 'offset' => 10, 'length' => 3]);
    // Empty / non-string types are not recorded — accumulator only carries
    // actionable type names.
    MessageEntity::tryCreate(['type' => '', 'offset' => 0, 'length' => 1]);
    MessageEntity::tryCreate(['offset' => 0, 'length' => 1]);

    $first = MessageEntity::flushUnknownTypeAccumulator();

    // Drain again to prove the accumulator empties on flush.
    $second = MessageEntity::flushUnknownTypeAccumulator();

    expect($first)->toBe(['some_future_type', 'another_future_type'])
        ->and($second)->toBe([]);
});

it('creates file, voice, and invoice models', function (): void {
    $file = File::create([
        'file_id' => 'file-id',
        'file_unique_id' => 'file-unique-id',
        'file_size' => 123,
        'file_path' => 'docs/file.txt',
    ]);

    $voice = Voice::create([
        'file_id' => 'voice-id',
        'file_unique_id' => 'voice-unique',
        'duration' => 10,
        'mime_type' => 'audio/ogg',
        'file_size' => 456,
    ]);

    $invoice = Invoice::create([
        'title' => 'Order #2',
        'description' => 'Sample order',
        'start_parameter' => 'start',
        'currency' => 'EUR',
        'total_amount' => 2500,
    ]);

    expect($file->filePath)->toBe('docs/file.txt')
        ->and($voice->mimeType)->toBe('audio/ogg')
        ->and($voice->fileSize)->toBe(456)
        ->and($invoice->currency)->toBe('EUR');
});

it('creates shipping and order info with and without address', function (): void {
    $shippingAddress = ShippingAddress::create([
        'country_code' => 'US',
        'state' => 'CA',
        'city' => 'San Francisco',
        'street_line1' => '1 Market',
        'street_line2' => 'Suite 10',
        'post_code' => '94105',
    ]);

    $withAddress = OrderInfo::create([
        'name' => 'Petr',
        'phone_number' => '+123456789',
        'email' => 'petr@example.test',
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'CA',
            'city' => 'San Francisco',
            'street_line1' => '1 Market',
            'street_line2' => 'Suite 10',
            'post_code' => '94105',
        ],
    ]);

    $withoutAddress = OrderInfo::create([]);

    expect($shippingAddress->city)->toBe('San Francisco')
        ->and($withAddress->shippingAddress)->toBeInstanceOf(ShippingAddress::class)
        ->and($withoutAddress->shippingAddress)->toBeNull();
});

it('creates successful payment with and without optional fields', function (): void {
    $withOrderInfo = SuccessfulPayment::create([
        'currency' => 'USD',
        'total_amount' => 500,
        'invoice_payload' => 'payload',
        'telegram_payment_charge_id' => 'tg-charge',
        'provider_payment_charge_id' => 'provider-charge',
        'shipping_option_id' => 'delivery',
        'order_info' => [
            'name' => 'Petr',
        ],
    ]);

    $withoutOptionals = SuccessfulPayment::create([
        'currency' => 'USD',
        'total_amount' => 500,
        'invoice_payload' => 'payload',
        'telegram_payment_charge_id' => 'tg-charge',
        'provider_payment_charge_id' => 'provider-charge',
    ]);

    expect($withOrderInfo->shippingOptionId)->toBe('delivery')
        ->and($withOrderInfo->orderInfo)->toBeInstanceOf(OrderInfo::class)
        ->and($withoutOptionals->shippingOptionId)->toBeNull()
        ->and($withoutOptionals->orderInfo)->toBeNull();
});

it('creates forum topic value objects', function (): void {
    $created = ForumTopicCreated::create([
        'name' => 'Announcements',
        'icon_color' => 1,
        'icon_custom_emoji_id' => 'emoji-id',
    ]);

    $edited = ForumTopicEdited::create([
        'name' => 'Updated announcements',
        'icon_custom_emoji_id' => 'emoji-new',
    ]);

    $closed = ForumTopicClosed::create([]);
    $reopened = ForumTopicReopened::create([]);
    $hidden = GeneralForumTopicHidden::create([]);
    $unhidden = GeneralForumTopicUnhidden::create([]);

    expect($created->iconCustomEmojiId)->toBe('emoji-id')
        ->and($edited->name)->toBe('Updated announcements')
        ->and($closed)->toBeInstanceOf(ForumTopicClosed::class)
        ->and($reopened)->toBeInstanceOf(ForumTopicReopened::class)
        ->and($hidden)->toBeInstanceOf(GeneralForumTopicHidden::class)
        ->and($unhidden)->toBeInstanceOf(GeneralForumTopicUnhidden::class);
});

it('creates callback and pre-checkout query models', function (): void {
    $callbackQuery = CallbackQuery::create([
        'id' => 'cbq-id',
        'from' => voUser(),
        'message' => voMessage(['text' => 'click']),
        'chat_instance' => 'chat-instance',
        'data' => 'action',
    ]);

    $preCheckoutQuery = PreCheckoutQuery::create([
        'id' => 'pre-id',
        'from' => voUser(),
        'currency' => 'USD',
        'total_amount' => 500,
        'invoice_payload' => 'payload',
    ]);

    expect($callbackQuery->chatInstance)->toBe('chat-instance')
        ->and($callbackQuery->message)->toBeInstanceOf(Message::class)
        ->and($preCheckoutQuery->totalAmount)->toBe(500);
});

it('creates explicit update models through static create methods', function (): void {
    $messageUpdate = MessageUpdate::create([
        'update_id' => 11,
        'message' => voMessage(['text' => 'message']),
    ]);

    $editedMessageUpdate = EditedMessageUpdate::create([
        'update_id' => 12,
        'edited_message' => voMessage([
            'edit_date' => 1_700_000_100,
        ]),
    ]);

    $callbackUpdate = CallbackQueryUpdate::create([
        'update_id' => 13,
        'callback_query' => [
            'id' => 'cbq-id',
            'from' => voUser(),
            'message' => voMessage(['text' => 'button']),
            'chat_instance' => 'chat-instance',
            'data' => 'pressed',
        ],
    ]);

    $preCheckoutUpdate = PreCheckoutQueryUpdate::create([
        'update_id' => 14,
        'pre_checkout_query' => [
            'id' => 'pre-id',
            'from' => voUser(),
            'currency' => 'USD',
            'total_amount' => 100,
            'invoice_payload' => 'payload',
        ],
    ]);

    expect($messageUpdate->id)->toBe(11)
        ->and($messageUpdate->message)->toBeInstanceOf(Message::class)
        ->and($editedMessageUpdate->message)->toBeInstanceOf(EditedMessage::class)
        ->and($callbackUpdate->callbackQuery)->toBeInstanceOf(CallbackQuery::class)
        ->and($preCheckoutUpdate->preCheckoutQuery)->toBeInstanceOf(PreCheckoutQuery::class);
});

it('evaluates webhook info state helpers', function (): void {
    $withError = new WebhookInfo(
        url: 'https://example.test/webhook',
        hasCustomCertificate: false,
        pendingUpdateCount: 3,
        ipAddress: null,
        lastErrorDate: new Carbon\CarbonImmutable('@1700000000'),
        lastErrorMessage: 'network',
        lastSynchronizationErrorDate: null,
        maxConnections: 40,
        allowedUpdates: ['message'],
    );

    $healthy = new WebhookInfo(
        url: '',
        hasCustomCertificate: false,
        pendingUpdateCount: 0,
        ipAddress: null,
        lastErrorDate: null,
        lastErrorMessage: null,
        lastSynchronizationErrorDate: null,
        maxConnections: null,
        allowedUpdates: null,
    );

    expect($withError->setup())->toBeTrue()
        ->and($withError->hasError())->toBeTrue()
        ->and($withError->synchronized())->toBeFalse()
        ->and($healthy->setup())->toBeFalse()
        ->and($healthy->hasError())->toBeFalse()
        ->and($healthy->synchronized())->toBeTrue();
});
