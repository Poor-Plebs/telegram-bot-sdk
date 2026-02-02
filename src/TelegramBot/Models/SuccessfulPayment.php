<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class SuccessfulPayment
{
    public function __construct(
        public readonly string $currency,
        public readonly int $totalAmount,
        public readonly string $invoicePayload,
        public readonly string $telegramPaymentChargeId,
        public readonly string $providerPaymentChargeId,
        public readonly ?string $shippingOptionId = null,
        public readonly ?OrderInfo $orderInfo = null,
    ) {
    }

    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            currency: $update['currency'],
            /** @phpstan-ignore-next-line */
            totalAmount: $update['total_amount'],
            /** @phpstan-ignore-next-line */
            invoicePayload: $update['invoice_payload'],
            /** @phpstan-ignore-next-line */
            telegramPaymentChargeId: $update['telegram_payment_charge_id'],
            /** @phpstan-ignore-next-line */
            providerPaymentChargeId: $update['provider_payment_charge_id'],
            /** @phpstan-ignore-next-line */
            shippingOptionId: $update['shipping_option_id'] ?? null,
            /** @phpstan-ignore-next-line */
            orderInfo: isset($update['order_info']) ? OrderInfo::create($update['order_info']) : null,
        );
    }
}
