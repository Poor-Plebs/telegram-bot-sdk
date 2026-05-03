<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use PoorPlebs\TelegramBotSdk\TelegramBot\Enums\MessageEntityType;

class MessageEntity
{
    /**
     * Side channel for unknown entity type strings emitted by Telegram during a
     * parse pass. Drained by GetUpdatesResponse at batch boundaries so callers
     * can react to additive Bot API changes (e.g. log a deduped notice) without
     * threading an observability dependency through every static factory.
     *
     * @var array<int,string>
     */
    private static array $unknownTypeAccumulator = [];

    public function __construct(
        public readonly MessageEntityType $type,
        public readonly int $offset,
        public readonly int $length,
    ) {
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            type: MessageEntityType::from($data['type']),
            /** @phpstan-ignore-next-line */
            offset: $data['offset'],
            /** @phpstan-ignore-next-line */
            length: $data['length'],
        );
    }

    /**
     * Drain and return the unknown type strings recorded since the previous
     * flush. Intended for batch-boundary consumers (GetUpdatesResponse).
     *
     * @return array<int,string>
     */
    public static function flushUnknownTypeAccumulator(): array
    {
        $types = self::$unknownTypeAccumulator;
        self::$unknownTypeAccumulator = [];

        return $types;
    }

    /**
     * Tolerant factory: returns null when Telegram emits an entity type the SDK
     * does not yet recognize (additive Bot API change), so callers can drop the
     * unknown annotation without crashing the surrounding message parse. The
     * unknown type string is recorded into the accumulator so a higher layer
     * (typically GetUpdatesResponse) can surface it for observability.
     *
     * @param array<string,mixed> $data
     */
    public static function tryCreate(array $data): ?self
    {
        /** @phpstan-ignore-next-line */
        $type = MessageEntityType::tryFrom($data['type'] ?? '');

        if ($type === null) {
            $rawType = $data['type'] ?? null;
            if (is_string($rawType) && $rawType !== '') {
                self::$unknownTypeAccumulator[] = $rawType;
            }

            return null;
        }

        return new self(
            type: $type,
            /** @phpstan-ignore-next-line */
            offset: $data['offset'],
            /** @phpstan-ignore-next-line */
            length: $data['length'],
        );
    }
}
