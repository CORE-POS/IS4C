<?php

namespace COREPOS\Fannie\API\jobs;
use \FannieConfig;
use \FannieLogger;
use \COREPOS\common\ErrorHandler;
use \Exception;

class QueueManager
{
    /**
     * QueueManager has been launched via start() method
     */
    private $running = false;

    /**
     * Get a connection to Redis server
     */
    private function redisConnect()
    {
        $conf = FannieConfig::config('PLUGIN_SETTINGS');
        $redis_host = isset($conf['SatelliteRedis']) ? $conf['SatelliteRedis'] : '127.0.0.1';
        $this->log("Connecting to Redis {$redis_host}");
        try {
            $redis = new \Predis\Client($redis_host);
            return $redis;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Queue a job
     * @param $json array describing job with keys
     *  - class [required] name of Job class 
     *  - data [optional] data to pass into Job object
     * @param $highPriority [boolean]
     * @return [boolean] success
     */
    public function add($json, $highPriority=false)
    {
        $redis = $this->redisConnect();
        if ($redis !== false) {
            try {
                $queue = $highPriority ? 'jobHigh' : 'jobLow';
                $this->log("Adding job: " . $json['class']);
                $redis->lpush($queue, json_encode($json));
                return true;
            } catch (Exception $ex) {
                $this->log($ex->getMessage());
            }
        }

        return false;
    }

    /**
     * Unpack queued job info and run it
     * @param $json [string]
     */
    private function runJob($json)
    {
        $spec = json_decode($json, true);
        if ($spec === null || !is_array($spec)) {
            return;
        }
        if (!isset($spec['class']) || !class_exists($spec['class'])) {
            return;
        }
        $class = $spec['class'];
        $this->log("Starting job: " . $class);
        $job = new $class(isset($spec['data']) ? $spec['data'] : array());

        if (method_exists($job, 'run')) {
            $job->run();
        }
        $this->log("Finished job: " . $class);
    }

    /**
     * Run all jobs
     * @param $redis connection to Redis
     *
     * Runs all high priority jobs, then all low priority jobs,
     * then blocks on the high priority queue. After the blocking
     * pop the process starts over. The blocking call is a rate
     * limiter to avoid constant polling.
     */
    private function runJobs($redis)
    {
        while (($json = $redis->rpop('jobHigh')) !== null) {
            $this->runJob($json);
        }
        while (($json = $redis->rpop('jobLow')) !== null) {
            $this->runJob($json);
        }

        $json = $redis->brpop('jobHigh', 10);
        if ($json !== null) {
            $this->runJob($json);
        }

        return;
    }

    private function log($msg)
    {
        if ($this->running) {
            echo $msg ."\n";
        }
    }

    public function start()
    {
        $this->running = true;
        $redis = $this->redisConnect();
        while (true) {
            try {
                if ($redis === false) {
                    throw new Exception('Not connected');
                }

                $this->runJobs($redis);

            } catch (Exception $ex) {
                $this->log($ex->getMessage());
                if ($redis === false || !$redis->isConnected()) {
                    sleep(5);
                    $this->redisConnect();
                }
            }
        }
    }
}

if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../config.php');
    include(__DIR__ . '/../FannieAPI.php');
}
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // run

    $logger = FannieLogger::factory();
    ErrorHandler::setLogger($logger);
    ErrorHandler::setErrorHandlers();

    $qm = new QueueManager();
    $qm->start();
}

