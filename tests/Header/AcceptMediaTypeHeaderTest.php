<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Header\AcceptMediaTypeHeader;

class AcceptMediaTypeHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function acceptMediaType()
    {
        return [
            ['', ['*/*']],
            ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', ['text/html', 'application/xhtml+xml', 'application/xml', '*/*']],
            ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*', ['text/html', 'application/xhtml+xml', '*/*', 'application/xml']],
            ['text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, */*;q=0.5', ['text/html;level=1', 'text/html', '*/*', 'text/html;level=2', 'text/*']],
        ];
    }

    /**
     * @dataProvider acceptMediaType
     */
    public function test_parses_correctly($header, $expectedMediaTypes)
    {
        $this->assertEquals($expectedMediaTypes, (new AcceptMediaTypeHeader($header))->getMediaTypes(), $header);
    }
}
