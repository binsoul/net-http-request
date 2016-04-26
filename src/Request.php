<?php

declare (strict_types = 1);

namespace BinSoul\Net\Http\Request;

use BinSoul\Net\Http\Message\ServerRequest;
use BinSoul\Net\Http\Request\Header\AcceptCharsetHeader;
use BinSoul\Net\Http\Request\Header\AcceptEncodingHeader;
use BinSoul\Net\Http\Request\Header\AcceptLanguageHeader;
use BinSoul\Net\Http\Request\Header\AcceptMediaTypeHeader;
use BinSoul\Net\Http\Request\Header\CacheControlHeader;
use BinSoul\Net\Http\Request\Header\UserAgentHeader;
use BinSoul\Net\Http\Request\Role\ClientRole;
use BinSoul\Net\Http\Request\Role\ServerRole;
use BinSoul\Net\IP;

/**
 * Extends ServerRequest with some often used methods and access to header objects.
 */
class Request extends ServerRequest
{
    /**
     * Returns the value of an attribute, a query or a post parameter.
     *
     * Values are looked up in the following order:
     *
     * - attributes
     * - query parameters
     * - post parameters
     *
     * @param string $name    name of the parameter
     * @param mixed  $default return value if the parameter doesn't exist
     *
     * @return string|mixed[]
     */
    public function get(string $name, $default = null)
    {
        $result = $default;
        if ($this->attributes->has($name)) {
            $result = $this->attributes->get($name, $default);
        } elseif ($this->query->has($name)) {
            $result = $this->query->get($name, $default);
        } elseif ($this->post->has($name)) {
            $result = $this->post->get($name, $default);
        }

        return $result;
    }

    /**
     * Checks if a parameter exists.
     *
     * @param string $name name of the parameter
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->attributes->has($name) || $this->query->has($name) || $this->post->has($name);
    }

    /**
     * Returns an array of all parameters combined.
     *
     * @return mixed[]
     */
    public function all(): array
    {
        return array_merge(
            $this->post->all(),
            $this->query->all(),
            $this->attributes->all()
        );
    }

    /**
     * Returns whether the request is SSL-encrypted or not.
     *
     * @return bool
     */
    public function isSSL(): bool
    {
        return
            strtolower($this->headers->get('X-Forwarded-Https', '')) == 'on' ||
            $this->headers->get('X-Forwarded-Https') == 1 ||
            strtolower($this->headers->get('X-Forwarded-Proto', '')) == 'https' ||
            strtolower($this->server->get('HTTPS', '')) == 'on' ||
            $this->server->get('HTTPS') == 1;
    }

    /**
     * Returns whether the request possibly comes from Javascript or not.
     *
     * @return bool
     */
    public function isJavascript(): bool
    {
        return strtolower($this->headers->get('X-Requested-With', '')) == 'xmlhttprequest';
    }

    /**
     * Returns if the "DNT" header is set and indicates that the user doesn't want to be tracked.
     *
     * @return bool
     */
    public function isDoNotTrack(): bool
    {
        return $this->headers->get('DNT', '0') == '1';
    }

    /**
     * Returns the client of the request.
     *
     * @return ClientRole
     */
    public function getClient(): ClientRole
    {
        $ip = '';
        $values = $this->headers->getValues('X-Forwarded-For');
        if (count($values) > 0 && IP::isValid($values[count($values) - 1])) {
            $ip = $values[count($values) - 1];
        } elseif (IP::isValid($this->headers->get('Client-IP', ''))) {
            $ip = $this->headers->get('Client-IP');
        } elseif (IP::isValid($this->server->get('REMOTE_ADDR', ''))) {
            $ip = $this->server->get('REMOTE_ADDR');
        }

        $port = $this->server->get('REMOTE_PORT');

        return new ClientRole($ip, $port);
    }

    /**
     * Returns the server of the request.
     *
     * @return ServerRole
     */
    public function getServer(): ServerRole
    {
        $ip = $this->server->get('SERVER_ADDR', '127.0.0.1');
        $port = $this->server->get('SERVER_PORT');

        return new ServerRole($ip, $port);
    }

    /**
     * Returns the user agent of the request.
     *
     * @return UserAgentHeader
     */
    public function getUserAgent(): UserAgentHeader
    {
        if ($this->headers->get('X-Original-User-Agent') != '') {
            $result = $this->headers->get('X-Original-User-Agent');
        } elseif ($this->headers->get('X-Device-User-Agent') != '') {
            $result = $this->headers->get('X-Device-User-Agent');
        } else {
            $result = $this->headers->get('User-Agent');
        }

        if ($this->headers->has('X-OperaMini-Phone-UA')) {
            $result .= ' '.$this->headers->get('X-OperaMini-Phone-UA');
        }

        return new UserAgentHeader(trim((string) $result));
    }

    /**
     * Returns the "Cache-Control" header of the request.
     *
     * @return CacheControlHeader
     */
    public function getCacheControl(): CacheControlHeader
    {
        return new CacheControlHeader(
            $this->headers->get('Cache-Control', ''),
            $this->headers->get('Pragma', '')
        );
    }

    /**
     * Returns the "Accept" header of the request.
     *
     * @return AcceptMediaTypeHeader
     */
    public function getAcceptMediaType(): AcceptMediaTypeHeader
    {
        return new AcceptMediaTypeHeader($this->headers->get('Accept', ''));
    }

    /**
     * Returns the "Accept-Encoding" header of the request.
     *
     * @return AcceptEncodingHeader
     */
    public function getAcceptEncoding(): AcceptEncodingHeader
    {
        return new AcceptEncodingHeader($this->headers->get('Accept-Encoding', ''));
    }

    /**
     * Returns the "Accept-Language" header of the request.
     *
     * @return AcceptLanguageHeader
     */
    public function getAcceptLanguage(): AcceptLanguageHeader
    {
        return new AcceptLanguageHeader($this->headers->get('Accept-Language', ''));
    }

    /**
     * Returns the "Accept-Charset" header of the request.
     *
     * @return AcceptCharsetHeader
     */
    public function getAcceptCharset(): AcceptCharsetHeader
    {
        return new AcceptCharsetHeader($this->headers->get('Accept-Charset', ''));
    }
}
