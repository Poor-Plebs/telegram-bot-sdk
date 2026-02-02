<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Enums;

enum ChatType: string
{
    case CHANNEL = 'channel';
    case GROUP = 'group';
    case PRIVATE = 'private';
    case SUPERGROUP = 'supergroup';
}
