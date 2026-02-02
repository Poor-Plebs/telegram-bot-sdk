<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class GetWebhookInfoResponse extends TelegramResponse
{
    /**
     * @var WebhookInfo
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * @param array<int|string,mixed> $result
     */
    protected function init(array $result): void
    {
        $lastErrorDate = $result['last_error_date'] ?? null;
        $lastSyncErrorDate = $result['last_synchronization_error_date'] ?? null;

        /** @phpstan-ignore-next-line */
        $this->result = new WebhookInfo(
            /** @phpstan-ignore-next-line */
            url: $result['url'],
            /** @phpstan-ignore-next-line */
            hasCustomCertificate: $result['has_custom_certificate'],
            /** @phpstan-ignore-next-line */
            pendingUpdateCount: $result['pending_update_count'],
            /** @phpstan-ignore-next-line */
            ipAddress: $result['ip_address'] ?? null,
            lastErrorDate: is_int($lastErrorDate) ? CarbonImmutable::createFromTimestamp($lastErrorDate) : null,
            /** @phpstan-ignore-next-line */
            lastErrorMessage: $result['last_error_message'] ?? null,
            lastSynchronizationErrorDate: is_int($lastSyncErrorDate) ? CarbonImmutable::createFromTimestamp($lastSyncErrorDate) : null,
            /** @phpstan-ignore-next-line */
            maxConnections: $result['max_connections'] ?? null,
            /** @phpstan-ignore-next-line */
            allowedUpdates: $result['allowed_updates'] ?? null,
        );
    }
}
