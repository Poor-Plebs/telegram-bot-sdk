<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class Message
{
    private const COMPRESSION_LEVEL = 9;

    private const COMPRESSION_THRESHOLD = 1024 * 10;

    public static function compress(string $payload, int $threshold = self::COMPRESSION_THRESHOLD): string
    {
        if (strlen($payload) >= $threshold) {
            $deflated = gzdeflate(
                $payload,
                self::COMPRESSION_LEVEL,
                ZLIB_ENCODING_GZIP
            );

            if ($deflated === false) {
                throw new RuntimeException('Failed to compress message.');
            }

            $payload = rtrim(chunk_split(base64_encode($deflated)));
        }

        return $payload;
    }

    /**
     * Returns the string representation of an HTTP message.
     *
     * @param MessageInterface $message Message to convert to a string.
     */
    public static function toString(MessageInterface $message): string
    {
        if ($message instanceof RequestInterface) {
            $msg = trim($message->getMethod() . ' '
                    . $message->getRequestTarget())
                . ' HTTP/' . $message->getProtocolVersion();
            if (!$message->hasHeader('host')) {
                $msg .= "\r\nHost: " . $message->getUri()->getHost();
            }
        } elseif ($message instanceof ResponseInterface) {
            $msg = 'HTTP/' . $message->getProtocolVersion() . ' '
                . $message->getStatusCode() . ' '
                . $message->getReasonPhrase();
        } else {
            throw new InvalidArgumentException('Unknown message type');
        }

        foreach ($message->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                foreach ($values as $value) {
                    $msg .= "\r\n{$name}: " . $value;
                }
            } else {
                $msg .= "\r\n{$name}: " . implode(', ', $values);
            }
        }

        return "{$msg}\r\n\r\n" . self::compress(
            self::stringifyStream($message->getBody())
        );
    }

    private static function stringifyStream(StreamInterface $stream): string
    {
        if (!$stream->isSeekable()) {
            return (string)$stream;
        }

        $position = $stream->tell();
        $stream->rewind();

        try {
            return $stream->getContents();
        } finally {
            $stream->seek($position);
        }
    }
}
