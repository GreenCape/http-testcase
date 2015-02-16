HTTP Test Case
==================
PHPUnit test case to assist in testing HTTP clients libraries. The testcase can start a http server and enqueue
a set of responses (a session) which can are replayed for HTTP requests to the server.

HTTP serving is provided by: https://github.com/warmans/http-playback

A test case will look like this:

```php
<?php
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

        $this->assertEquals('bar', $this->sendGet('http://localhost:8081/p/1/anything/is/ok/here'));
        $this->assertEquals('baz', $this->sendGet('http://localhost:8081/p/1/whatever'));
        $this->assertEquals('cat', $this->sendGet('http://localhost:8081/p/1/some/random/path'));
    }
}
```