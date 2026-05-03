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
     * @param array<int|string,mixed> $result
     */
    protected function init(array $result): void
    {
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

        /** @phpstan-ignore-next-line property.readOnlyAssignNotInConstructor */
        $this->result = $updates;
    }
}
