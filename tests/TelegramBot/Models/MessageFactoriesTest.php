<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedForumTopicTextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedTextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicClosedMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicCreatedMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicEditedMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicReopenedMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicTextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\ForumTopicVoiceMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GeneralForumTopicHiddenMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GeneralForumTopicUnhiddenMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\InvoiceMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\LeftChatMemberMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\Message;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\NewChatMembersMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\SuccessfulPaymentMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TextMessage;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\User;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\VoiceMessage;

covers(
    Message::class,
    EditedMessage::class,
    TextMessage::class,
    VoiceMessage::class,
    ForumTopicTextMessage::class,
    ForumTopicVoiceMessage::class,
    ForumTopicCreatedMessage::class,
    ForumTopicEditedMessage::class,
    ForumTopicClosedMessage::class,
    ForumTopicReopenedMessage::class,
    GeneralForumTopicHiddenMessage::class,
    GeneralForumTopicUnhiddenMessage::class,
    InvoiceMessage::class,
    SuccessfulPaymentMessage::class,
    NewChatMembersMessage::class,
    LeftChatMemberMessage::class,
    EditedTextMessage::class,
    EditedForumTopicTextMessage::class,
    User::class,
);

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function modelUser(array $overrides = []): array
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
function modelChat(array $overrides = []): array
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
function modelMessage(array $overrides = []): array
{
    return array_merge([
        'message_id' => 10,
        'date' => 1_700_000_000,
        'from' => modelUser(),
        'chat' => modelChat(),
    ], $overrides);
}

/**
 * @param array<string,mixed> $overrides
 * @return array<string,mixed>
 */
function modelVoice(array $overrides = []): array
{
    return array_merge([
        'file_id' => 'voice-id',
        'file_unique_id' => 'voice-unique-id',
        'duration' => 4,
    ], $overrides);
}

it('creates the generic message model when no specialized payload exists', function (): void {
    $message = Message::create(modelMessage());

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message)->not->toBeInstanceOf(TextMessage::class)
        ->and($message->messageId)->toBe(10);
});

it('creates text messages and ignores invalid entities entries', function (): void {
    $message = Message::create(modelMessage([
        'text' => 'hello',
        'entities' => [
            'invalid-entry',
            [
                'type' => 'bot_command',
                'offset' => 0,
                'length' => 6,
            ],
        ],
    ]));

    expect($message)->toBeInstanceOf(TextMessage::class)
        ->and($message->text)->toBe('hello')
        ->and($message->entities)->toHaveCount(1)
        ->and($message->entities[0]->length)->toBe(6);
});

it('creates text messages with recursive text replies and non-array entities fallback', function (): void {
    $message = Message::create(modelMessage([
        'text' => 'parent text',
        'entities' => 'not-an-array',
        'reply_to_message' => modelMessage([
            'text' => 'child text',
        ]),
    ]));

    expect($message)->toBeInstanceOf(TextMessage::class)
        ->and($message->entities)->toBeArray()->toBeEmpty()
        ->and($message->replyToMessage)->toBeInstanceOf(TextMessage::class)
        ->and($message->replyToMessage?->text)->toBe('child text');
});

it('creates forum topic text messages with topic-created reply context', function (): void {
    $message = Message::create(modelMessage([
        'text' => 'forum text',
        'is_topic_message' => true,
        'message_thread_id' => 77,
        'reply_to_message' => modelMessage([
            'message_thread_id' => 77,
            'forum_topic_created' => [
                'name' => 'Topic name',
                'icon_color' => 1,
            ],
        ]),
    ]));

    expect($message)->toBeInstanceOf(ForumTopicTextMessage::class)
        ->and($message->messageThreadId)->toBe(77)
        ->and($message->forumTopicCreatedMessage)->toBeInstanceOf(ForumTopicCreatedMessage::class)
        ->and($message->replyToMessage)->toBeNull();
});

it('creates forum topic text messages with regular text replies', function (): void {
    $message = Message::create(modelMessage([
        'text' => 'topic text',
        'is_topic_message' => true,
        'message_thread_id' => 71,
        'entities' => 'invalid-entities',
        'reply_to_message' => modelMessage([
            'text' => 'reply text',
        ]),
    ]));

    expect($message)->toBeInstanceOf(ForumTopicTextMessage::class)
        ->and($message->entities)->toBeArray()->toBeEmpty()
        ->and($message->forumTopicCreatedMessage)->toBeNull()
        ->and($message->replyToMessage)->toBeInstanceOf(TextMessage::class)
        ->and($message->replyToMessage?->text)->toBe('reply text');
});

it('creates voice messages with recursive reply voice payloads', function (): void {
    $message = Message::create(modelMessage([
        'voice' => modelVoice(),
        'reply_to_message' => modelMessage([
            'voice' => modelVoice(['duration' => 9]),
        ]),
    ]));

    expect($message)->toBeInstanceOf(VoiceMessage::class)
        ->and($message->voice->duration)->toBe(4)
        ->and($message->replyToMessage)->toBeInstanceOf(VoiceMessage::class)
        ->and($message->replyToMessage?->voice->duration)->toBe(9);
});

it('creates forum topic voice messages with topic-created reply context', function (): void {
    $message = Message::create(modelMessage([
        'voice' => modelVoice(),
        'is_topic_message' => true,
        'message_thread_id' => 88,
        'reply_to_message' => modelMessage([
            'message_thread_id' => 88,
            'forum_topic_created' => [
                'name' => 'Voice topic',
                'icon_color' => 2,
            ],
        ]),
    ]));

    expect($message)->toBeInstanceOf(ForumTopicVoiceMessage::class)
        ->and($message->messageThreadId)->toBe(88)
        ->and($message->forumTopicCreatedMessage)->toBeInstanceOf(ForumTopicCreatedMessage::class)
        ->and($message->replyToMessage)->toBeNull();
});

it('creates forum topic voice messages with regular voice replies', function (): void {
    $message = Message::create(modelMessage([
        'voice' => modelVoice(),
        'is_topic_message' => true,
        'message_thread_id' => 89,
        'reply_to_message' => modelMessage([
            'voice' => modelVoice(['duration' => 12]),
        ]),
    ]));

    expect($message)->toBeInstanceOf(ForumTopicVoiceMessage::class)
        ->and($message->forumTopicCreatedMessage)->toBeNull()
        ->and($message->replyToMessage)->toBeInstanceOf(VoiceMessage::class)
        ->and($message->replyToMessage?->voice->duration)->toBe(12);
});

it('creates invoice and successful payment messages', function (): void {
    $invoiceMessage = Message::create(modelMessage([
        'invoice' => [
            'title' => 'Order #1',
            'description' => 'Test',
            'start_parameter' => 'start',
            'currency' => 'USD',
            'total_amount' => 499,
        ],
    ]));

    $paymentMessage = Message::create(modelMessage([
        'successful_payment' => [
            'currency' => 'USD',
            'total_amount' => 499,
            'invoice_payload' => 'payload',
            'telegram_payment_charge_id' => 'tg-charge',
            'provider_payment_charge_id' => 'provider-charge',
            'order_info' => [
                'name' => 'Petr',
                'shipping_address' => [
                    'country_code' => 'US',
                    'state' => 'CA',
                    'city' => 'SF',
                    'street_line1' => '1 Market',
                    'street_line2' => 'Suite 10',
                    'post_code' => '94105',
                ],
            ],
        ],
    ]));

    expect($invoiceMessage)->toBeInstanceOf(InvoiceMessage::class)
        ->and($invoiceMessage->invoice->currency)->toBe('USD')
        ->and($paymentMessage)->toBeInstanceOf(SuccessfulPaymentMessage::class)
        ->and($paymentMessage->successfulPayment->orderInfo)->not->toBeNull()
        ->and($paymentMessage->successfulPayment->orderInfo?->shippingAddress?->city)->toBe('SF');
});

it('creates and filters new-chat-members maps by valid user ids', function (): void {
    $message = Message::create(modelMessage([
        'new_chat_members' => [
            modelUser(['id' => 111, 'first_name' => 'A']),
            ['first_name' => 'missing-id'],
            'invalid-member',
            modelUser(['id' => 222, 'first_name' => 'B']),
        ],
    ]));

    expect($message)->toBeInstanceOf(NewChatMembersMessage::class)
        ->and($message->newChatMembers)->toHaveCount(2)
        ->and($message->newChatMembers)->toHaveKeys([111, 222]);
});

it('creates left-chat-member messages', function (): void {
    $message = Message::create(modelMessage([
        'left_chat_member' => modelUser(['id' => 555, 'first_name' => 'Left']),
    ]));

    expect($message)->toBeInstanceOf(LeftChatMemberMessage::class)
        ->and($message->leftChatMember->id)->toBe(555);
});

it('creates forum topic service-message variants', function (): void {
    $variants = [
        'forum_topic_created' => [ForumTopicCreatedMessage::class, ['name' => 'A', 'icon_color' => 1]],
        'forum_topic_edited' => [ForumTopicEditedMessage::class, ['name' => 'B']],
        'forum_topic_closed' => [ForumTopicClosedMessage::class, []],
        'forum_topic_reopened' => [ForumTopicReopenedMessage::class, []],
        'general_forum_topic_hidden' => [GeneralForumTopicHiddenMessage::class, []],
        'general_forum_topic_unhidden' => [GeneralForumTopicUnhiddenMessage::class, []],
    ];

    foreach ($variants as $field => [$expectedClass, $value]) {
        $message = Message::create(modelMessage([
            'message_thread_id' => 901,
            $field => $value,
        ]));

        expect($message)->toBeInstanceOf($expectedClass)
            ->and($message->messageThreadId)->toBe(901);
    }
});

it('creates edited text and edited forum-topic text variants', function (): void {
    $editedText = EditedMessage::create(modelMessage([
        'edit_date' => 1_700_000_100,
        'text' => 'edited text',
        'entities' => 'invalid-entities',
        'reply_to_message' => modelMessage([
            'text' => 'original text',
        ]),
    ]));

    $editedTopicText = EditedMessage::create(modelMessage([
        'edit_date' => 1_700_000_200,
        'text' => 'edited topic text',
        'is_topic_message' => true,
        'message_thread_id' => 700,
        'reply_to_message' => modelMessage([
            'message_thread_id' => 700,
            'forum_topic_created' => [
                'name' => 'Topic',
                'icon_color' => 7,
            ],
        ]),
    ]));

    expect($editedText)->toBeInstanceOf(EditedTextMessage::class)
        ->and($editedText->replyToMessage)->toBeInstanceOf(TextMessage::class)
        ->and($editedText->entities)->toBeArray()->toBeEmpty()
        ->and($editedTopicText)->toBeInstanceOf(EditedForumTopicTextMessage::class)
        ->and($editedTopicText->forumTopicCreatedMessage)->toBeInstanceOf(ForumTopicCreatedMessage::class)
        ->and($editedTopicText->messageThreadId)->toBe(700);
});

it('creates edited generic and edited topic text messages with text reply context', function (): void {
    $editedGeneric = EditedMessage::create(modelMessage([
        'edit_date' => 1_700_000_300,
    ]));

    $editedTopicTextWithReply = EditedMessage::create(modelMessage([
        'edit_date' => 1_700_000_400,
        'text' => 'edited topic',
        'is_topic_message' => true,
        'message_thread_id' => 701,
        'entities' => 'invalid-entities',
        'reply_to_message' => modelMessage([
            'text' => 'topic reply text',
        ]),
    ]));

    expect($editedGeneric)->toBeInstanceOf(EditedMessage::class)
        ->and($editedGeneric)->not->toBeInstanceOf(EditedTextMessage::class)
        ->and($editedTopicTextWithReply)->toBeInstanceOf(EditedForumTopicTextMessage::class)
        ->and($editedTopicTextWithReply->entities)->toBeArray()->toBeEmpty()
        ->and($editedTopicTextWithReply->forumTopicCreatedMessage)->toBeNull()
        ->and($editedTopicTextWithReply->replyToMessage)->toBeInstanceOf(TextMessage::class)
        ->and($editedTopicTextWithReply->replyToMessage?->text)->toBe('topic reply text');
});

it('creates new-chat-members message with non-array payload fallback', function (): void {
    $message = Message::create(modelMessage([
        'new_chat_members' => 'invalid-members-payload',
    ]));

    expect($message)->toBeInstanceOf(NewChatMembersMessage::class)
        ->and($message->newChatMembers)->toBeArray()->toBeEmpty();
});

it('extracts user keys and validates id type', function (): void {
    expect(User::key(['id' => 321]))->toBe(321)
        ->and(fn (): int => User::key(['id' => '321']))
        ->toThrow(InvalidArgumentException::class, 'Type `string` of data field `id` is not an integer.');
});
