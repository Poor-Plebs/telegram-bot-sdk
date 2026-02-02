<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class GeneralForumTopicUnhidden
{
    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self();
    }
}
