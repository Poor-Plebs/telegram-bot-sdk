<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class ForumTopicEdited
{
    public function __construct(
        public readonly ?string $name = null,
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
            name: $data['name'] ?? null,
            /** @phpstan-ignore-next-line */
            iconCustomEmojiId: $data['icon_custom_emoji_id'] ?? null,
        );
    }
}
