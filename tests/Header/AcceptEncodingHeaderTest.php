<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Header\AcceptEncodingHeader;

class AcceptEncodingHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function acceptEncoding()
    {
        return [
            ['', ['*']],
            ['gzip,deflate', ['gzip', 'deflate']],
            ['deflate, gzip', ['deflate', 'gzip']],
            ['gzip;q=1.0, identity; q=0.5, *;q=0', ['gzip', 'identity', '*']],
        ];
    }

    /**
     * @dataProvider acceptEncoding
     */
    public function test_parses_correctly($header, $expectedEncodings)
    {
        $this->assertEquals($expectedEncodings, (new AcceptEncodingHeader($header))->getEncodings(), $header);
    }
}
