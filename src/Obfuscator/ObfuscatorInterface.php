<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Obfuscator;

interface ObfuscatorInterface
{
    /**
     * @param mixed $value Value to obfuscate.
     * @return mixed Obfuscated value.
     */
    public function __invoke($value);
}
