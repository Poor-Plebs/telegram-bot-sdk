<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class VoiceMessage extends Message
{
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        public readonly Voice $voice,
        public readonly ?string $caption = null,
        public readonly ?self $replyToMessage = null,
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
        /** @var array<string,mixed>|null $replyToMessageData */
        $replyToMessageData = $data['reply_to_message'] ?? null;
        $replyToMessage = null;

        if (is_array($replyToMessageData) && array_key_exists('voice', $replyToMessageData)) {
            $replyToMessage = self::create($replyToMessageData);
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
            voice: Voice::create($data['voice']),
            /** @phpstan-ignore-next-line */
            caption: $data['caption'] ?? null,
            replyToMessage: $replyToMessage,
        );
    }
}
