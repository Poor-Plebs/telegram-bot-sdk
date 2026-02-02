<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class Invoice
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $startParameter,
        public readonly string $currency,
        public readonly int $totalAmount,
    ) {
    }

    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            title: $update['title'],
            /** @phpstan-ignore-next-line */
            description: $update['description'],
            /** @phpstan-ignore-next-line */
            startParameter: $update['start_parameter'],
            /** @phpstan-ignore-next-line */
            currency: $update['currency'],
            /** @phpstan-ignore-next-line */
            totalAmount: $update['total_amount'],
        );
    }
}
