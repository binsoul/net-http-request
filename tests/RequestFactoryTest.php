<?php

namespace BinSoul\Test\Http\Request;

use BinSoul\Bridge\Http\Message\StreamFactory;
use BinSoul\Bridge\Http\Message\UriFactory;
use BinSoul\Net\Http\Request\RequestFactory;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    private function buildServer()
    {
        return [
            'DOCUMENT_ROOT' => '/var/www',
            'REMOTE_ADDR' => '::1',
            'REMOTE_PORT' => '12345',
            'SERVER_SOFTWARE' => 'PHP',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'binsoul.de',
            'SERVER_ADDR' => 'foobar.com',
            'SERVER_PORT' => '8000',
            'REQUEST_URI' => '/path?foo=bar&baz[]=qux',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => '/var/www/index.php',
            'PHP_SELF' => '/index.php',
            'QUERY_STRING' => 'foo=bar&baz[]=qux',
            'HTTP_HOST' => 'barqux.com:8000',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'de,en-US;q=0.7,en;q=0.3',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
            'HTTP_DNT' => '1',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_COOKIE' => 'frontend=16935a435226473dad8be9718fa0c982',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
    }

    private function buildRequestFactory()
    {
        $body = $this->getMock(StreamInterface::class);
        $uri = $this->getMock(UriInterface::class);

        $uri->expects($this->any())->method('__toString')->willReturnCallback(
            function () use (&$uriString) {
                return $uriString;
            }
        );

        $uriFactory = $this->getMock(UriFactory::class, ['build']);
        $uriFactory->expects($this->any())->method('build')->willReturnCallback(
            function ($string) use ($uri, &$uriString) {
                $uriString = $string;

                return $uri;
            }
        );

        $streamFactory = $this->getMock(StreamFactory::class, ['build']);
        $streamFactory->expects($this->any())->method('build')->willReturn($body);

        /* @var UriFactory $uriFactory */
        /* @var StreamFactory $streamFactory */
        return new RequestFactory($uriFactory, $streamFactory);
    }

    public function test_build_from_globals()
    {
        $request = $this->buildRequestFactory()->buildFromEnvironment();
        $this->assertInstanceOf(StreamInterface::class, $request->getBody());
    }

    public function test_detects_correct_method()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $server = $this->buildServer();
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('GET', $request->getMethod());

        $server['REQUEST_METHOD'] = 'PUT';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('PUT', $request->getMethod());

        $server['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PATCH';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('PATCH', $request->getMethod());
    }

    public function test_builds_correct_host_for_uri()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $request = $this->buildRequestFactory()->buildFromData($body, $this->buildServer());
        $this->assertEquals('http://barqux.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        $server['HTTP_X_FORWARDED_HOST'] = 'foo.com';

        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://foo.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        $server['HTTP_HOST'] = 'bar.com';

        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://bar.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        unset($server['HTTP_HOST']);

        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://binsoul.de:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        unset($server['SERVER_NAME']);
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://foobar.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        unset($server['SERVER_ADDR']);
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://localhost:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());
    }

    public function test_builds_correct_port_for_uri()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $server = $this->buildServer();
        $server['SERVER_PORT'] = '80';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:80/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        $server['HTTP_X_FORWARDED_PORT'] = '1234';

        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:1234/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['HTTPS'] = 'on';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('https://barqux.com:1234/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['HTTP_X_FORWARDED_PORT'] = '';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('https://barqux.com:443/path?foo=bar&baz[]=qux', $request->getUri()->__toString());
    }

    public function test_builds_correct_path_for_uri()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $server = $this->buildServer();
        $server['REQUEST_URI'] = '/foobar';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/foobar?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['HTTP_X_REWRITE_URL'] = '/index.php';

        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/index.php?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        $server['REQUEST_URI'] = 'http://barqux.com:8000/';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/?foo=bar&baz[]=qux', $request->getUri()->__toString());
    }

    public function test_replaces_scriptname_in_path()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $server = $this->buildServer();
        $server['SCRIPT_NAME'] = '/index.php';
        $server['SCRIPT_FILENAME'] = '/var/www/index.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['SCRIPT_NAME'] = '/foo.php';
        $server['SCRIPT_FILENAME'] = '/var/www/htdocs/public/index.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['SCRIPT_FILENAME'] = '/var/www/htdocs/public/foo.php';
        $server['PHP_SELF'] = '/htdocs/public/index.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['PHP_SELF'] = '/htdocs/public/bar.php';
        $server['SCRIPT_FILENAME'] = '/var/www/htdocs/public/index.php';
        $server['ORIG_SCRIPT_NAME'] = '/shared/htdocs/public/index.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server['ORIG_SCRIPT_NAME'] = '/shared/htdocs/public/baz.php';
        $server['PHP_SELF'] = '/htdocs/public/bar.php';
        $server['SCRIPT_FILENAME'] = 'qux.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        $server['REQUEST_URI'] = '/path1/index.php/path2/';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['SCRIPT_FILENAME'] = '/var/www/index.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path1/index.php/path2/?foo=bar&baz[]=qux', $request->getUri()->__toString());

        $server = $this->buildServer();
        $server['REQUEST_URI'] = '/path/to/index.html';
        $server['SCRIPT_NAME'] = '/path/to/index.php';
        $server['SCRIPT_FILENAME'] = '/var/www/path/index.php';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('http://barqux.com:8000/path/to/index.html?foo=bar&baz[]=qux', $request->getUri()->__toString());
    }

    public function test_uses_input_stream_as_body()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $request = $this->buildRequestFactory()->buildFromData($body, $this->buildServer());
        $this->assertSame($body, $request->getBody());
    }

    public function test_builds_simple_files()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $files = [
            [
                'tmp_name' => 'php://memory',
                'error' => UPLOAD_ERR_OK,
                'size' => 10,
                'name' => 'filename',
                'type' => 'MEMORY',
            ],
        ];

        $request = $this->buildRequestFactory()->buildFromData($body, $this->buildServer(), [], [], [], $files);
        $uploadedFiles = $request->getUploadedFiles();
        $this->assertEquals('filename', $uploadedFiles[0]->getClientFilename());
    }

    public function test_builds_nested_files()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $files = [
            'foo' => [
                'bar' => [
                    'tmp_name' => ['php://memory', 'php://memory'],
                    'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                    'size' => [10, 20],
                    'name' => ['filename1', 'filename2'],
                    'type' => ['MEMORY', 'MEMORY'],
                ],
            ],
        ];

        $request = $this->buildRequestFactory()->buildFromData($body, $this->buildServer(), [], [], [], $files);
        $uploadedFiles = $request->getUploadedFiles();
        $this->assertEquals('filename1', $uploadedFiles['foo']['bar'][0]->getClientFilename());
        $this->assertEquals('filename2', $uploadedFiles['foo']['bar'][1]->getClientFilename());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_raises_exception_for_invalid_files()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $files = [
            'foo',
        ];

        $request = $this->buildRequestFactory()->buildFromData($body, $this->buildServer(), [], [], [], $files);
    }

    public function test_detects_correct_protocol()
    {
        /** @var StreamInterface $body */
        $body = $this->getMock(StreamInterface::class);

        $server = $this->buildServer();
        unset($server['SERVER_PROTOCOL']);
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('1.1', $request->getProtocolVersion());

        $server['SERVER_PROTOCOL'] = 'HTTP/1.0';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('1.0', $request->getProtocolVersion());

        $server['SERVER_PROTOCOL'] = 'HTTP/1.2';
        $request = $this->buildRequestFactory()->buildFromData($body, $server);
        $this->assertEquals('1.2', $request->getProtocolVersion());
    }
}
