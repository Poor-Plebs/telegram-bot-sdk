<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class WebhookInfo
{
    /**
     * @param array<int,string>|null $allowedUpdates
     */
    public function __construct(
        public readonly string $url,
        public readonly bool $hasCustomCertificate,
        public readonly int $pendingUpdateCount,
        public readonly ?string $ipAddress,
        public readonly ?CarbonImmutable $lastErrorDate,
        public readonly ?string $lastErrorMessage,
        public readonly ?CarbonImmutable $lastSynchronizationErrorDate,
        public readonly ?int $maxConnections,
        public readonly ?array $allowedUpdates,
    ) {
    }

    public function setup(): bool
    {
        return $this->url !== '';
    }

    public function hasError(): bool
    {
        return $this->lastErrorDate !== null;
    }

    public function synchronized(): bool
    {
        return $this->pendingUpdateCount === 0;
    }
}
