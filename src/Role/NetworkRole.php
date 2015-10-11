<?php

namespace BinSoul\Net\Http\Request\Role;

use BinSoul\Net\IP;

/**
 * Provides information about an participants of the request reachable via a network.
 */
abstract class NetworkRole
{
    /** @var IP */
    protected $ip;
    /** @var int|null */
    protected $port;

    /**
     * Constructs an instance of this class.
     *
     * @param string   $ip   server IP address
     * @param int|null $port server port number
     */
    public function __construct($ip, $port = null)
    {
        if (IP::isValid($ip)) {
            $this->ip = new IP($ip);
        } else {
            $this->ip = new IP('127.0.0.1');
        }
        $this->port = $port;
    }

    /**
     * Returns the IP address.
     *
     * @return IP
     */
    public function getIP()
    {
        return $this->ip;
    }

    /**
     * Returns the port number.
     *
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }
}
