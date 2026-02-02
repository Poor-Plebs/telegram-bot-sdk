<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7;

use GuzzleHttp\Psr7\Uri as GuzzleUri;
use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;
use Psr\Http\Message\UriInterface;
use UnexpectedValueException;

final class Uri extends GuzzleUri
{
    /**
     * Redeclared this property in this class, because guzzle's Uri
     * implementation declared this property as private.
     *
     * @var array<string,string>
     */
    private static $replaceQuery = ['=' => '%3D', '&' => '%26'];

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
        $result = [];

        if ($query === '') {
            return $result;
        }

        $decoder = fn (string $url): string => rawurldecode(str_replace(
            '+',
            ' ',
            $url
        ));

        foreach (explode('&', $query) as $keyValuePair) {
            $parts = explode('=', $keyValuePair, 2);
            $key = $decoder($parts[0]);
            $value = isset($parts[1]) ? $decoder($parts[1]) : null;

            if (!isset($result[$key])) {
                $result[$key] = $value;
            } else {
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            }
        }

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

        // Shortcut to avoid unecessary processing.
        if (
            $queryString === '' ||
            strpos($queryString, "{$parameter}=") === false
        ) {
            return $uri;
        }

        $finalQuery = [];
        $queryParameters = self::parseQueryString($queryString);
        foreach ($queryParameters as $queryParameter => &$values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            /* Query string separators ("=", "&") within the key or value need
             * to be encoded (while preventing double-encoding) before setting
             * the query string. All other chars that need percent-encoding will
             * be encoded by withQuery().
             */
            $finalKey = strtr($queryParameter, self::$replaceQuery);
            foreach ($values as &$value) {
                $value = (string)$value;
                if ($queryParameter === $parameter) {
                    $value = $obfuscator($value);
                }
                $finalValue = strtr($value, self::$replaceQuery);
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
