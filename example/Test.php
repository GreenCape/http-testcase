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

    protected function sendGet($host)
    {
        $ch = curl_init($host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return curl_exec($ch);
    }

    public function testHttpServerReturnsConfiguredResponse()
    {
        $server = self::getServer('8081');
        $server->enqueue(1, 200, 'bar');

        $this->assertEquals('bar', $this->sendGet('http://localhost:8081/p/1/'));
    }

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
}
