<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\Exception;

/**
 * Overwritten to obfuscate the telegram bot token in the path if the exception
 * factory is used so that sentry, logs and other tools do not leak the token.
 */
class ClientException extends BadResponseException
{
}
