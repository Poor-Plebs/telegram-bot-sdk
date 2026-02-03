<?php

declare(strict_types=1);

namespace PoorPlebs\TelegramBotSdk\GuzzleHttp;

use GuzzleHttp\MessageFormatterInterface;
use GuzzleHttp\Psr7\Utils;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Message;
use PoorPlebs\TelegramBotSdk\GuzzleHttp\Psr7\Uri;
use PoorPlebs\TelegramBotSdk\Obfuscator\StringObfuscator;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Stringable;
use Throwable;
use UnexpectedValueException;

use function date;
use function gethostname;
use function gmdate;
use function implode;
use function preg_replace_callback;
use function strpos;
use function substr;
use function trim;

final class ObfuscatedMessageFormatter implements MessageFormatterInterface
{
    /**
     * Apache Common Log Format.
     *
     * @link https://httpd.apache.org/docs/2.4/logs.html#common
     *
     * @var string
     */
    public const CLF = '{hostname} {req_header_User-Agent} - [{date_common_log}] "{method} {target} HTTP/{version}" {code} {res_header_Content-Length}';

    public const DEBUG = ">>>>>>>>\n{request}\n<<<<<<<<\n{response}\n--------\n{error}";

    public const SHORT = '[{ts}] "{method} {target} HTTP/{version}" {code}';

    /**
     * @var array<string,StringObfuscator>
     */
    protected array $queryParameters = [];

    /**
     * @var array<string,StringObfuscator>
     */
    protected array $reqBodyParameters = [];

    /**
     * @var array<string,StringObfuscator>
     */
    protected array $reqHeaders = [];

    /**
     * @var array<string,StringObfuscator>
     */
    protected array $resBodyParameters = [];

    /**
     * @var array<string,StringObfuscator>
     */
    protected array $resHeaders = [];

    /**
     * @var array<string,StringObfuscator>
     */
    protected array $uriParameters = [];

    private StringObfuscator $defaultObfuscator;

    /**
     * @var string Template used to format log messages
     */
    private $template;

    /**
     * @param string $template Log message template
     */
    public function __construct(
        ?string $template = self::CLF,
        ?StringObfuscator $defaultObfuscator = null
    ) {
        $this->template = $template ?? self::CLF;

        $this->defaultObfuscator = $defaultObfuscator ?? new StringObfuscator();
    }

    public function format(
        RequestInterface $req,
        ?ResponseInterface $res = null,
        ?Throwable $err = null
    ): string {
        $req = $this->obfuscateRequest($req);
        if ($res instanceof ResponseInterface) {
            $res = $this->obfuscateResponse($res);
        }

        $cache = [];

        /** @var string */
        return preg_replace_callback(
            '/{\s*([A-Za-z_\-\.0-9]+)\s*}/',
            function (array $matches) use ($req, $res, $err, &$cache): string {
                if (isset($cache[$matches[1]])) {
                    return $cache[$matches[1]];
                }

                $result = '';
                switch ($matches[1]) {
                    case 'request':
                        $result = Message::toString($req);
                        break;
                    case 'response':
                        if (!$res instanceof ResponseInterface) {
                            $result = '';
                            break;
                        }

                        $result = $this->shouldLogResponseBody($res)
                            ? Message::toString($res)
                            : $this->responseToStringWithoutBody($res);
                        break;
                    case 'req_headers':
                        $result = trim($req->getMethod()
                            . ' ' . $req->getRequestTarget())
                            . ' HTTP/' . $req->getProtocolVersion() . "\r\n"
                            . $this->headers($req);
                        break;
                    case 'res_headers':
                        $result = $res instanceof ResponseInterface
                            ? \sprintf(
                                'HTTP/%s %d %s',
                                $res->getProtocolVersion(),
                                $res->getStatusCode(),
                                $res->getReasonPhrase()
                            ) . "\r\n" . $this->headers($res)
                            : 'NULL';
                        break;
                    case 'req_body':
                        $result = Message::compress(
                            self::stringifyStream($req->getBody())
                        );
                        break;
                    case 'res_body':
                        if (!$res instanceof ResponseInterface) {
                            $result = 'NULL';
                            break;
                        }

                        if (!$this->shouldLogResponseBody($res)) {
                            $result = 'RESPONSE_NOT_LOGGEABLE';
                            break;
                        }

                        $body = $res->getBody();

                        if (!$body->isSeekable()) {
                            $result = 'RESPONSE_NOT_LOGGEABLE';
                            break;
                        }

                        $result = Message::compress(
                            self::stringifyStream($res->getBody())
                        );
                        break;
                    case 'ts':
                    case 'date_iso_8601':
                        $result = gmdate('c');
                        break;
                    case 'date_common_log':
                        $result = date('d/M/Y:H:i:s O');
                        break;
                    case 'method':
                        $result = $req->getMethod();
                        break;
                    case 'version':
                        $result = $req->getProtocolVersion();
                        break;
                    case 'uri':
                    case 'url':
                        $result = $req->getUri()->__toString();
                        break;
                    case 'target':
                        $result = $req->getRequestTarget();
                        break;
                    case 'req_version':
                        $result = $req->getProtocolVersion();
                        break;
                    case 'res_version':
                        $result = $res instanceof ResponseInterface
                            ? $res->getProtocolVersion()
                            : 'NULL';
                        break;
                    case 'host':
                        $result = $req->getHeaderLine('Host');
                        break;
                    case 'hostname':
                        $result = gethostname();
                        break;
                    case 'code':
                        $result = $res instanceof ResponseInterface ? $res->getStatusCode() : 'NULL';
                        break;
                    case 'phrase':
                        $result = $res instanceof ResponseInterface ? $res->getReasonPhrase() : 'NULL';
                        break;
                    case 'error':
                        $result = $err instanceof Throwable ? $err->getMessage() : 'NULL';
                        break;
                    default:
                        // handle prefixed dynamic headers
                        if (strpos($matches[1], 'req_header_') === 0) {
                            $result = $req->getHeaderLine(substr($matches[1], 11));
                        } elseif (strpos($matches[1], 'res_header_') === 0) {
                            $result = $res instanceof ResponseInterface
                                ? $res->getHeaderLine(substr($matches[1], 11))
                                : 'NULL';
                        }
                }

                $cache[$matches[1]] = (string)$result;

                return (string)$result;
            },
            $this->template
        );
    }

    /**
     * @param array<int|string,string|StringObfuscator> $queryParameters
     */
    public function setQueryParameters(array $queryParameters): self
    {
        $this->queryParameters = $this->prepareParameters($queryParameters);

        return $this;
    }

    /**
     * @param array<int|string,string|StringObfuscator> $reqBodyParameters
     */
    public function setRequestBodyParameters(array $reqBodyParameters): self
    {
        $this->reqBodyParameters = $this->prepareParameters($reqBodyParameters);

        return $this;
    }

    /**
     * @param array<int|string,string|StringObfuscator> $reqHeaders
     */
    public function setRequestHeaders(array $reqHeaders): self
    {
        $this->reqHeaders = $this->prepareParameters($reqHeaders);

        return $this;
    }

    /**
     * @param array<int|string,string|StringObfuscator> $resBodyParameters
     */
    public function setResponseBodyParameters(array $resBodyParameters): self
    {
        $this->resBodyParameters = $this->prepareParameters($resBodyParameters);

        return $this;
    }

    /**
     * @param array<int|string,string|StringObfuscator> $resHeaders
     */
    public function setResponseHeaders(array $resHeaders): self
    {
        $this->resHeaders = $this->prepareParameters($resHeaders);

        return $this;
    }

    /**
     * @param array<int|string,string|StringObfuscator> $uriParameters
     */
    public function setUriParameters(array $uriParameters): self
    {
        $this->uriParameters = $this->prepareParameters($uriParameters);

        return $this;
    }

    /**
     * Get headers from message as string
     */
    private function headers(MessageInterface $message): string
    {
        $result = '';
        foreach ($message->getHeaders() as $name => $values) {
            $result .= $name . ': ' . implode(', ', $values) . "\r\n";
        }

        return trim($result);
    }

    private function obfuscateRequest(RequestInterface $req): RequestInterface
    {
        $req = $this->obfuscateRequestHeaders($req);
        $req = $this->obfuscateUri($req);
        $req = $this->obfuscateRequestBody($req);

        return $req;
    }

    private function obfuscateRequestBody(RequestInterface $req): RequestInterface
    {
        if (
            $this->reqBodyParameters === [] ||
            !$req->hasHeader('Content-Type')
        ) {
            return $req;
        }

        $contentTypes = $req->getHeader('Content-Type');
        $contentType = end($contentTypes);
        if (
            !is_string($contentType) ||
            !self::containsAny($contentType, ['/json', '+json'])
        ) {
            return $req;
        }

        $body = $req->getBody();
        $position = $body->isSeekable()
            ? $body->tell()
            : null;

        /** @var array<int|string,mixed> $reqJson */
        $reqJson = json_decode(
            self::stringifyStream($body),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $match = false;
        array_walk_recursive($reqJson, function (&$value, $key) use (&$match) {
            if (!is_string($key)) {
                return;
            }

            if (array_key_exists($key, $this->reqBodyParameters)) {
                if (!$match) {
                    $match = true;
                }

                $value = $this->reqBodyParameters[$key]($value);
            }
        });

        if ($match) {
            $req = $req->withBody(Utils::streamFor(json_encode(
                $reqJson,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
            )));

            if ($position !== null && $req->getBody()->isSeekable()) {
                $size = $req->getBody()->getSize();
                $targetPosition = $position;
                if (is_int($size)) {
                    $targetPosition = min($position, $size);
                }
                $req->getBody()->seek($targetPosition);
            }
        }

        return $req;
    }

    private function obfuscateRequestHeaders(RequestInterface $req): RequestInterface
    {
        foreach ($this->reqHeaders as $reqHeader => &$obfuscator) {
            if ($req->hasHeader($reqHeader)) {
                $req = $req->withHeader(
                    $reqHeader,
                    array_map($obfuscator, $req->getHeader($reqHeader))
                );
            }
        }

        return $req;
    }

    private function obfuscateResponse(ResponseInterface $res): ResponseInterface
    {
        $res = $this->obfuscateResponseHeaders($res);
        $res = $this->obfuscateResponseBody($res);

        return $res;
    }

    private function obfuscateResponseBody(ResponseInterface $res): ResponseInterface
    {
        if (
            $this->resBodyParameters === [] ||
            !$res->hasHeader('Content-Type')
        ) {
            return $res;
        }

        $contentTypes = $res->getHeader('Content-Type');
        $contentType = end($contentTypes);
        if (
            !is_string($contentType) ||
            !self::containsAny($contentType, ['/json', '+json'])
        ) {
            return $res;
        }

        $body = $res->getBody();
        $position = $body->isSeekable()
            ? $body->tell()
            : null;

        /** @var array<int|string,mixed> $resJson */
        $resJson = json_decode(
            self::stringifyStream($body),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $match = false;
        array_walk_recursive($resJson, function (&$value, $key) use (&$match) {
            if (!is_string($key)) {
                return;
            }

            if (array_key_exists($key, $this->resBodyParameters)) {
                if (!$match) {
                    $match = true;
                }

                $value = $this->resBodyParameters[$key]($value);
            }
        });

        if ($match) {
            $res = $res->withBody(Utils::streamFor(json_encode(
                $resJson,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
            )));

            if ($position !== null && $res->getBody()->isSeekable()) {
                $size = $res->getBody()->getSize();
                $targetPosition = $position;
                if (is_int($size)) {
                    $targetPosition = min($position, $size);
                }
                $res->getBody()->seek($targetPosition);
            }
        }

        return $res;
    }

    private function obfuscateResponseHeaders(ResponseInterface $res): ResponseInterface
    {
        foreach ($this->resHeaders as $resHeader => &$obfuscator) {
            if ($res->hasHeader($resHeader)) {
                $res = $res->withHeader(
                    $resHeader,
                    array_map($obfuscator, $res->getHeader($resHeader))
                );
            }
        }

        return $res;
    }

    private function obfuscateUri(RequestInterface $req): RequestInterface
    {
        $modifiedUri = $uri = $req->getUri();
        $modifiedUri = Uri::withObfuscatedUserInfo(
            $modifiedUri,
            $this->defaultObfuscator
        );

        foreach ($this->uriParameters as $pattern => $obfuscator) {
            $modifiedUri = Uri::withObfuscatedPathSegment(
                $modifiedUri,
                $pattern,
                $obfuscator
            );
        }

        foreach ($this->queryParameters as $queryParameter => $transformer) {
            $modifiedUri = Uri::withObfuscatedQueryParameter(
                $modifiedUri,
                $queryParameter,
                $transformer
            );
        }

        if ($modifiedUri !== $uri) {
            $req = $req->withUri($modifiedUri);
        }

        return $req;
    }

    /**
     * @param array<int|string,StringObfuscator|string|Stringable> $parameters
     * @return array<string,StringObfuscator>
     */
    private function prepareParameters(array $parameters): array
    {
        /** @var array<string,StringObfuscator> $prepParams */
        $prepParams = [];
        array_walk($parameters, function (StringObfuscator|string|Stringable &$value, int|string $key) use (&$prepParams): void {
            $prepKey = $value;
            $prepValue = $this->defaultObfuscator;

            if (is_string($key)) {
                if (!$value instanceof StringObfuscator) {
                    throw new UnexpectedValueException(sprintf(
                        'Assoc array value must be instance of %s. %s given.',
                        StringObfuscator::class,
                        var_export($value, true),
                    ));
                }

                $prepKey = $key;
                $prepValue = $value;
            } elseif (!is_string($prepKey) && !$prepKey instanceof Stringable) {
                throw new UnexpectedValueException(sprintf(
                    'Numeric array value must be a string or stringifyable. ' .
                        '%s given.',
                    var_export($prepKey, true),
                ));
            }

            $prepParams[(string)$prepKey] = $prepValue;
        });

        return $prepParams;
    }

    private function responseToStringWithoutBody(ResponseInterface $res): string
    {
        $msg = 'HTTP/' . $res->getProtocolVersion() . ' '
            . $res->getStatusCode() . ' '
            . $res->getReasonPhrase();

        foreach ($res->getHeaders() as $name => $values) {
            if (strtolower($name) === 'set-cookie') {
                foreach ($values as $value) {
                    $msg .= "\r\n{$name}: " . $value;
                }
            } else {
                $msg .= "\r\n{$name}: " . implode(', ', $values);
            }
        }

        return "{$msg}\r\n\r\n";
    }

    private function shouldLogResponseBody(ResponseInterface $res): bool
    {
        $contentType = strtolower($res->getHeaderLine('Content-Type'));
        if ($contentType === '') {
            return true;
        }

        $contentType = trim(strtok($contentType, ';'));

        return str_contains($contentType, '/json')
            || str_contains($contentType, '+json')
            || str_starts_with($contentType, 'text/');
    }

    /**
     * @param array<int,string> $needles
     */
    private static function containsAny(string $value, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
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
