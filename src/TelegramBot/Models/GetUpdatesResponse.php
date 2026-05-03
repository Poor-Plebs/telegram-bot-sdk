<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Throwable;

class GetUpdatesResponse extends TelegramResponse
{
    /**
     * Exceptions thrown while parsing individual updates in this batch. Failed
     * updates are replaced with GenericUpdate so the caller can still advance
     * the polling offset past them; this property exposes the raw failures so
     * the caller can report them (e.g. to Sentry).
     *
     * @var array<int,Throwable>
     */
    public array $parseErrors = [];

    /**
     * @var array<int,AbstractUpdate>
     */
    public readonly mixed $result; // @phpstan-ignore-line

    /**
     * Unknown Telegram message entity type strings observed while parsing this
     * batch (deduplicated). Subclasses can react to these for early warning of
     * additive Bot API changes that the SDK enum does not yet recognize.
     *
     * @var array<int,string>
     */
    public array $unknownMessageEntityTypes = [];

    /**
     * @param array<int|string,mixed> $result
     */
    protected function init(array $result): void
    {
        // Discard anything left in the accumulator from a prior parse pass so
        // unknown types from earlier batches cannot leak into this one.
        MessageEntity::flushUnknownTypeAccumulator();

        $updates = [];

        foreach ($result as $update) {
            if (!is_array($update)) {
                continue;
            }

            /** @var array<string,mixed> $update */
            try {
                $updates[] = UpdateFactory::create($update);
            } catch (Throwable $exception) {
                $this->parseErrors[] = $exception;

                if (isset($update['update_id']) && is_int($update['update_id'])) {
                    $updates[] = new GenericUpdate($update['update_id']);
                }
            }
        }

        $this->unknownMessageEntityTypes = array_values(array_unique(
            MessageEntity::flushUnknownTypeAccumulator(),
        ));

        /** @phpstan-ignore-next-line property.readOnlyAssignNotInConstructor */
        $this->result = $updates;
    }
}
