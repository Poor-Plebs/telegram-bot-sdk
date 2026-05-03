<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use PoorPlebs\TelegramBotSdk\TelegramBot\Enums\MessageEntityType;

class MessageEntity
{
    public function __construct(
        public readonly MessageEntityType $type,
        public readonly int $offset,
        public readonly int $length,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            type: MessageEntityType::from($data['type']),
            /** @phpstan-ignore-next-line */
            offset: $data['offset'],
            /** @phpstan-ignore-next-line */
            length: $data['length'],
        );
    }

    /**
     * Tolerant factory: returns null when Telegram emits an entity type the SDK
     * does not yet recognize (additive Bot API change), so callers can drop the
     * unknown annotation without crashing the surrounding message parse.
     *
     * @param array<string,mixed> $data
     */
    public static function tryCreate(array $data): ?self
    {
        /** @phpstan-ignore-next-line */
        $type = MessageEntityType::tryFrom($data['type'] ?? '');

        if ($type === null) {
            return null;
        }

        return new self(
            type: $type,
            /** @phpstan-ignore-next-line */
            offset: $data['offset'],
            /** @phpstan-ignore-next-line */
            length: $data['length'],
        );
    }
}
