<?php

require_once('colour.php');
require_once('Util.php');

/**
 * A return type of "stdResult" is used for many functions in this class.
 * stdResult = an array or array of arrays consiting of two parameters, the first one being a success
 *             boolean, and the second being a message.
 */

class StratumServer {
    private $conn = false;
    private $current_id = 1;
    private $commands = array();
    private $difficulty = false;
    private $jobs = 0;
    private $worker_name = false;
    private $extranonce1 = "";
    private $extranonce2 = "16b9769a";
    private $extranonce2_size = 4;
    private $job = false;
    private $current_jobs = array();
    private $num_sleeps = 20;

    private $connected = false;

    const JOB_FILE = '/full/path/to/where/StratumServer/writes/jobdata/j.txt';

    /**
     * Connect to the stratum server.
     * @param string $host
     * @param string $port
     * @return array stdResult
     */
    public function connect($host, $port = "3333") {
        $connect_string = "tcp://{$host}:{$port}";

        if(($this->conn = stream_socket_client($connect_string, $errno, $errstr)) === false) {
            return array(false, "Unable to connect to {$host}:{$port} [$errstr]");
        }

        $this->connected = true;
        socket_set_blocking($this->conn, false);
        return array(true, "Connected to {$host}:{$port}");
    }

    /**
     * Are we connected to stratum?
     * @return boolean
     */
    public function connected() {
        return $this->connected;
    }

    /**
     * Read any pending messages from server and process them.
     * @param int $wait How long to wait for any messages in seconds
     * @return array stdResult
     */
    private function read() {
        if(!$this->conn) {
            return array(false, "Connection not established");
        }

        $results = array();
        while(($message = fgets($this->conn))) {
            $results[] = $this->processResponse($message);
        }

        if(count($results) == 0) {
            return array(false, "Nothing to read from server");
        } else {
            return $results;
        }
    }

    /**
     * Write a message to the server.
     * @param string $message
     * @return array stdResult
     */
    private function write($message) {
        if(!$this->conn) {
            return array(false, "Connection not established");
        }

        if((fwrite($this->conn, $message."\n")) === false) {
            $this->connected = false;
            return array(false, B_RED."Write failed, lost connection?".CLEAR);
        }
        
        return array(true, WHITE."Wrote: ".CLEAR.$message);
    }

    /**
     * Parse the Stratum protocol error message and return the string portion.
     * @param array $error
     * @return string
     */
    private function serverErrorMessage($error) {
        if(!is_array($error)) {
            return "Error format unknown";
        }
        return $error[1];
    }

    /**
     * Process a string response (json encoded) from a Stratum server.
     * @param string $message
     * @return array stdResult
     */
    private function processResponse($message) {
        if(($response = json_decode($message)) === false) {
            return array(false, "Invalid response from server: {$message}");
        }
        
        if(isset($response->error) && !is_null($response->error)) {
            return array(false, $this->serverErrorMessage($response->error));
        }

        if(isset($response->result) && !is_null($response->result)) {
            if(isset($response->id) && isset($this->commands[$response->id]) && isset($response->result)) {
                switch($this->commands[$response->id]) {
                    case 'mining.subscribe':
                        $this->extranonce1 = $response->result[1];
                        $this->extranonce2_size = $response->result[2];
                        $this->extranonce2 = substr(md5(rand()), 0, $this->extranonce2_size * 2);
                        return array(true, WHITE."Subscribed! ".CLEAR."extranonce1={$this->extranonce1} extranonce2={$this->extranonce2} extranonce2_size={$this->extranonce2_size}");
                }
            }
            return array(true, WHITE."Response: ".CLEAR.$message);
        }

        if(isset($response->method) && !is_null($response->method)) {
            switch($response->method) {
                case 'mining.set_difficulty':
                    $this->difficulty = $response->params[0];
                    return array(true, WHITE."Difficulty: ".CLEAR.$this->difficulty);
                case 'mining.notify':
                    $this->jobs++;

                    // miner is doing all the work, needs the hex job data
                    $job = array(
                        'job_id'        => $response->params[0],
                        'prevhash'      => $response->params[1],
                        'coinb1'        => $response->params[2],
                        'coinb2'        => $response->params[3],
                        'merkle_branch' => $response->params[4],
                        'version'       => $response->params[5],
                        'nbits'         => $response->params[6],
                        'ntime'         => $response->params[7],
                        'clean_jobs'    => $response->params[8],
                        'extranonce1'   => $this->extranonce1,
                        'extranonce2'   => $this->extranonce2,
                        'extranonce2_size' => $this->extranonce2_size,
                        'difficulty'    => $this->difficulty,
                    );
                    $this->job = $job;

                    // keep list of active jobs, so we can submit to previous ones if clean_jobs remains false
                    if($job['clean_jobs']) {
                        $this->current_jobs = array();
                    }
                    $this->current_jobs[] = $job;

                    $this->refreshWorkerData();

                    return array(true, WHITE."Job: ".CLEAR.$job['job_id'].' [refresh:'.($response->params[8] ? 'true' : 'false').']');
            }
            return array(true, WHITE."Message: ".CLEAR.$message);
        }

        return array(false, "Unknown how to handle message: {$message}");
    }

    /**
     * Write to the publicly accessible file for workers to grab.
     * @return array stdResult
     */
    private function refreshWorkerData() {
        if(!$this->job) {
            return array(false, 'No job found!');
        }

        if(($fp = fopen(self::JOB_FILE,'w+')) === false) {
            return array(false, "Can't open job file!");
        }

        $data = "{$this->job['job_id']},{$this->job['prevhash']},{$this->job['coinb1']},{$this->job['coinb2']},".
                "{$this->job['version']},{$this->job['nbits']},{$this->job['ntime']},".
                "{$this->job['extranonce1']},{$this->job['extranonce2_size']},{$this->job['difficulty']}\n".
                json_encode($this->job['merkle_branch']);
        
        fwrite($fp, $data);
        fclose($fp);
    }

    /**
     * Build a Stratum protocol message.
     * @param string $command ex:mining.authorize
     * @param array $params
     * @return string json_encoded
     */
    public function buildMessage($command, array $params = array()) {
        $message = array(
            'id' => $this->current_id++,
            'method' => $command,
            'params' => $params,
        );

        // for processing specific results later as they may come in unknown order
        $this->commands[$message['id']] = $command;

        return json_encode($message);
    }

    /**
     * Do a subscribe request to the Stratum server.
     * @return array stdResult
     */
    public function subscribe() {
        $message = $this->buildMessage('mining.subscribe');
        return $this->write($message);
    }

    /**
     * Do an authorize request to the Stratum server (register a worker).
     * @param string $worker_name
     * @param string $pass
     * @return array stdResult
     */
    public function authorize($worker_name, $pass) {
        $this->worker_name = $worker_name;

        $message = $this->buildMessage('mining.authorize', array(
            $this->worker_name,
            $pass
        ));
        
        return $this->write($message);
    }

    /**
     * Submit work to Stratum server.
     * @param string $job_id
     * @param string $extranonce2
     * @param string $ntime
     * @param string $nonce
     * @return array stdResult
     */
    public function submit($job_id, $extranonce2, $ntime, $nonce) {
        if(!$this->worker_name) {
            return array(false, "No worker authorized!");
        }
        
        $message = $this->buildMessage('mining.submit', array(
            $this->worker_name,
            $job_id,
            $extranonce2,
            $ntime,
            $nonce
        ));

        return $this->write($message);
    }

    /**
     * Check if a job exists so we can submit work to it.
     * @param string $job_id
     * @return boolean
     */
    public function jobExists($job_id) {
        if(count($this->current_jobs)) {
            foreach($this->current_jobs as $job) {
                if($job['job_id'] == $job_id) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if the work file has actual work in it, and submit it.
     * @param string $work_filename
     */
    public function checkWorkFile($work_filename) {
        if(file_exists($work_filename) && ($data = file_get_contents($work_filename)) !== false) {
            $strings = explode("\n",$data);
            $results = array();
            foreach($strings as $string) {
                $data = json_decode($string);

                if(isset($data->job_id) && isset($data->nonce)) {
                    if($this->jobExists($data->job_id)) {
                        if(strlen($data->extranonce2) != $this->extranonce2_size * 2) {
                            $results[] = array(false, B_MAGENTA.'Extranonce2 is not the correct size'.CLEAR);
                        } else {
                            $results[] = $this->submit($data->job_id, $data->extranonce2, $data->ntime, $data->nonce);

                            if(isset($data->relative_difficulty)) {
                                $results[] = array(true, "Relative difficulty: ".$data->relative_difficulty);
                            }
                        }
                    } else {
                        $results[] = array(false, "Found work to submit, but job is too old or doesn't exist");
                    }
                }
            }
            unlink($work_filename);
            
            return count($results) ? $results : 0;
        }

        return false;
    }

    /**
     * Check for a "work file", which is created by a worker who has found an acceptible solution.  If found,
     * submit work to stratum for consideration.
     * Then attempt to read from stratum server connection to get any pending messages.
     * @param int $tries
     * @param int $sleep
     * @param string $work_filename full path to work file where this server is running
     * @return array stdResult
     */
    public function poll($tries = 1, $sleep = 1, $work_filename = false) {
        for($i = 0; $i < $tries; $i++) {

            for($count = 0; $count < $this->num_sleeps; $count++) {
                // want to check for work file quite often
                if($work_filename) {
                    $result = $this->checkWorkFile($work_filename);
                    if(is_array($result)) {
                        return $result;
                    }
                }

                // no sleeping the first time through
                if($i == 0) {
                    break;
                }

                usleep(1000000 / $this->num_sleeps);
            }

            $results = $this->read();
            if(count($results) > 0 && is_array($results[0])) {
                return $results;
            }
        }
        return array(true, "Poll: Nothing to read from server after {$tries} sleeping {$sleep} seconds inbetween");
    }
}
