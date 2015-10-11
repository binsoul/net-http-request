<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Header\AcceptLanguageHeader;

class AcceptLanguageHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function acceptLanguage()
    {
        return [
            ['', ['*'], ['*']],
            ['de,en-US;q=0.9,en;q=0.8,fr,', ['de', 'fr', 'en-US', 'en'], ['de', 'fr', 'en']],
            ['de,en;q=0.8,en-US;q=0.9,fr', ['de', 'fr', 'en-US', 'en'], ['de', 'fr', 'en']],
            ['de,en;q=0.8,en-US;q=0.9,fr;q=abc', ['de', 'en-US', 'en', 'fr'], ['de', 'en', 'fr']],
        ];
    }

    /**
     * @dataProvider acceptLanguage
     */
    public function test_parses_correctly($header, $expectedLanguages, $expectedLocales)
    {
        $this->assertEquals($expectedLocales, (new AcceptLanguageHeader($header))->getLocales(), $header);
        $this->assertEquals($expectedLanguages, (new AcceptLanguageHeader($header))->getLanguages(), $header);
    }
}
