<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

/**
 * Represents a file ready to be downloaded from Telegram.
 *
 * @see https://core.telegram.org/bots/api#file
 */
class File
{
    public function __construct(
        public readonly string $fileId,
        public readonly string $fileUniqueId,
        public readonly ?int $fileSize = null,
        public readonly ?string $filePath = null,
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
            fileSize: $data['file_size'] ?? null,
            /** @phpstan-ignore-next-line */
            filePath: $data['file_path'] ?? null,
        );
    }
}
