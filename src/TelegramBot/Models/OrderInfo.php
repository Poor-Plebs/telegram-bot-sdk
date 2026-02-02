<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class OrderInfo
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $email = null,
        public readonly ?ShippingAddress $shippingAddress = null,
    ) {
    }

    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            name: $update['name'] ?? null,
            /** @phpstan-ignore-next-line */
            phoneNumber: $update['phone_number'] ?? null,
            /** @phpstan-ignore-next-line */
            email: $update['email'] ?? null,
            /** @phpstan-ignore-next-line */
            shippingAddress: isset($update['shipping_address']) ? ShippingAddress::create($update['shipping_address']) : null,
        );
    }
}
