<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Obfuscator;

use PoorPlebs\GuzzleObfuscatedFormatter\Obfuscator\StringObfuscator;

class TelegramBotTokenObfuscator extends StringObfuscator
{
    // Matches "/bot<digits>:<token>" where token may include letters, digits, underscore, and hyphen
    public const TOKEN_REGEX = '/\/bot\d+:[A-Za-z0-9_\-]+/';

    public function __invoke(mixed $value): string
    {
        return '/bot' . parent::__invoke($value);
    }
}
