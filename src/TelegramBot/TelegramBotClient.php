<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use PoorPlebs\GuzzleConnectRetryDecider\ConnectRetryDecider;
use PoorPlebs\GuzzleObfuscatedFormatter\GuzzleHttp\ObfuscatedMessageFormatter;
use PoorPlebs\GuzzleRetryAfterMiddleware\RetryAfterMiddleware;
use PoorPlebs\TelegramBotSdk\Obfuscator\TelegramBotTokenObfuscator;
use PoorPlebs\TelegramBotSdk\Psr\Log\WrappedLogger;
use PoorPlebs\TelegramBotSdk\TelegramBot\GuzzleHttp\TokenObfuscationMiddleware;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\File;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetFileResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetUpdatesResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\GetWebhookInfoResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\Message;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\SendInvoiceResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\SendMessageResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\TelegramResponse;
use PoorPlebs\TelegramBotSdk\TelegramBot\Models\WebhookInfo;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use UnexpectedValueException;

class TelegramBotClient implements LoggerAwareInterface, TelegramBotClientInterface
{
    /**
     * @var array<int,string> AVAILABLE_PARSE_MODES
     */
    public const AVAILABLE_PARSE_MODES = [
        self::PARSE_MODE_HTML,
        self::PARSE_MODE_MARKDOWN_V2,
        self::PARSE_MODE_MARKDOWN,
    ];

    public const BASE_URI = 'https://api.telegram.org/bot%s';

    public const DEFAULT_CONNECT_TIMEOUT = 1.0;

    public const DEFAULT_TIMEOUT = 5.0;

    public const FILE_BASE_URI = 'https://api.telegram.org/file/bot%s';

    public const PARSE_MODE_HTML = 'HTML';

    public const PARSE_MODE_MARKDOWN = 'Markdown';

    public const PARSE_MODE_MARKDOWN_V2 = 'MarkdownV2';

    private const RETRY_AFTER_CACHE_KEY_PREFIX = 'telegram:retry-after:';

    protected Client $client;

    protected WrappedLogger $logger;

    private int $chatId;

    private string $token;

    /**
     * @param array<string,mixed> $config Additional guzzle config.
     */
    public function __construct(
        CacheInterface $cache,
        string $token,
        int $chatId,
        array $config = [],
    ) {
        $this->logger = new WrappedLogger();
        $this->token = $token;
        $this->chatId = $chatId;

        $messageFormatter =
            (new ObfuscatedMessageFormatter(ObfuscatedMessageFormatter::DEBUG))
                ->setUriParameters([
                    TelegramBotTokenObfuscator::TOKEN_REGEX => new TelegramBotTokenObfuscator(),
                ])
                ->setRequestBodyParameters([
                    'chat_id',
                    'payload',
                    'provider_token',
                    'pre_checkout_query_id',
                    'callback_query_id',
                    'secret_token',
                    'ip_address',
                    'photo_url',
                ])
                ->setResponseBodyParameters([
                    'id',
                    'first_name',
                    'last_name',
                    'username',
                    'invoice_payload',
                    'telegram_payment_charge_id',
                    'provider_payment_charge_id',
                ]);

        $handlerStack = HandlerStack::create();

        $handlerStack->push(
            Middleware::retry(new ConnectRetryDecider()),
            'connect_retry',
        );

        $handlerStack->push(
            Middleware::log($this->logger, $messageFormatter, LogLevel::DEBUG),
            'obfuscated_logger',
        );

        /* Wraps http_errors so the exceptions it creates from the request
         * URI get the telegram bot token masked as well.
         */
        $handlerStack->unshift(
            new TokenObfuscationMiddleware(),
            'token_obfuscation',
        );

        $handlerStack->unshift(
            new RetryAfterMiddleware($cache),
            'retry_after',
        );

        $this->extendHandlerStack($handlerStack);

        // @phpstan-ignore argument.type (Caller supplied Guzzle config is passed through as is.)
        $this->client = new Client(array_merge([
            'base_uri' => rtrim(sprintf(
                self::BASE_URI,
                $this->token
            ), '/') . '/',
            'handler' => $handlerStack,
            RequestOptions::CONNECT_TIMEOUT => self::DEFAULT_CONNECT_TIMEOUT,
            RequestOptions::TIMEOUT => self::DEFAULT_TIMEOUT,
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
            ],
            RetryAfterMiddleware::REQUEST_OPTION => self::RETRY_AFTER_CACHE_KEY_PREFIX . hash('murmur3f', $this->token),
        ], $config));
    }

    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        ?bool $showAlert = null,
        ?string $url = null,
        ?int $cacheTime = null,
    ): PromiseInterface {
        return $this
            ->client
            ->postAsync(
                'answerCallbackQuery',
                [
                    RequestOptions::JSON => array_filter([
                        'callback_query_id' => $callbackQueryId,
                        'text' => $text,
                        'show_alert' => $showAlert,
                        'url' => $url,
                        'cache_time' => $cacheTime,
                    ], fn ($value): bool => $value !== null),
                ]
            )
            ->then(TelegramResponse::make(...));
    }

    public function answerPreCheckoutQuery(
        string $preCheckoutQueryId,
        bool $ok,
        ?string $errorMessage = null,
    ): PromiseInterface {
        if (!$ok && $errorMessage === null) {
            throw new UnexpectedValueException('Must provide an error message for failed pre checkout query.');
        }

        return $this
            ->client
            ->postAsync(
                'answerPreCheckoutQuery',
                [
                    RequestOptions::JSON => array_filter([
                        'pre_checkout_query_id' => $preCheckoutQueryId,
                        'ok' => $ok,
                        'error_message' => $errorMessage,
                    ], fn ($value): bool => $value !== null),
                ]
            )
            ->then(TelegramResponse::make(...));
    }

    public function deleteMessage(int $chatId, int $messageId): PromiseInterface
    {
        return $this
            ->client
            ->postAsync(
                'deleteMessage',
                [
                    RequestOptions::JSON => [
                        'chat_id' => $chatId,
                        'message_id' => $messageId,
                    ],
                ],
            );
    }

    public function deleteWebhook(?bool $dropPendingUpdates = null): PromiseInterface
    {
        return $this
            ->client
            ->postAsync('deleteWebhook', [
                RequestOptions::JSON => array_filter([
                    'drop_pending_updates' => $dropPendingUpdates,
                ], fn ($value): bool => $value !== null),
            ])
            ->then(TelegramResponse::make(...));
    }

    public function downloadFileStream(string $filePath): StreamInterface
    {
        $url = $this->getFileDownloadUrl($filePath);

        $response = $this->client->get($url, [
            RequestOptions::STREAM => true,
            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 5,
        ]);

        return $response->getBody();
    }

    public function downloadFileStreamAsync(string $filePath): PromiseInterface
    {
        $url = $this->getFileDownloadUrl($filePath);

        return $this->client
            ->getAsync($url, [
                RequestOptions::STREAM => true,
                RequestOptions::TIMEOUT => 30,
                RequestOptions::CONNECT_TIMEOUT => 5,
            ])
            ->then(static fn (ResponseInterface $response): StreamInterface => $response->getBody());
    }

    /**
     * @see https://core.telegram.org/bots/api#getfile
     */
    public function getFile(string $fileId): PromiseInterface
    {
        return $this
            ->client
            ->getAsync(
                'getFile',
                [
                    RequestOptions::QUERY => [
                        'file_id' => $fileId,
                    ],
                ],
            )
            ->then(GetFileResponse::make(...))
            ->then(fn (GetFileResponse $response): File => $response->result);
    }

    /**
     * Build the full download URL for a file.
     *
     * @param string $filePath The file_path returned from getFile
     * @return string The full URL to download the file
     */
    public function getFileDownloadUrl(string $filePath): string
    {
        return rtrim(sprintf(self::FILE_BASE_URI, $this->token), '/') . '/' . ltrim($filePath, '/');
    }

    public function getMe(): PromiseInterface
    {
        return $this
            ->client
            ->getAsync('getMe')
            ->then(TelegramResponse::make(...));
    }

    /**
     * @param array<int,string> $allowedUpdates
     * @param array<string,mixed> $options Additional Guzzle request options (e.g., progress callback)
     */
    public function getUpdates(
        ?int $offset = null,
        ?int $limit = null,
        ?int $timeout = null,
        ?array $allowedUpdates = null,
        array $options = [],
    ): PromiseInterface {
        $allowedUpdatesJson = null;
        if ($allowedUpdates !== null) {
            $allowedUpdatesJson = json_encode($allowedUpdates, JSON_THROW_ON_ERROR);
        }

        $responseClass = $this->getUpdatesResponseClass();

        return $this
            ->client
            ->getAsync(
                'getUpdates',
                // @phpstan-ignore argument.type (Caller supplied request options are passed through as is.)
                array_merge([
                    RequestOptions::TIMEOUT => 65.0,
                    RequestOptions::QUERY => array_filter([
                        'offset' => $offset,
                        'limit' => $limit,
                        'timeout' => $timeout,
                        'allowed_updates' => $allowedUpdatesJson,
                    ], fn ($value): bool => $value !== null),
                ], $options)
            )
            ->then(fn (ResponseInterface $response): array => $responseClass::make($response)->result);
    }

    public function getWebhookInfo(): PromiseInterface
    {
        return $this
            ->client
            ->getAsync('getWebhookInfo')
            ->then(GetWebhookInfoResponse::make(...))
            ->then(fn (GetWebhookInfoResponse $response): WebhookInfo => $response->result);
    }

    /**
     * @param array<string,mixed> $options Animation can not be used.
     */
    public function sendAnimation(string $animation, array $options = []): PromiseInterface
    {
        $mergedOptions = array_merge([
            'chat_id' => $this->chatId,
        ], $options, [
            'animation' => $animation,
        ]);

        return $this->client->postAsync(
            'sendAnimation',
            [
                RequestOptions::JSON => $mergedOptions,
            ],
        );
    }

    /**
     * @param array<string,mixed> $options Text can not be used.
     */
    public function sendInvoice(array $options = []): PromiseInterface
    {
        $mergedOptions = array_merge([
            'chat_id' => $this->chatId,
        ], $options, [
        ]);

        return $this
            ->client
            ->postAsync(
                'sendInvoice',
                [
                    RequestOptions::JSON => $mergedOptions,
                ],
            )
            ->then(SendInvoiceResponse::make(...))
            ->then(fn (SendInvoiceResponse $response): Message => $response->result);
    }

    /**
     * @param array<string,mixed> $options Text can not be used.
     */
    public function sendMessage(string $message, array $options = []): PromiseInterface
    {
        $mergedOptions = array_merge([
            'chat_id' => $this->chatId,
        ], $options, [
            'text' => $message,
        ]);

        return $this
            ->client
            ->postAsync(
                'sendMessage',
                [
                    RequestOptions::JSON => $mergedOptions,
                ],
            )
            ->then(SendMessageResponse::make(...))
            ->then(fn (SendMessageResponse $response): Message => $response->result);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger->setLogger($logger);
    }

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
    ): PromiseInterface {
        $allowedUpdatesJson = null;
        if ($allowedUpdates !== null) {
            $allowedUpdatesJson = json_encode($allowedUpdates, JSON_THROW_ON_ERROR);
        }

        return $this
            ->client
            ->postAsync(
                'setWebhook',
                [
                    RequestOptions::JSON => array_filter([
                        'url' => $url,
                        'ip_address' => $ipAddress,
                        'max_connections' => $maxConnection,
                        'allowed_updates' => $allowedUpdatesJson,
                        'drop_pending_updates' => $dropPendingUpdates,
                        'secret_token' => $secretToken,
                    ], fn ($value): bool => $value !== null),
                ]
            )
            ->then(TelegramResponse::make(...));
    }

    /**
     * Extension hook for subclasses to push additional Guzzle middleware onto
     * the handler stack the SDK constructs. Called after the SDK's own
     * middleware (connect_retry, obfuscated_logger, http_errors,
     * token_obfuscation, retry_after) is in place and before the Guzzle Client is built. Discarded if the
     * caller fully overrides the stack via the `handler` config option.
     */
    protected function extendHandlerStack(HandlerStack $stack): void // @phpstan-ignore missingType.generics (Generic since Guzzle 8 only.)
    {
    }

    /**
     * Override in subclasses to return a custom GetUpdatesResponse class.
     * Lets callers attach observability behavior to per-batch parse state
     * (e.g. report parseErrors to Sentry, log unknownMessageEntityTypes) by
     * subclassing the response and reacting in init() — without the SDK having
     * to know about the host framework's logging or error reporting facilities.
     *
     * @return class-string<GetUpdatesResponse>
     */
    protected function getUpdatesResponseClass(): string
    {
        return GetUpdatesResponse::class;
    }
}
