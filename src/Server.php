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
     * @var resource
     */
    private $proc;

    /**
     * @var array
     */
    private $pipes = array();

    /**
     * @param $binPath
     * @param int $port
     */
    public function __construct($binPath, $port = 8888)
    {
        $this->binPath = $binPath;
        $this->port = $port;
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

        $desc = array (1 => array("file", sys_get_temp_dir().'/http-testcase.log', "w"));

        $this->proc = proc_open(
            sprintf("%s/http-playback --port %s 2>&1", $this->binPath, $this->port),
            $desc,
            $this->pipes
        );

        foreach ($this->pipes as $pipe) {
            stream_set_blocking($pipe, 0);
        }

        //ensure server has time to startup
        usleep(1000 * 100);

        if (!$this->isRunning()) {
            throw new \RuntimeException("HTTP server failed to start. Output follows:\n".$this->getOutput());
        }
    }

    /**
     * Kill this server
     */
    public function stop()
    {
        if (!is_resource($this->proc)) {
            return;
        }

        $status = proc_get_status($this->proc);
        if ($status['running'] == true) {

            //close all pipes that are still open
            foreach($this->pipes as $pipe) {
                fclose($pipe);
            }

            //get the parent pid of the process we want to kill
            $ppid = $status['pid'];

            if (!$ppid) {
                throw new \RuntimeException('Unknown server PID');
            }

            //use ps to get all the children of this process, and kill them
            $pids = preg_split('/\s+/', `ps -o pid --no-heading --ppid $ppid`);

            foreach ($pids as $pid) {
                if (is_numeric($pid)) {
                    exec("kill -9 $pid 2>&1 >/dev/null");
                }
            }
            proc_close($this->proc);
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
     * @throws \RuntimeException
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
        $msg = curl_exec($ch);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
            throw new \RuntimeException('Failed to enqueue response.'.($this->isRunning() ? '' : ' Server is not running!').'. Response was: '.$msg);
        }
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

    /**
     * Get the output of the webserver process
     *
     * @return string
     */
    public function getOutput()
    {
        return file_get_contents(sys_get_temp_dir().'/http-testcase.log');
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        if (!is_resource($this->proc)) {
            return false;
        }
        $status = proc_get_status($this->proc);
        return $status['running'];
    }
}
