<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use InvalidArgumentException;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly bool $isBot,
        public readonly string $firstName,
        public readonly ?string $lastName = null,
        public readonly ?string $username = null,
        public readonly ?string $languageCode = null,
        public readonly ?bool $isPremium = null,
        public readonly ?bool $addedToAttachmentMenu = null,
        public readonly ?bool $canJoinGroups = null,
        public readonly ?bool $canReadAllGroupMessages = null,
        public readonly ?bool $supportsInlineQueries = null,
        public readonly ?bool $canConnectToBusiness = null,
        public readonly ?bool $hasMainWebApp = null,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            id: $data['id'],
            /** @phpstan-ignore-next-line */
            isBot: $data['is_bot'],
            /** @phpstan-ignore-next-line */
            firstName: $data['first_name'],
            /** @phpstan-ignore-next-line */
            lastName: $data['last_name'] ?? null,
            /** @phpstan-ignore-next-line */
            username: $data['username'] ?? null,
            /** @phpstan-ignore-next-line */
            languageCode: $data['language_code'] ?? null,
            /** @phpstan-ignore-next-line */
            isPremium: $data['is_premium'] ?? null,
            /** @phpstan-ignore-next-line */
            addedToAttachmentMenu: $data['added_to_attachment_menu'] ?? null,
            /** @phpstan-ignore-next-line */
            canJoinGroups: $data['can_join_groups'] ?? null,
            /** @phpstan-ignore-next-line */
            canReadAllGroupMessages: $data['can_read_all_group_messages'] ?? null,
            /** @phpstan-ignore-next-line */
            supportsInlineQueries: $data['supports_inline_queries'] ?? null,
            /** @phpstan-ignore-next-line */
            canConnectToBusiness: $data['can_connect_to_business'] ?? null,
            /** @phpstan-ignore-next-line */
            hasMainWebApp: $data['has_main_web_app'] ?? null,
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function key(array $data): int
    {
        if (!is_int($data['id'])) {
            throw new InvalidArgumentException(sprintf(
                'Type `%s` of data field `id` is not an integer.',
                gettype($data['id']),
            ));
        }

        return $data['id'];
    }
}
