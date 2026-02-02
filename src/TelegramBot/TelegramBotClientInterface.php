<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot;

use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

interface TelegramBotClientInterface
{
    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        ?bool $showAlert = null,
        ?string $url = null,
        ?int $cacheTime = null,
    ): PromiseInterface;

    public function answerPreCheckoutQuery(
        string $preCheckoutQueryId,
        bool $ok,
        ?string $errorMessage = null,
    ): PromiseInterface;

    public function deleteMessage(int $chatId, int $messageId): PromiseInterface;

    public function deleteWebhook(?bool $dropPendingUpdates = null): PromiseInterface;

    /**
     * Download a file as a stream using the configured HTTP client.
     *
     * @param string $filePath The file_path returned from getFile
     */
    public function downloadFileStream(string $filePath): StreamInterface;

    /**
     * Download a file as a stream asynchronously using the configured HTTP client.
     *
     * @param string $filePath The file_path returned from getFile
     */
    public function downloadFileStreamAsync(string $filePath): PromiseInterface;

    /**
     * Get basic information about a file and prepare it for downloading.
     *
     * @see https://core.telegram.org/bots/api#getfile
     */
    public function getFile(string $fileId): PromiseInterface;

    /**
     * Build the full download URL for a file.
     *
     * @param string $filePath The file_path returned from getFile
     * @return string The full URL to download the file
     */
    public function getFileDownloadUrl(string $filePath): string;

    public function getMe(): PromiseInterface;

    /**
     * @param array<int,string>|null $allowedUpdates
     * @param array<string,mixed> $options Additional Guzzle request options (e.g., progress callback)
     */
    public function getUpdates(
        ?int $offset = null,
        ?int $limit = null,
        ?int $timeout = null,
        ?array $allowedUpdates = null,
        array $options = [],
    ): PromiseInterface;

    public function getWebhookInfo(): PromiseInterface;

    /**
     * @param array<string,mixed> $options Animation can not be used.
     */
    public function sendAnimation(string $animation, array $options = []): PromiseInterface;

    /**
     * @param array<string,mixed> $options Text can not be used.
     */
    public function sendInvoice(array $options = []): PromiseInterface;

    /**
     * @param array<string,mixed> $options Text can not be used.
     */
    public function sendMessage(string $message, array $options = []): PromiseInterface;

    public function setLogger(LoggerInterface $logger): void;

    /**
     * @param array<int,string>|null $allowedUpdates
     */
    public function setWebhook(
        string $url,
        ?string $ipAddress = null,
        ?int $maxConnection = null,
        ?array $allowedUpdates = null,
        ?bool $dropPendingUpdates = null,
        ?string $secretToken = null,
    ): PromiseInterface;
}
