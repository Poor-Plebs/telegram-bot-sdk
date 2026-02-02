<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class CallbackQueryUpdate extends AbstractUpdate
{
    public function __construct(int $id, public readonly CallbackQuery $callbackQuery)
    {
        parent::__construct($id);
    }

    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): self
    {
        return new self(
            /** @phpstan-ignore-next-line */
            id: $update['update_id'],
            /** @phpstan-ignore-next-line */
            callbackQuery: CallbackQuery::create($update['callback_query']),
        );
    }
}
