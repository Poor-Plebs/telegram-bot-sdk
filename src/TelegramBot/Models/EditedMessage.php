<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class EditedMessage extends Message
{
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        public readonly CarbonImmutable $editDate,
    ) {
        parent::__construct(
            messageId: $messageId,
            date: $date,
            from: $from,
            chat: $chat,
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function create(array $data): self
    {
        if (array_key_exists('text', $data) && ($data['is_topic_message'] ?? false) === true) {
            return EditedForumTopicTextMessage::create($data);
        } elseif (array_key_exists('text', $data)) {
            return EditedTextMessage::create($data);
        }

        return new self(
            /** @phpstan-ignore-next-line */
            messageId: $data['message_id'],
            /** @phpstan-ignore-next-line */
            date: new CarbonImmutable($data['date']),
            /** @phpstan-ignore-next-line */
            from: User::create($data['from']),
            /** @phpstan-ignore-next-line */
            chat: Chat::create($data['chat']),
            /** @phpstan-ignore-next-line */
            editDate: new CarbonImmutable($data['edit_date']),
        );
    }
}
