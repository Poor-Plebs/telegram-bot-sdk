<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\Psr\Log;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stringable;

/**
 * Allowes changing the logger without being able to access the logger instance.
 *
 * Useful with classes where we can pass a logger to the constructor, but the
 * class does not implement the LoggerAwareInterface or does not provide a
 * setter to set a new logger instance.
 */
class WrappedLogger extends AbstractLogger implements LoggerAwareInterface
{
    protected LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param array<int|string,mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
