HTTP Test Case
==================
PHPUnit test case to assist in behaviour/integration testing HTTP clients libraries. The testcase can start a http 
server and enqueue a set of responses (a session) which can are replayed for HTTP requests to the server. This is 
roughly similar to the testcase provided by Guzzle but without any external (NPM) dependencies.

The HTTP server is written in golang. The source is available here: https://github.com/warmans/http-playback

## Test Case API

The testcase exposes the following methods: 

#### startServer($port)

Start a server on the given port and return a Server instance.

#### getServer($port)

Get a Server instance for a server previously started using startServer().

#### stopServers()

Tell all running servers to exit. Note that shutdown functions are automatically registered for Server instances
so omitting a tearDown call to stopServers should not leave servers running.

### Server API

Interactions with http playback servers are simplified using the Server instance which is returned from calls to 
startServer() and getServer()

#### start()

Start the server (generally only called by HttpTestCase::startServer).

#### stop()

Stop the server (generally only called by HttpTestCase::startServer).

#### enqueue($session, $status = 200, $body = "", $headers = array(), $wait = 0)

Add a response to a HTTP session. A session is just a named list of responses held within the server. Using 
named sessions allows the tester to simulate different endpoints without requiring exact paths. If you don't care
about segregated response queues for different pages just always use the same session name.

#### getReplayUri($session, $path)

If you've enqueued some responses and just want the full playback URI you can use this method. e.g.

```
$server->enqueue("foo", 200); //setup a session
$server->getReplayUri("foo", "/bar/baz"); // http://localhost:8080/p/foo/bar/baz - will return the configured response
```

#### getOutput()

Get the logfile output from the server as a string. Note that a server log is created in `sys_get_temp_dir().'/http-testcase.log'`

#### isRunning()

Check if the server is currently running.

### Examples

A test case will look like this (this one can be run from the project if dev dependencies are installed root using
`./vendor/bin/phpunit example`)

```php

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

```