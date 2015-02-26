<?php
namespace HttpTestCase;

class HttpTestCase extends \PHPUnit_Framework_TestCase
{
    public static $servers = array();

    /**
     * Start a webserver on the given port and return a Server instance.
     *
     * @param string $port
     * @return Server
     */
    public static function startServer($port)
    {
        self::$servers[$port] = new Server(__DIR__.'/../bin', $port);
        self::$servers[$port]->start();

        return self::$servers[$port];
    }

    /**
     * Get a Server instance for a webserver previously started using startServer()
     *
     * @param string $port
     * @return Server
     */
    public static function getServer($port)
    {
        return self::$servers[$port];
    }

    /**
     * Tell all running servers to exit. Note that shutdown functions are automatically registered for Server instances
     * so omitting a tearDown call to stopServers should not leave servers running.
     */
    public static function stopServers()
    {
        if (count(self::$servers) > 0) {
            while ($server = array_pop(self::$servers)) {
                $server->stop();
            }
        }
    }
}
