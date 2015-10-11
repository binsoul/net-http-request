<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Role\NetworkRole;

class NetworkRoleImplementation extends NetworkRole {

}

class NetworkRoleTest extends \PHPUnit_Framework_TestCase
{
    public function test_getIP_returns_loopback_as_default()
    {
        $this->assertEquals('127.0.0.1', (new NetworkRoleImplementation(''))->getIP());
        $this->assertEquals('127.0.0.1', (new NetworkRoleImplementation('abc'))->getIP());
    }

    public function test_getPort_returns_correctValue()
    {
        $this->assertEquals(1234, (new NetworkRoleImplementation('127.0.0.1', 1234))->getPort());
    }

    public function test_getPort_returns_null_as_default()
    {
        $this->assertNull((new NetworkRoleImplementation('127.0.0.1'))->getPort());
    }
}
