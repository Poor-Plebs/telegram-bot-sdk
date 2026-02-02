<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

class UpdateFactory
{
    /**
     * @param array<string,mixed> $update
     */
    public static function create(array $update): AbstractUpdate
    {
        if (array_key_exists('message', $update)) {
            return MessageUpdate::create($update);
        } elseif (array_key_exists('edited_message', $update)) {
            return EditedMessageUpdate::create($update);
        } elseif (array_key_exists('callback_query', $update)) {
            return CallbackQueryUpdate::create($update);
        } elseif (array_key_exists('pre_checkout_query', $update)) {
            return PreCheckoutQueryUpdate::create($update);
        }

        /** @phpstan-ignore-next-line */
        return new GenericUpdate($update['update_id']);
    }
}
