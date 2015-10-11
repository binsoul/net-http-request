<?php

namespace BinSoul\Test\Http\Request;

use BinSoul\Net\Http\Message\Collection\HeaderCollection;
use BinSoul\Net\Http\Message\Collection\ParameterCollection;
use BinSoul\Net\Http\Request\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function test_get_preference()
    {
        /** @var UriInterface $uri */
        $uri = $this->getMock(UriInterface::class);
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $collection = new ParameterCollection(['foo' => 'bar']);

        $request = new Request('get', $uri, $body);
        $this->assertEquals('bar', $request->get('foo', 'bar'));

        $request = $request->withAttribute('foo', 'bar');

        $this->assertTrue($request->has('foo'));
        $this->assertEquals('bar', $request->get('foo'));

        $request = new Request('get', $uri, $body, null, null, $collection);
        $this->assertTrue($request->has('foo'));
        $this->assertEquals('bar', $request->get('foo'));

        $request = new Request('get', $uri, $body, null, null, null, $collection);
        $this->assertTrue($request->has('foo'));
        $this->assertEquals('bar', $request->get('foo'));
    }

    public function test_all_joins_arrays()
    {
        /** @var UriInterface $uri */
        $uri = $this->getMock(UriInterface::class);
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $request = new Request(
            'get',
            $uri,
            $body,
            null,
            null,
            new ParameterCollection(['c' => 'bar']),
            new ParameterCollection(['e' => 'f'])
        );

        $request = $request->withAttributes(['a' => 'b', 'c' => 'foo']);

        $this->assertEquals(['a' => 'b', 'c' => 'foo', 'e' => 'f'], $request->all());
    }

    /**
     * @param array $headers
     * @param array $server
     *
     * @return Request
     */
    private function buildRequest(array $headers = [], array $server = [])
    {
        /** @var UriInterface $uri */
        $uri = $this->getMock(UriInterface::class);
        /* @var StreamInterface $uri */
        $body = $this->getMock(StreamInterface::class);

        return new Request(
            'get',
            $uri,
            $body,
            new HeaderCollection($headers),
            null,
            null,
            null,
            new ParameterCollection($server)
        );
    }

    public function test_detects_ssl()
    {
        $this->assertFalse($this->buildRequest()->isSSL());
        $this->assertTrue($this->buildRequest([], ['HTTPS' => 'on'])->isSSL());
        $this->assertFalse($this->buildRequest([], ['HTTPS' => 'off'])->isSSL());
        $this->assertTrue($this->buildRequest(['X-Forwarded-Https' => '1'])->isSSL());
    }

    public function test_detects_javascript()
    {
        $this->assertFalse($this->buildRequest()->isJavascript());
        $this->assertTrue($this->buildRequest(['X-Requested-With' => 'XMLHttpRequest'])->isJavascript());
        $this->assertTrue($this->buildRequest(['X-Requested-With' => 'xmlhttprequest'])->isJavascript());
    }

    public function test_detects_do_not_track()
    {
        $this->assertFalse($this->buildRequest()->isDoNotTrack());
        $this->assertTrue($this->buildRequest(['DNT' => '1'])->isDoNotTrack());
        $this->assertFalse($this->buildRequest(['DNT' => '0'])->isDoNotTrack());
    }

    public function test_uses_loopback_as_fallback_client()
    {
        $this->assertEquals('127.0.0.1', $this->buildRequest()->getClient()->getIP());
    }

    public function test_uses_client_from_server()
    {
        $this->assertEquals('1.2.3.4', $this->buildRequest([], ['REMOTE_ADDR' => '1.2.3.4'])->getClient()->getIP());
        $this->assertEquals('1234', $this->buildRequest([], ['REMOTE_PORT' => '1234'])->getClient()->getPort());
    }

    public function test_uses_client_from_request()
    {
        $this->assertEquals('1.2.3.4', $this->buildRequest(['X-Forwarded-For' => '1.2.3.4'])->getClient()->getIP());
        $this->assertEquals('1.2.3.4', $this->buildRequest(['Client-IP' => '1.2.3.4'])->getClient()->getIP());
    }

    public function test_uses_loopback_as_fallback_server()
    {
        $this->assertEquals('127.0.0.1', $this->buildRequest()->getServer()->getIP());
    }

    public function test_uses_server_from_server_data()
    {
        $this->assertEquals('1.2.3.4', $this->buildRequest([], ['SERVER_ADDR' => '1.2.3.4'])->getServer()->getIP());
        $this->assertEquals('1234', $this->buildRequest([], ['SERVER_PORT' => '1234'])->getServer()->getPort());
    }

    public function test_uses_useragent_from_request()
    {
        $this->assertEquals(
            'Firefox 41.0',
            $this->buildRequest(['User-Agent' => 'Firefox/41.0'])->getUserAgent()->getBrowser()
        );

        $this->assertEquals(
            'Firefox 41.0',
            $this->buildRequest(['X-Original-User-Agent' => 'Firefox/41.0'])->getUserAgent()->getBrowser()
        );

        $this->assertEquals(
            'Firefox 41.0',
            $this->buildRequest(['X-Device-User-Agent' => 'Firefox/41.0'])->getUserAgent()->getBrowser()
        );

        $this->assertEquals(
            'Firefox 41.0',
            $this->buildRequest(['X-OperaMini-Phone-UA' => 'Firefox/41.0'])->getUserAgent()->getBrowser()
        );
    }

    public function test_uses_cachecontrol_from_request()
    {
        $this->assertTrue($this->buildRequest(['Cache-Control' => 'no-cache'])->getCacheControl()->isReload());
        $this->assertTrue($this->buildRequest(['Pragma' => 'no-cache'])->getCacheControl()->isReload());
    }

    public function test_uses_acceptmediatype_from_request()
    {
        $this->assertEquals(
            ['foo', 'bar'],
            $this->buildRequest(['Accept' => 'foo,bar'])->getAcceptMediaType()->getMediaTypes()
        );
    }

    public function test_uses_acceptencoding_from_request()
    {
        $this->assertEquals(
            ['gzip', 'deflate'],
            $this->buildRequest(['Accept-Encoding' => 'gzip,deflate'])->getAcceptEncoding()->getEncodings()
        );
    }

    public function test_uses_acceptlanguage_from_request()
    {
        $this->assertEquals(
            ['de', 'fr'],
            $this->buildRequest(['Accept-Language' => 'de,fr;q=0.9'])->getAcceptLanguage()->getLanguages()
        );
    }

    public function test_uses_acceptcharset_from_request()
    {
        $this->assertEquals(
            ['utf-8', 'iso'],
            $this->buildRequest(['Accept-Charset' => 'utf-8,iso;q=0.9'])->getAcceptCharset()->getCharsets()
        );
    }
}
