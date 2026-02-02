<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

abstract class AbstractUpdate
{
    public function __construct(public readonly int $id)
    {
    }
}
