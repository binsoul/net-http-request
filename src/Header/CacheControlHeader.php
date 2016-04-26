<?php

namespace BinSoul\Net\Http\Request\Header;

/**
 * Represent the "Cache-Control" header.
 */
class CacheControlHeader
{
    /** @var mixed[] */
    private $cacheControl;

    /**
     * Constructs an instance of this class.
     *
     * @param string $cacheControl raw value of the "Cache-Control" header
     * @param string $pragma       raw value of the "Pragma" header
     */
    public function __construct(string $cacheControl, string $pragma)
    {
        $this->cacheControl = $this->parseHeader($cacheControl);
        if (count($this->cacheControl) == 0 && trim(strtolower($pragma)) == 'no-cache') {
            $this->cacheControl = ['no-cache' => true];
        }
    }

    /**
     * Checks if the client has set an desired maximum age of the response.
     *
     * @return bool
     */
    public function hasMaxAge(): bool 
    {
        return isset($this->cacheControl['no-cache']) || isset($this->cacheControl['max-age']);
    }

    /**
     * Returns the desired maximum age of the response in seconds.
     *
     * The maximum age is greater than or equal to zero. If no maximum age is set this method returns PHP_INT_MAX.
     *
     * @return int
     */
    public function getMaxAge(): int 
    {
        if (isset($this->cacheControl['no-cache'])) {
            return 0;
        }

        if (isset($this->cacheControl['max-age'])) {
            return max((int) $this->cacheControl['max-age'], 0);
        }

        return PHP_INT_MAX;
    }

    /**
     * Checks if the client wants a new response if any data has changed.
     *
     * @return bool
     */
    public function isRefresh(): bool 
    {
        return isset($this->cacheControl['max-age']) ? ($this->cacheControl['max-age'] == 0) : false;
    }

    /**
     * Checks if the client wants new response.
     *
     * @return bool
     */
    public function isReload(): bool 
    {
        return isset($this->cacheControl['no-cache']);
    }

    /**
     * Parses the given header and returns an array of values.
     *
     * @param string $header
     *
     * @return mixed[]
     */
    private function parseHeader(string $header): array 
    {
        $result = [];

        $parts = explode(',', preg_replace('/\s+/', '', strtolower($header)));
        foreach ($parts as $part) {
            if (trim($part) == '') {
                continue;
            }

            $keyValue = explode('=', $part, 2);
            if (count($keyValue) <= 1) {
                $result[$part] = true;
            } else {
                $result[$keyValue[0]] = $keyValue[1];
            }
        }

        return $result;
    }
}
