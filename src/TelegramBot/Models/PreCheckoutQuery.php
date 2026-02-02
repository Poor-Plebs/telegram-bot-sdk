<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class PreCheckoutQuery
{
    public function __construct(
        public readonly string $id,
        public readonly User $from,
        public readonly string $currency,
        public readonly int $totalAmount,
        public readonly string $invoicePayload,
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
            currency: $update['currency'],
            /** @phpstan-ignore-next-line */
            totalAmount: $update['total_amount'],
            /** @phpstan-ignore-next-line */
            invoicePayload: $update['invoice_payload'],
        );
    }
}
