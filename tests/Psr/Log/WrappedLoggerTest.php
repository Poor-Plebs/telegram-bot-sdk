<?php

declare(strict_types=1);

use PoorPlebs\TelegramBotSdk\Psr\Log\WrappedLogger;
use Psr\Log\AbstractLogger;

covers(WrappedLogger::class);

final class WrappedLoggerTest extends AbstractLogger
{
    /**
     * @var array<int,array{level:mixed,message:string,context:array<int|string,mixed>}>
     */
    public array $records = [];

    /**
     * @param array<int|string,mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => $level,
            'message' => (string)$message,
            'context' => $context,
        ];
    }
}

it('uses null logger by default without throwing', function (): void {
    $logger = new WrappedLogger();

    $logger->log('info', 'message');

    expect(true)->toBeTrue();
});

it('delegates logs to provided logger and supports logger swapping', function (): void {
    $first = new WrappedLoggerTest();
    $second = new WrappedLoggerTest();

    $logger = new WrappedLogger($first);
    $logger->log('warning', 'first-message', ['a' => 1]);

    $logger->setLogger($second);
    $logger->log('error', 'second-message', ['b' => 2]);

    expect($first->records)->toHaveCount(1)
        ->and($first->records[0])->toMatchArray([
            'level' => 'warning',
            'message' => 'first-message',
            'context' => ['a' => 1],
        ])
        ->and($second->records)->toHaveCount(1)
        ->and($second->records[0])->toMatchArray([
            'level' => 'error',
            'message' => 'second-message',
            'context' => ['b' => 2],
        ]);
});
