<?php
namespace HttpTestCase;

class Server
{
    /**
     * @var string
     */
    private $binPath;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $serverLogPath;

    /**
     * @var int
     */
    private $pid;

    /**
     * @param $binPath
     * @param int $port
     * @param string $serverLogPath
     */
    public function __construct($binPath, $port = 8888, $serverLogPath = '/dev/null')
    {
        $this->binPath = $binPath;
        $this->port = $port;
        $this->serverLogPath = $serverLogPath;
    }

    /**
     * Start the webserver
     *
     * @throws \RuntimeException
     */
    public function start()
    {
        // Kill the web server when the process ends if not explicitly stopped
        register_shutdown_function(array($this, 'stop'));

        // Command that starts the web server
        $command = sprintf('%s/http-playback --port %s >%s 2>&1 & echo $!', $this->binPath, $this->port, $this->serverLogPath);

        // Execute the command and store the process ID
        $output = array();
        $status = 0;
        exec($command, $output, $status);
        if ($status != 0) {
            throw new \RuntimeException('HTTP Server failed to start');
        }

        $this->pid = (int)$output[0];

        //wait for server to start... is there a better way?
        usleep(1000*100);
    }

    /**
     * Kill this server
     */
    public function stop()
    {
        if ($this->pid) {
            passthru("kill {$this->pid} >/dev/null 2>&1");
        }
    }

    /**
     * Add response to server session.
     *
     * @param string $session
     * @param int $status
     * @param string $body
     * @param array $headers
     * @param int $wait
     */
    public function enqueue($session, $status = 200, $body = "", $headers = array(), $wait = 0)
    {
        $req = array(
            "Status" => $status,
            "Body" => $body,
            "Wait" => $wait
        );

        if ($headers) {
            $req["Headers"] = $headers;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:{$this->port}/r/$session");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req));
        curl_exec($ch);
    }

    /**
     * Get the uri to replay a session
     *
     * @param string $session
     * @param string $path
     * @return string
     */
    public function getReplayUri($session, $path = '')
    {
        return 'http://localhost:'.$this->port.'/p/'.$session.'/'.$path;
    }
}
