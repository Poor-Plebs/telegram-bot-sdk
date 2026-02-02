<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class EditedForumTopicTextMessage extends EditedTextMessage
{
    /**
     * @param array<int,MessageEntity> $entities
     */
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        CarbonImmutable $editDate,
        string $text,
        array $entities,
        ?TextMessage $replyToMessage,
        public readonly int $messageThreadId,
        public readonly ?ForumTopicCreatedMessage $forumTopicCreatedMessage = null,
    ) {
        parent::__construct(
            messageId: $messageId,
            date: $date,
            from: $from,
            chat: $chat,
            editDate: $editDate,
            text: $text,
            entities: $entities,
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
        $forumTopicCreatedMessage = null;
        $replyToMessage = null;
        $entitiesData = $data['entities'] ?? [];

        if (is_array($replyToMessageData)) {
            if (array_key_exists('forum_topic_created', $replyToMessageData)) {
                $forumTopicCreatedMessage = ForumTopicCreatedMessage::create($replyToMessageData);
            } elseif (array_key_exists('text', $replyToMessageData)) {
                $replyToMessage = TextMessage::create($replyToMessageData);
            }
        }
        if (!is_array($entitiesData)) {
            $entitiesData = [];
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
            editDate: new CarbonImmutable($data['edit_date']),
            /** @phpstan-ignore-next-line */
            text: $data['text'],
            entities: TextMessage::createEntities($entitiesData),
            replyToMessage: $replyToMessage,
            /** @phpstan-ignore-next-line */
            messageThreadId: $data['message_thread_id'],
            forumTopicCreatedMessage: $forumTopicCreatedMessage,
        );
    }
}
