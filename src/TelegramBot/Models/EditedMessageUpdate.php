<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class EditedMessageUpdate extends AbstractUpdate
{
    public function __construct(int $id, public readonly EditedMessage $message)
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
            message: EditedMessage::create($update['edited_message']),
        );
    }
}
