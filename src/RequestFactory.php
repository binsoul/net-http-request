<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Request;

use BinSoul\Bridge\Http\Message\UriFactory;
use BinSoul\Bridge\Http\Message\StreamFactory;
use BinSoul\Net\Http\Message\Collection\HeaderCollection;
use BinSoul\Net\Http\Message\Collection\ParameterCollection;
use BinSoul\Net\Http\Message\Part\Header;
use BinSoul\Net\Http\Message\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Builds instances of the Request class.
 */
class RequestFactory
{
    /** @var UriFactory */
    private $uriFactory;
    /** @var StreamFactory */
    private $streamFactory;

    /**
     * Constructs an instance of this class.
     *
     * @param UriFactory    $uriFactory
     * @param StreamFactory $streamFactory
     */
    public function __construct(UriFactory $uriFactory, StreamFactory $streamFactory)
    {
        $this->uriFactory = $uriFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * Builds a Request instance from PHP globals.
     *
     * @return Request
     */
    public function buildFromEnvironment(): Request
    {
        $get = isset($_GET) ? $_GET : [];
        $post = isset($_POST) ? $_POST : [];
        $cookie = isset($_COOKIE) ? $_COOKIE : [];
        $files = isset($_FILES) ? $_FILES : [];
        $server = isset($_SERVER) ? $_SERVER : [];

        return $this->buildFromData(
            $this->streamFactory->build('php://input', 'rb'),
            $server,
            $get,
            $post,
            $cookie,
            $files
        );
    }

    /**
     * Builds a Request instance from provided data.
     *
     * @param StreamInterface $inputStream
     * @param array           $server
     * @param array           $get
     * @param array           $post
     * @param array           $cookie
     * @param array           $files
     *
     * @return Request
     */
    public function buildFromData(
        StreamInterface $inputStream,
        array $server = [],
        array $get = [],
        array $post = [],
        array $cookie = [],
        array $files = []
    ): Request {
        $headerCollection = new HeaderCollection($this->extractHeaderValues($server));
        $serverCollection = new ParameterCollection($this->extractServerValues($server));

        if (strpos($headerCollection->get('Content-Type', ''), 'application/x-www-form-urlencoded') === 0 &&
            in_array(strtoupper($serverCollection->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE'])
        ) {
            parse_str((string) $inputStream, $post);
        }

        $postCollection = new ParameterCollection($post);

        return new Request(
            $this->getMethod($serverCollection, $headerCollection),
            $this->buildUri($serverCollection, $headerCollection),
            $inputStream,
            $headerCollection,
            new ParameterCollection($cookie),
            new ParameterCollection($get),
            $postCollection,
            $serverCollection,
            $this->buildFiles($files),
            $this->getProtocol($serverCollection)
        );
    }

    /**
     * Extracts server parameters from $_SERVER-compatible array.
     *
     * @param array $server
     *
     * @return array
     */
    private function extractServerValues(array $server): array
    {
        $result = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_COOKIE') === 0 || strpos($key, 'HTTP_') === 0 || strpos($key, 'CONTENT_') === 0) {
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Converts an uppercase header name from $_SERVER to the real header name.
     *
     * @param string $name
     *
     * @return string
     */
    private function convertHeaderName(string $name): string
    {
        $key = strtolower(strtr($name, '_', '-'));
        $result = Header::getRegisteredName($key);
        if ($result == $key) {
            $result = strtr(ucwords(strtr(strtolower($name), '_', ' ')), ' ', '-');
        }

        return $result;
    }

    /**
     * Extracts header parameters from $_SERVER compatible array.
     *
     * @param mixed[] $server
     *
     * @return mixed[]
     */
    private function extractHeaderValues(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_COOKIE') === 0) {
                continue;
            }

            if (strpos($key, 'HTTP_') === 0) {
                $headers[$this->convertHeaderName(substr($key, 5))] = $value;
            } elseif (strpos($key, 'CONTENT_') === 0) {
                $headers[$this->convertHeaderName($key)] = $value;
            }
        }

        return $headers;
    }

    /**
     * Returns HTTP protocol version from parameter collection.
     *
     * @param ParameterCollection $server
     *
     * @return string
     */
    private function getProtocol(ParameterCollection $server): string
    {
        if (preg_match('/([0-9\.]+)/', $server->get('SERVER_PROTOCOL', ''), $matches)) {
            return $matches[0];
        }

        return '1.1';
    }

    /**
     * Returns the request method from a parameter collection.
     *
     * If an X-Http-Method-Override header exists this value is returned instead of the server provided method.
     *
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return string
     */
    private function getMethod(ParameterCollection $server, HeaderCollection $headers): string
    {
        return strtoupper($headers->get('X-Http-Method-Override', $server->get('REQUEST_METHOD', 'GET')));
    }

    /**
     * Checks if the current request uses an ssl connection.
     *
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return bool
     */
    private function isSSL(ParameterCollection $server, HeaderCollection $headers): bool
    {
        return
            strtolower($headers->get('X-Forwarded-Https', '')) == 'on' ||
            $headers->get('X-Forwarded-Https') == 1 ||
            strtolower($headers->get('X-Forwarded-Proto', '')) == 'https' ||
            strtolower($headers->get('Front-End-Https', '')) == 'on' ||
            strtolower($server->get('HTTPS', '')) == 'on' ||
            $server->get('HTTPS') == 1;
    }

    /**
     * Returns the scheme of the request uri.
     *
     * Always returns "https" if the current request uses an ssl connection.
     *
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return string
     */
    private function getScheme(ParameterCollection $server, HeaderCollection $headers): string
    {
        return $this->isSSL($server, $headers) ? 'https' : 'http';
    }

    /**
     * Returns the host of the request uri.
     *
     * Returns "localhost" if no other host parameters are available.
     * If an X-Forwarded-Host header exists the last host from this header is returned.
     *
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return string
     */
    private function getHost(ParameterCollection $server, HeaderCollection $headers): string
    {
        if (($result = $headers->get('X-Forwarded-Host'))) {
            $elements = explode(',', $result);

            $result = $elements[count($elements) - 1];
        } else {
            $result = $headers->get('Host', false);
            if (!$result) {
                $result = $server->get('SERVER_NAME', false);
            }

            if (!$result) {
                $result = $server->get('SERVER_ADDR', 'localhost');
            }
        }

        $result = trim($result);

        // Remove port number from host
        $result = preg_replace('/:\d+$/', '', $result);

        return $result;
    }

    /**
     * Returns the port of the request uri.
     *
     * Returns 443 if the current request uses an ssl connection.
     * If an X-Forwarded-Port header exists this value is returned.
     *
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return int
     */
    private function getPort(ParameterCollection $server, HeaderCollection $headers): int
    {
        if ((int) $headers->get('X-Forwarded-Port', 0) > 0) {
            return (int) $headers->get('X-Forwarded-Port', 0);
        }

        return $this->isSSL($server, $headers) ? 443 : (int) $server->get('SERVER_PORT', 80);
    }

    /**
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return string
     */
    private function getPathAndQuery(ParameterCollection $server, HeaderCollection $headers): string
    {
        $result = '';

        if ($headers->has('X-Rewrite-Url')) {
            $result = $headers->get('X-Rewrite-Url');
        } elseif ($server->has('REQUEST_URI')) {
            $result = $server->get('REQUEST_URI');
        }

        if (strpos($result, '://')) {
            $path = @parse_url($result, PHP_URL_PATH);
            if ($path !== false) {
                $result = $path;
            }
        }

        return $result;
    }

    /**
     * @param ParameterCollection $server
     * @param string              $requestUri
     *
     * @return string
     */
    private function getBaseUrl(ParameterCollection $server, string $requestUri): string
    {
        $filename = basename($server->get('SCRIPT_FILENAME'));

        if (basename($server->get('SCRIPT_NAME')) === $filename) {
            $result = $server->get('SCRIPT_NAME');
        } elseif (basename($server->get('PHP_SELF')) === $filename) {
            $result = $server->get('PHP_SELF');
        } elseif (basename($server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $result = $server->get('ORIG_SCRIPT_NAME');
        } else {
            // Backtrack up the script_filename to find the portion matching php_self
            $path = $server->get('PHP_SELF', '');
            $file = $server->get('SCRIPT_FILENAME', '');
            $segments = explode('/', trim($file, '/'));
            $segments = array_reverse($segments);
            $index = 0;
            $result = '';
            do {
                $seg = $segments[$index];
                $result = '/'.$seg.$result;
                ++$index;
            } while (($index < count($segments)) && (($pos = strpos($path, $result)) > 0));
        }

        if (strpos($requestUri, $result) === 0) {
            // full base URI matches
            return $result;
        }

        if (strpos($requestUri, dirname($result)) === 0) {
            // path matches
            return rtrim(dirname($result), '/');
        }

        $basename = basename($result);
        if (empty($basename) || !strpos($requestUri, $basename)) {
            // no match
            return '';
        }

        if ((strlen($requestUri) >= strlen($result)) && ((($pos = strpos($requestUri, $result)) > 0))) {
            $result = substr($requestUri, 0, $pos + strlen($result));
        }

        return rtrim($result, '/');
    }

    /**
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return string
     */
    private function getPath(ParameterCollection $server, HeaderCollection $headers): string
    {
        $requestUri = $this->getPathAndQuery($server, $headers);
        if (($pos = strpos($requestUri, '?'))) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        $baseUrl = $this->getBaseUrl($server, $requestUri);
        if (($baseUrl != '')) {
            if (($pathInfo = substr($requestUri, strlen($baseUrl))) === false) {
                return $baseUrl;
            }

            return $baseUrl.$pathInfo;
        }

        return $requestUri;
    }

    /**
     * Builds the request uri.
     *
     * @param ParameterCollection $server
     * @param HeaderCollection    $headers
     *
     * @return UriInterface
     */
    private function buildUri(ParameterCollection $server, HeaderCollection $headers): UriInterface
    {
        $query = ltrim($server->get('QUERY_STRING', ''), '?');
        if ($query != '') {
            $query = '?'.$query;
        }

        $scheme = $this->getScheme($server, $headers);
        $host = $this->getHost($server, $headers);
        $port = $this->getPort($server, $headers);
        $path = $this->getPath($server, $headers);

        return $this->uriFactory->build($scheme.'://'.$host.':'.$port.$path.$query);
    }

    /**
     * Builds an array of UploadedFileInterface instances from a $_FILES compatible array.
     *
     * Transforms each value into an UploadedFileInterface instance and normalizes nested arrays.
     *
     * @param array[] $files $_FILES-compatible array to transform
     *
     * @return UploadedFileInterface[]
     */
    private function buildFiles(array $files): array
    {
        $result = [];
        foreach ($files as $key => $value) {
            if (is_array($value)) {
                if (isset($value['tmp_name'])) {
                    $result[$key] = $this->buildUploadedFile($value);
                } else {
                    $result[$key] = $this->buildFiles($value);
                }

                continue;
            }

            throw new \InvalidArgumentException(
                sprintf(
                    'File data should be an array but is of type "%s".',
                    is_scalar($value) ? $value : gettype($value)
                )
            );
        }

        return $result;
    }

    /**
     * Builds an UploadedFileInterface instance from a file entry.
     *
     * If the entry represents multiple files all files are returned as array of UploadedFileInterface instances.
     *
     * @param mixed $entry entry of an $_FILES-compatible array
     *
     * @return UploadedFileInterface|UploadedFileInterface[]
     */
    private function buildUploadedFile(array $entry)
    {
        if (is_array($entry['tmp_name'])) {
            return $this->buildNestedFiles($entry);
        }

        return new UploadedFile(
            $this->streamFactory->build($entry['tmp_name'], 'rb'),
            $entry['tmp_name'],
            $entry['error'],
            $entry['size'],
            $entry['name'],
            $entry['type']
        );
    }

    /**
     * Builds an array of UploadedFileInterface instances from a nested file entry.
     *
     * @param array $files
     *
     * @return UploadedFileInterface[]
     */
    private function buildNestedFiles(array $files): array
    {
        $result = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $data = [
                'tmp_name' => $files['tmp_name'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
            ];

            $result[$key] = $this->buildUploadedFile($data);
        }

        return $result;
    }
}
