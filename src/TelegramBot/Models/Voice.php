<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

/**
 * Represents a voice message from Telegram.
 *
 * @see https://core.telegram.org/bots/api#voice
 */
class Voice
{
    public function __construct(
        public readonly string $fileId,
        public readonly string $fileUniqueId,
        public readonly int $duration,
        public readonly ?string $mimeType = null,
        public readonly ?int $fileSize = null,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            fileId: $data['file_id'],
            /** @phpstan-ignore-next-line */
            fileUniqueId: $data['file_unique_id'],
            /** @phpstan-ignore-next-line */
            duration: $data['duration'],
            /** @phpstan-ignore-next-line */
            mimeType: $data['mime_type'] ?? null,
            /** @phpstan-ignore-next-line */
            fileSize: $data['file_size'] ?? null,
        );
    }
}
