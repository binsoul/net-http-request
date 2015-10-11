<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Header\AcceptCharsetHeader;

class AcceptCharsetHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function acceptCharset()
    {
        return [
            ['', ['*']],
            ['iso-8859-5, unicode-1-1;q=0.8, utf-8', ['iso-8859-5', 'utf-8', 'unicode-1-1']],
        ];
    }

    /**
     * @dataProvider acceptCharset
     */
    public function test_parses_correctly($header, $expectedCharsets)
    {
        $this->assertEquals($expectedCharsets, (new AcceptCharsetHeader($header))->getCharsets(), $header);
    }
}
