<?php

namespace BinSoul\Test\Http\Request\Role;

use BinSoul\Net\Http\Request\Role\ClientRole;

class ClientRoleTest extends \PHPUnit_Framework_TestCase
{
    public function headlessIPs()
    {
        return [
            ['54.224.190.177'],
            ['50.16.53.87'],
            ['174.129.80.74'],
            ['54.211.250.38'],
            ['54.237.85.111'],
            ['54.224.139.155'],
            ['50.17.158.18'],
        ];
    }

    /**
     * @dataProvider headlessIPs
     */
    public function test_headless_ips($ip)
    {
        $this->assertTrue((new ClientRole($ip))->isHeadless(), $ip);
    }

    public function regularIPs()
    {
        return [
            [''],
            ['127.0.0.1'],
            ['92.168.1.1'],
            ['54.193.255.255'],
            ['66.249.78.126'],
            ['46.22.33.163'],
            ['212.185.174.122'],
            ['72.14.199.200'],
            ['141.8.147.12'],
            ['157.55.35.43'],
            ['209.172.60.198'],
            ['88.79.122.68'],
        ];
    }

    /**
     * @dataProvider regularIPs
     */
    public function test_regular_ips($ip)
    {
        $this->assertFalse((new ClientRole($ip))->isHeadless(), $ip);
    }
}
