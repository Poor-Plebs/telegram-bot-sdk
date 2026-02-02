<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Enums;

enum MessageEntityType: string
{
    case BOLD = 'bold';
    case BOT_COMMAND = 'bot_command';
    case CASHTAG = 'cashtag';
    case CODE = 'code';
    case CUSTOM_EMOJI = 'custom_emoji';
    case EMAIL = 'email';
    case HASHTAG = 'hashtag';
    case ITALIC = 'italic';
    case MENTION = 'mention';
    case PHONE_NUMBER = 'phone_number';
    case PRE = 'pre';
    case SPOILER = 'spoiler';
    case STRIKETHROUGH = 'strikethrough';
    case TEXT_LINK = 'text_link';
    case TEXT_MENTION = 'text_mention';
    case UNDERLINE = 'underline';
    case URL = 'url';
}
