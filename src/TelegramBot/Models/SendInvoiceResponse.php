<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class SendInvoiceResponse extends TelegramResponse
{
    /**
     * @var Message
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<int|string,mixed> $result
     */
    protected function init(array $result): void
    {
        /** @phpstan-ignore-next-line */
        $this->result = Message::create($result);
    }
}
