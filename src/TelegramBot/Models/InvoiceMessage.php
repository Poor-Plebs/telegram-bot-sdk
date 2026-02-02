<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class InvoiceMessage extends Message
{
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        public readonly Invoice $invoice,
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
            invoice: Invoice::create($data['invoice']),
        );
    }
}
