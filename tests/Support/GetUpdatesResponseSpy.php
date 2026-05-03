<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Tests\Support;

use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetUpdatesResponse;

final class GetUpdatesResponseSpy extends GetUpdatesResponse
{
    public static int $instances = 0;

    /**
     * @param array<int|string,mixed> $result
     */
    protected function init(array $result): void
    {
        self::$instances++;
        parent::init($result);
    }
}
