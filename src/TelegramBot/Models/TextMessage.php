<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class TextMessage extends Message
{
    /**
     * @param array<int,MessageEntity> $entities
     */
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        public readonly string $text,
        public readonly array $entities,
        public readonly ?self $replyToMessage = null,
    ) {
        parent::__construct(
            messageId: $messageId,
            date: $date,
            from: $from,
            chat: $chat,
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
            $replyToMessage = self::create($replyToMessageData);
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
            text: $data['text'],
            entities: self::createEntities($entitiesData),
            replyToMessage: $replyToMessage,
        );
    }

    /**
     * @param array<mixed> $entitiesData
     * @return array<int,MessageEntity>
     */
    public static function createEntities(array $entitiesData): array
    {
        $entities = [];

        foreach ($entitiesData as $entityData) {
            if (!is_array($entityData)) {
                continue;
            }

            /** @var array<string,mixed> $entityData */
            $entities[] = MessageEntity::create($entityData);
        }

        return $entities;
    }
}
