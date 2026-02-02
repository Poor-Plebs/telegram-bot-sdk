<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class PreCheckoutQueryUpdate extends AbstractUpdate
{
    public function __construct(int $id, public readonly PreCheckoutQuery $preCheckoutQuery)
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
            preCheckoutQuery: PreCheckoutQuery::create($update['pre_checkout_query']),
        );
    }
}
