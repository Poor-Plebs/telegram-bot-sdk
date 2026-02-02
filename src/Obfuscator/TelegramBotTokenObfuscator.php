<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Obfuscator;

class TelegramBotTokenObfuscator extends StringObfuscator
{
    // Matches "/bot<digits>:<token>" where token may include letters, digits, underscore, and hyphen
    public const TOKEN_REGEX = '/\/bot\d+:[A-Za-z0-9_\-]+/';

    /**
     * @return string Obfuscated string.
     */
    public function __invoke($value): string
    {
        return '/bot' . parent::__invoke($value);
    }
}
