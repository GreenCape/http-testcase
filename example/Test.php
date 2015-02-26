<?php
require_once(__DIR__.'/../vendor/autoload.php');

use HttpTestCase\HttpTestCase;

class Test extends HttpTestCase
{
    public static function setUpBeforeClass()
    {
        self::startServer('8081');
    }

    public static function tearDownAfterClass()
    {
        self::stopServers();
    }

    /**
     * Just send a HTTP GET request somewhere.
     */
    protected function sendGet($host)
    {
        $ch = curl_init($host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }

    /**
     * Enqueue and request a single response.
     */
    public function testHttpServerReturnsConfiguredResponse()
    {
        $server = self::getServer('8081');
        $server->enqueue(1, 200, 'bar');

        $this->assertEquals('bar', $this->sendGet('http://localhost:8081/p/1/'));
    }

    /**
     * Enqueue some responses then send some GET requests to the server and assert they were returned in the
     * expected order.
     */
    public function testHttpServerReturnsMultipleResponses()
    {
        $server = self::getServer('8081');
        $server->enqueue(1, 200, 'bar');
        $server->enqueue(1, 200, 'baz');
        $server->enqueue(1, 200, 'cat');

        $this->assertEquals('bar', $this->sendGet('http://localhost:8081/p/1/'));
        $this->assertEquals('baz', $this->sendGet('http://localhost:8081/p/1/'));
        $this->assertEquals('cat', $this->sendGet('http://localhost:8081/p/1/'));
    }

    /**
     * Enqueue 1000 responses then request them all.
     */
    public function testOneWayLoad()
    {
        $requests = 1000;

        //in
        $server = self::getServer('8081');
        for ($i = 0; $i < $requests; $i++) {
            $server->enqueue(1, 200, "foo", array());
        }

        //out
        for ($i = 0; $i < $requests; $i++) {
            if ('foo' !== ($res = $this->sendGet($server->getReplayUri(1)))) {
                $this->fail('bad response: '.$res);
            }
        }
    }

    /**
     * Enqueue and request 1000 responses
     */
    public function testAlternatingLoad()
    {
        $requests = 1000;

        $server = self::getServer('8081');
        for ($i = 0; $i < $requests; $i++) {
            //in
            $server->enqueue(1, 200, "foo", array());
            //out
            if ('foo' !== ($res = $this->sendGet($server->getReplayUri(1)))) {
                $this->fail('bad response: '.$res);
            }
        }
    }
}