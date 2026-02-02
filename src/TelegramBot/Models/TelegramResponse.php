<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\TelegramBot\Models;

use Psr\Http\Message\ResponseInterface;

class TelegramResponse
{
    public readonly ?string $description;

    public readonly bool $ok;

    public readonly mixed $result;

    public function __construct(public readonly ResponseInterface $response)
    {
        /** @var array<int|string,mixed> $data */
        $data = json_decode(
            json: (string)$response->getBody(),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        /** @phpstan-ignore-next-line */
        $this->ok = $data['ok'];

        if (array_key_exists('description', $data) && is_string($data['description'])) {
            $this->description = $data['description'];
        } else {
            $this->description = null;
        }

        if (array_key_exists('result', $data) && is_array($data['result'])) {
            $this->init($data['result']);
        } else {
            $this->result = null;
        }
    }

    /**
     * Overwrites must initialize $this->result with the appropriate model or scalar.
     *
     * @param array<int|string,mixed> $result
     */
    protected function init(array $result): void
    {
        /** @phpstan-ignore-next-line */
        $this->result = $result;
    }

    public static function make(ResponseInterface $response): static
    {
        /** @phpstan-ignore-next-line */
        return new static($response);
    }
}
