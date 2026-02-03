<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri as GuzzleUri;
use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;
use Psr\Http\Message\UriInterface;
use UnexpectedValueException;

final class Uri extends GuzzleUri
{
    /**
     * Mirrors guzzle URI query separator replacement to avoid double-encoding
     * already encoded query keys/values before calling withQuery().
     *
     * @var array<string,string>
     */
    private const REPLACE_QUERY = ['=' => '%3D', '&' => '%26'];

    /**
     * Copied from ringcentral psr7 package.
     *
     * Parse a query string into an associative array.
     *
     * If multiple values are found for the same key, the value of that key
     * value pair will become an array. This function does not parse nested
     * PHP style arrays into an associative array (e.g., foo[a]=1&foo[b]=2 will
     * be parsed into ['foo[a]' => '1', 'foo[b]' => '2']).
     *
     * @return array<string,array<int|string|null>|string|null>
     */
    public static function parseQueryString(string $query): array
    {
        $result = Query::parse($query, true);

        /** @var array<string,array<int|string|null>|string|null> $result */
        return $result;
    }

    public static function withObfuscatedPathSegment(
        UriInterface $uri,
        string $pattern,
        StringObfuscator $obfuscator
    ): UriInterface {
        $path = $uri->getPath();

        // Suppress warning from invalid regex pattern, we'll check error state instead
        $obfuscatedPath = @preg_replace_callback(
            $pattern,
            fn (array $matches): string => $obfuscator($matches[0]),
            $path
        );

        $error = preg_last_error();
        $errorMsg = preg_last_error_msg();
        if ($obfuscatedPath === null || $error !== PREG_NO_ERROR) {
            if ($error === PREG_NO_ERROR) {
                $errorMsg = 'Unknown error';
            }

            throw new UnexpectedValueException(sprintf(
                'Failed to replace path segment. %s',
                $errorMsg
            ), $error);
        } elseif (strcmp($obfuscatedPath, $path) !== 0) {
            $uri = $uri->withPath($obfuscatedPath);
        }

        return $uri;
    }

    public static function withObfuscatedQueryParameter(
        UriInterface $uri,
        string $parameter,
        StringObfuscator $obfuscator
    ): UriInterface {
        $queryString = $uri->getQuery();

        if ($queryString === '') {
            return $uri;
        }

        $finalQuery = [];
        $queryParameters = self::parseQueryString($queryString);
        if (!array_key_exists($parameter, $queryParameters)) {
            return $uri;
        }

        foreach ($queryParameters as $queryParameter => &$values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            /* Query string separators ("=", "&") within the key or value need
             * to be encoded (while preventing double-encoding) before setting
             * the query string. All other chars that need percent-encoding will
             * be encoded by withQuery().
             */
            $finalKey = strtr($queryParameter, self::REPLACE_QUERY);
            foreach ($values as &$value) {
                $value = (string)$value;
                if ($queryParameter === $parameter) {
                    $value = $obfuscator($value);
                }
                $finalValue = strtr($value, self::REPLACE_QUERY);
                $finalQuery[] = "{$finalKey}={$finalValue}";
            }
        }

        return $uri->withQuery(implode('&', $finalQuery));
    }

    public static function withObfuscatedUserInfo(
        UriInterface $uri,
        StringObfuscator $obfuscator
    ): UriInterface {
        $userInfo = $uri->getUserInfo();
        $pos = strpos($userInfo, ':');
        if ($pos !== false) {
            $uri = $uri->withUserInfo(
                substr($userInfo, 0, $pos),
                $obfuscator(substr($userInfo, $pos + 1))
            );
        }

        return $uri;
    }
}
