<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

/**
 * Voice message from a forum topic.
 */
class ForumTopicVoiceMessage extends VoiceMessage
{
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        Voice $voice,
        public readonly int $messageThreadId,
        ?string $caption = null,
        ?VoiceMessage $replyToMessage = null,
        public readonly ?ForumTopicCreatedMessage $forumTopicCreatedMessage = null,
    ) {
        parent::__construct(
            messageId: $messageId,
            date: $date,
            from: $from,
            chat: $chat,
            voice: $voice,
            caption: $caption,
            replyToMessage: $replyToMessage,
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        /** @var array<string,mixed>|null $replyToMessageData */
        $replyToMessageData = $data['reply_to_message'] ?? null;
        $replyToMessage = null;
        $forumTopicCreatedMessage = null;

        if (is_array($replyToMessageData)) {
            // Check if the reply_to_message contains a forum_topic_created service message
            if (array_key_exists('forum_topic_created', $replyToMessageData)) {
                $forumTopicCreatedMessage = ForumTopicCreatedMessage::create($replyToMessageData);
            } elseif (array_key_exists('voice', $replyToMessageData)) {
                $replyToMessage = VoiceMessage::create($replyToMessageData);
            }
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
            /** @phpstan-ignore-next-line */
            voice: Voice::create($data['voice']),
            /** @phpstan-ignore-next-line */
            messageThreadId: $data['message_thread_id'],
            /** @phpstan-ignore-next-line */
            caption: $data['caption'] ?? null,
            replyToMessage: $replyToMessage,
            forumTopicCreatedMessage: $forumTopicCreatedMessage,
        );
    }
}
