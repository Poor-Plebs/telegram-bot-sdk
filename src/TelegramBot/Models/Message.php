<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class Message
{
    public function __construct(
        public readonly int $messageId,
        public readonly CarbonImmutable $date,
        public readonly User $from,
        public readonly Chat $chat,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        if (array_key_exists('forum_topic_created', $data)) {
            return ForumTopicCreatedMessage::create($data);
        } elseif (array_key_exists('forum_topic_edited', $data)) {
            return ForumTopicEditedMessage::create($data);
        } elseif (array_key_exists('forum_topic_closed', $data)) {
            return ForumTopicClosedMessage::create($data);
        } elseif (array_key_exists('forum_topic_reopened', $data)) {
            return ForumTopicReopenedMessage::create($data);
        } elseif (array_key_exists('general_forum_topic_hidden', $data)) {
            return GeneralForumTopicHiddenMessage::create($data);
        } elseif (array_key_exists('general_forum_topic_unhidden', $data)) {
            return GeneralForumTopicUnhiddenMessage::create($data);
        } elseif (array_key_exists('text', $data) && ($data['is_topic_message'] ?? false) === true) {
            return ForumTopicTextMessage::create($data);
        } elseif (array_key_exists('text', $data)) {
            return TextMessage::create($data);
        } elseif (array_key_exists('voice', $data) && ($data['is_topic_message'] ?? false) === true) {
            return ForumTopicVoiceMessage::create($data);
        } elseif (array_key_exists('voice', $data)) {
            return VoiceMessage::create($data);
        } elseif (array_key_exists('invoice', $data)) {
            return InvoiceMessage::create($data);
        } elseif (array_key_exists('successful_payment', $data)) {
            return SuccessfulPaymentMessage::create($data);
        } elseif (array_key_exists('new_chat_members', $data)) {
            return NewChatMembersMessage::create($data);
        } elseif (array_key_exists('left_chat_member', $data)) {
            return LeftChatMemberMessage::create($data);
        }

        return new self(
            /** @phpstan-ignore-next-line */
            messageId: $data['message_id'],
            /** @phpstan-ignore-next-line */
            date: new CarbonImmutable($data['date']),
            /** @phpstan-ignore-next-line */
            from: User::create($data['from']),
            /** @phpstan-ignore-next-line */
            chat: Chat::create($data['chat']),
        );
    }
}
