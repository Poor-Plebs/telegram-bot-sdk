<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class CallbackQuery
{
    public function __construct(
        public readonly string $id,
        public readonly User $from,
        public readonly Message $message,
        public readonly string $chatInstance,
        public readonly string $data,
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
            from: User::create($update['from']),
            /** @phpstan-ignore-next-line */
            message: Message::create($update['message']),
            /** @phpstan-ignore-next-line */
            chatInstance: $update['chat_instance'],
            /** @phpstan-ignore-next-line */
            data: $update['data'],
        );
    }
}
