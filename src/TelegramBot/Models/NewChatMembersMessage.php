<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Carbon\CarbonImmutable;

class NewChatMembersMessage extends Message
{
    /**
     * @param array<int,User> $newChatMembers
     */
    public function __construct(
        int $messageId,
        CarbonImmutable $date,
        User $from,
        Chat $chat,
        public readonly array $newChatMembers,
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
        $newChatMembersData = $data['new_chat_members'] ?? [];
        if (!is_array($newChatMembersData)) {
            $newChatMembersData = [];
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
            newChatMembers: self::createNewChatMembersMap($newChatMembersData),
        );
    }

    /**
     * @param array<mixed> $newChatMembersData
     * @return array<int,User>
     */
    private static function createNewChatMembersMap(array $newChatMembersData): array
    {
        $newChatMembers = [];

        foreach ($newChatMembersData as $memberData) {
            if (!is_array($memberData) || !array_key_exists('id', $memberData)) {
                continue;
            }

            /** @var array<string,mixed> $memberData */
            $member = User::create($memberData);
            $newChatMembers[$member->id] = $member;
        }

        return $newChatMembers;
    }
}
