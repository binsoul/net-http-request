<?php

namespace BinSoul\Net\Http\Request\Role;

/**
 * Provides information about the client of the request.
 */
class ClientRole extends NetworkRole
{
    /**
     * list of known cloud provider IP addresses.
     *
     * @var int[][]
     */
    private static $clouds = [
        // Amazon
        ['start' => 387186688, 'end' => 387448831], // 23.20.0.0 - 23.23.255.255
        ['start' => 780730368, 'end' => 780795903], // 46.137.0.0 - 46.137.255.255
        ['start' => 775127040, 'end' => 775147519], // 46.51.128.0 - 46.51.207.255
        ['start' => 775149568, 'end' => 775159807], // 46.51.216.0 - 46.51.255.255
        ['start' => 839909376, 'end' => 840171519], // 50.16.0.0 - 50.19.255.255
        ['start' => 846200832, 'end' => 846266367], // 50.112.0.0 - 50.112.255.255
        ['start' => 918683648, 'end' => 920518655], // 54.194.0.0 - 54.221.255.255
        ['start' => 920649728, 'end' => 921042943], // 54.224.0.0 - 54.229.255.255
        ['start' => 921174016, 'end' => 921255935], // 54.232.0.0 - 54.233.63.255
        ['start' => 921305088, 'end' => 921632767], // 54.234.0.0 - 54.238.255.255
        ['start' => 921763840, 'end' => 922746879], // 54.241.0.0 - 54.255.255.255
        ['start' => 1137311744, 'end' => 1137328127], // 67.202.0.0 - 67.202.63.255
        ['start' => 1264943104, 'end' => 1264975871], // 75.101.128.0 - 75.101.255.255
        ['start' => 1333592064, 'end' => 1333624831], // 79.125.0.0 - 79.125.127.255
        ['start' => 1618935808, 'end' => 1618952191], // 96.127.0.0 - 96.127.63.255
        ['start' => 2927689728, 'end' => 2927755263], // 174.129.0.0 - 174.129.255.255
        ['start' => 2938748928, 'end' => 2938765311], // 175.41.192.0 - 175.41.255.255
        ['start' => 2954903552, 'end' => 2954911743], // 176.32.64.0 - 176.32.95.255
        ['start' => 2955018240, 'end' => 2955083775], // 176.34.0.0 - 176.34.255.255
        ['start' => 2974253056, 'end' => 2974285823], // 177.71.128.0 - 177.71.255.255
        ['start' => 3098116096, 'end' => 3098148863], // 184.169.128.0 - 184.169.255.255
        ['start' => 3091726336, 'end' => 3091857407], // 184.72.0.0 - 184.73.255.255
        ['start' => 3438051328, 'end' => 3438084095], // 204.236.128.0 - 204.236.255.255

        // Gandi
        ['start' => 1559429120, 'end' => 1559437311], // 92.243.0.0 - 92.243.31.255
    ];

    /**
     * Return whether the client is known to be another server.
     *
     * Sometimes the User-Agent header doesn't provide enough information about the client to
     * identify him as another server. In this case you can use this method to check if the client request comes
     * from an IP address which is known to be a server.
     *
     * @return bool
     */
    public function isHeadless()
    {
        $ip = sprintf('%u', ip2long($this->ip));

        foreach (self::$clouds as $range) {
            if ($ip >= $range['start'] && $ip <= $range['end']) {
                return true;
            }
        }

        return false;
    }
}
