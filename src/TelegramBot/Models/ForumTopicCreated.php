<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class ForumTopicCreated
{
    public function __construct(
        public readonly string $name,
        public readonly int $iconColor,
        public readonly ?string $iconCustomEmojiId = null,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            name: $data['name'],
            /** @phpstan-ignore-next-line */
            iconColor: $data['icon_color'],
            /** @phpstan-ignore-next-line */
            iconCustomEmojiId: $data['icon_custom_emoji_id'] ?? null,
        );
    }
}
