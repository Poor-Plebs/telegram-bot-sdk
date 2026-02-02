<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class EditedTextMessage extends EditedMessage
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
        public readonly string $text,
        public readonly array $entities,
        public readonly ?TextMessage $replyToMessage = null,
    ) {
        parent::__construct(
            messageId: $messageId,
            date: $date,
            from: $from,
            chat: $chat,
            editDate: $editDate,
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
        $entitiesData = $data['entities'] ?? [];

        if (is_array($replyToMessageData) && array_key_exists('text', $replyToMessageData)) {
            $replyToMessage = TextMessage::create($replyToMessageData);
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
        );
    }
}
