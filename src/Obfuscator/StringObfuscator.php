<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Obfuscator;

class StringObfuscator implements ObfuscatorInterface
{
    public const DEFAULT_OBFUSCATION_INPUT = '*';

    public const DEFAULT_OBFUSCATION_MULTIPLIER = 10;

    private string $input;

    private int $multiplier;

    public function __construct(
        string $input = self::DEFAULT_OBFUSCATION_INPUT,
        int $multiplier = self::DEFAULT_OBFUSCATION_MULTIPLIER
    ) {
        $this->input = $input;
        $this->multiplier = $multiplier;
    }

    /**
     * @return string Obfuscated string.
     */
    public function __invoke($value): string
    {
        return str_repeat(
            $this->input,
            $this->multiplier
        );
    }

    public function input(): string
    {
        return $this->input;
    }

    public function multiplier(): int
    {
        return $this->multiplier;
    }
}
