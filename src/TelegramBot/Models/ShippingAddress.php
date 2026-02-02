<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class ShippingAddress
{
    public function __construct(
        public readonly string $countryCode,
        public readonly string $state,
        public readonly string $city,
        public readonly string $streetLine1,
        public readonly string $streetLine2,
        public readonly string $postCode,
    ) {
    }

    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            countryCode: $update['country_code'],
            /** @phpstan-ignore-next-line */
            state: $update['state'],
            /** @phpstan-ignore-next-line */
            city: $update['city'],
            /** @phpstan-ignore-next-line */
            streetLine1: $update['street_line1'],
            /** @phpstan-ignore-next-line */
            streetLine2: $update['street_line2'],
            /** @phpstan-ignore-next-line */
            postCode: $update['post_code'],
        );
    }
}
