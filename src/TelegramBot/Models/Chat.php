<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use PoorPlebs\TelegramBotSdk\TelegramBot\Enums\ChatType;

class Chat
{
    public function __construct(
        public readonly int $id,
        public readonly ChatType $type,
        public readonly ?string $title = null,
        public readonly ?string $username = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?bool $isForum = null,
        public readonly ?bool $isDirectMessages = null,
    ) {
    }

    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            id: $update['id'],
            /** @phpstan-ignore-next-line */
            type: ChatType::from($update['type']),
            /** @phpstan-ignore-next-line */
            title: $update['title'] ?? null,
            /** @phpstan-ignore-next-line */
            username: $update['username'] ?? null,
            /** @phpstan-ignore-next-line */
            firstName: $update['first_name'] ?? null,
            /** @phpstan-ignore-next-line */
            lastName: $update['last_name'] ?? null,
            /** @phpstan-ignore-next-line */
            isForum: $update['is_forum'] ?? null,
            /** @phpstan-ignore-next-line */
            isDirectMessages: $update['is_direct_messages'] ?? null,
        );
    }
}
