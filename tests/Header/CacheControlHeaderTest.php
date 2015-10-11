<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Header\CacheControlHeader;

class CacheControlHeaderTest extends \PHPUnit_Framework_TestCase
{
    public function test_parses_single_values()
    {
        $this->assertTrue((new CacheControlHeader('no-cache', ''))->isReload());
        $this->assertTrue((new CacheControlHeader('', 'no-cache'))->isReload());
        $this->assertTrue((new CacheControlHeader('no-cache', 'no-cache'))->isReload());
    }

    public function test_parses_multiple_values()
    {
        $this->assertTrue((new CacheControlHeader('no-cache, max-age=0', ''))->isReload());
        $this->assertTrue((new CacheControlHeader('Max-Age=0, No-Cache', 'no-cache'))->isReload());
    }

    public function test_pragma_is_ignored_if_cache_control_present()
    {
        $this->assertTrue((new CacheControlHeader('max-age=0', 'no-cache'))->isRefresh());
        $this->assertEquals(100, (new CacheControlHeader('max-age=100', 'no-cache'))->getMaxAge());
    }

    public function test_maxAge_returns_large_value_if_not_set()
    {
        $this->assertTrue((new CacheControlHeader('', ''))->getMaxAge() > 365 * 24 * 60 * 60 * 60);
    }

    public function test_maxAge_returns_zero_if_nocache()
    {
        $this->assertTrue((new CacheControlHeader('no-cache', ''))->hasMaxAge());
        $this->assertEquals(0, (new CacheControlHeader('no-cache', ''))->getMaxAge());
        $this->assertEquals(0, (new CacheControlHeader('no-cache, max-age=100', ''))->getMaxAge());
    }

    public function test_hasMaxAge_returns_false_if_not_set()
    {
        $this->assertFalse((new CacheControlHeader('', ''))->hasMaxAge());
        $this->assertFalse((new CacheControlHeader('abc', ''))->hasMaxAge());
    }
}
