<?php
namespace HttpTestCase;

class HttpTestCase extends \PHPUnit_Framework_TestCase
{
    public static $servers = array();

    /**
     * @param string $port
     */
    public static function startServer($port)
    {
        self::$servers[$port] = new Server(__DIR__.'/../bin', $port);
        self::$servers[$port]->start();

    }

    /**
     * @param string $port
     * @return Server
     */
    public static function getServer($port)
    {
        return self::$servers[$port];
    }

    public static function stopServers()
    {
        if (count(self::$servers) > 0) {
            while ($server = array_pop(self::$servers)) {
                $server->stop();
            }
        }
    }
}
