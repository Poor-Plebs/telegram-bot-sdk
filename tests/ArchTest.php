<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\TelegramBot\Models\AbstractUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\CallbackQueryUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\EditedMessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GenericUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\MessageUpdate;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\PreCheckoutQueryUpdate;

arch('all source files use strict types')
    ->expect('PoorPlebs\TelegramBotSdk')
    ->toUseStrictTypes();

arch('all test files use strict types')
    ->expect('PoorPlebs\TelegramBotSdk\Tests')
    ->toUseStrictTypes();

arch('no debugging functions in source code')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray'])
    ->not->toBeUsed();

arch('interfaces use the interface suffix')
    ->expect('PoorPlebs\TelegramBotSdk')
    ->interfaces()
    ->toHaveSuffix('Interface');

arch('telegram enums are enums and use the type suffix')
    ->expect('PoorPlebs\TelegramBotSdk\TelegramBot\Enums')
    ->toBeEnums()
    ->toHaveSuffix('Type');

arch('telegram guzzle exceptions use the exception suffix')
    ->expect('PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception')
    ->classes()
    ->toHaveSuffix('Exception');

arch('update models extend abstract update')
    ->expect([
        CallbackQueryUpdate::class,
        EditedMessageUpdate::class,
        GenericUpdate::class,
        MessageUpdate::class,
        PreCheckoutQueryUpdate::class,
    ])
    ->toExtend(AbstractUpdate::class);
