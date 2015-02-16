<?php
namespace HttpTestCase;

class Server
{
    private $binPath;

    private $port;

    private $serverLogPath;

    private $pid;

    public function __construct($binPath, $port = 8888, $serverLogPath = '/dev/null')
    {
        $this->binPath = $binPath;
        $this->port = $port;
        $this->serverLogPath = $serverLogPath;
    }

    public function start()
    {
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

        // Kill the web server when the process ends if not explicitly stopped
        register_shutdown_function(array($this, 'stop'));

        //wait for server to start... is there a better way?
        usleep(1000*100);
    }

    public function stop()
    {
        system("kill {$this->pid} >/dev/null 2>&1");
    }

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

    public function getReplayUri($session, $path='')
    {
        return 'http://localhost:'.$this->port.'/p/'.$session.'/'.$path;
    }
}
