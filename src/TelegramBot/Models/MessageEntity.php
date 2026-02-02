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
}
