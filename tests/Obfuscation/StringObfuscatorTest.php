<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;

covers(StringObfuscator::class);

it('obfuscates values with configured character and multiplier', function (): void {
    $obfuscator = new StringObfuscator('x', 4);

    expect($obfuscator('secret'))->toBe('xxxx')
        ->and($obfuscator->input())->toBe('x')
        ->and($obfuscator->multiplier())->toBe(4);
});

it('uses defaults when no configuration is provided', function (): void {
    $obfuscator = new StringObfuscator();

    expect($obfuscator('anything'))->toBe('**********')
        ->and($obfuscator->input())->toBe(StringObfuscator::DEFAULT_OBFUSCATION_INPUT)
        ->and($obfuscator->multiplier())->toBe(StringObfuscator::DEFAULT_OBFUSCATION_MULTIPLIER);
});
