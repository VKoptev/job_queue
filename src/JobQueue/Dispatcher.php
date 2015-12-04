<?php
namespace JobQueue;


/**
 * Class Dispatcher
 * @package JobQueue
 */
class Dispatcher {

    use MongoTrait, LogTrait;

    const WORKERS_MAX = 10;
    const HOVERING_TIMEOUT = 60; // in sec

    private $cmd = '';
    private $run = true;
    private $workersCount = 0;
    private $workers = [];
    private $ignoreJobs = [];

    /**
     * max count of run workers
     * @var int
     */
    private $workerMax = self::WORKERS_MAX;

    /**
     * Hovering timeout
     * @var int
     */
    private $timeout = self::HOVERING_TIMEOUT;

    /**
     * Logging method
     * @var callable
     */
    private $log = null;

    public function __construct() {

        $this->mongoInit();
        $this->cmd   = Options::getInstance()->get('worker_cmd');

        if (empty($this->cmd)) {
            throw new Exception('Bad worker command options');
        }

        $this->workerMax = Options::getInstance()->get('worker_max_count', self::WORKERS_MAX);
        $this->log = Options::getInstance()->get('log', function(){ /*do nothing*/ });
        $this->timeout = intval(Options::getInstance()->get('hovering_timeout', self::HOVERING_TIMEOUT));
    }

    public function run() {

        pcntl_signal(SIGTERM, [$this, 'terminate']);

        foreach ($this->job() as $jobId) {
            if ($this->workersCount < $this->workerMax) {
                $this->doWork($jobId);
            }
        }
    }

    public function terminate($signo) {

        $this->run = false;
    }

    /**
     * Generator
     */
    private function job() {

        while ($this->run) {
            $this->checkWorkers();
            foreach ($this->getNewJob() as $jobId) {
                yield $jobId;
            }
            pcntl_signal_dispatch();
            sleep(1);
        }
        $this->killWorkers();
    }

    private function checkWorkers() {

        foreach ($this->workers as $i => $worker) {

            $status = proc_get_status($worker->resource);
            if (!$status['running']) {
                $this->remProcess($i, $worker);
                $this->log("proc {$worker->jobId} {$status['pid']} close");
            }
        }
    }

    private function killWorkers() {

        foreach ($this->workers as $i => $worker) {
            $this->remProcess($i, $worker, true);
        }
    }

    private function doWork($jobId) {

        $worker = (object)[
            'cmd' => $this->cmd . ' ' . $jobId,
            'descriptorspec' => [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["file","/dev/null", "w"]
            ],
            'pipe' => [],
            'resource' => null,
            'jobId' => $jobId,
        ];

        $worker->resource = proc_open($worker->cmd, $worker->descriptorspec, $worker->pipe);
        if (is_resource($worker->resource)) {
            $this->ignoreJobs[(string)$jobId] = $jobId;
            $this->workers[] = $worker;
            $this->workersCount++;
            $status = proc_get_status($worker->resource);
            $this->log("proc $jobId {$status['pid']} open");
        }
    }

    private function remProcess($i, $worker, $terminate = false) {

        fclose($worker->pipe[0]);
        fclose($worker->pipe[1]);
        if ($terminate) {
            proc_terminate($worker->resource, SIGKILL);
        } else {
            proc_close($worker->resource);
        }
        unset($this->workers[$i], $this->ignoreJobs[(string)$worker->jobId]);
        $this->workersCount--;
    }

    private function getNewJob() {

        $condition = ['_id' => ['$nin' => array_values($this->ignoreJobs) ?: []]];
        if ($this->timeout > 0) {
            $condition['$or'] = [
                ['status' => JobBase::STATUS_NEW],
                [
                    'status'  => JobBase::STATUS_IN_PROCESS,
                    'updated' => ['$lte' => new \MongoDate(time() - $this->timeout)]
                ]
            ];
        } else {
            $condition['status'] = JobBase::STATUS_NEW;
        }

        $job = $this->collection()->find($condition, ['_id' => 1])->limit($this->workerMax);
        $result = [];
        foreach ($job as $doc) {
            $result[] = $doc['_id'];
        }
        return $result;
    }

    protected function log() {

        if (!empty($this->log) && is_callable($this->log)) {
            call_user_func_array($this->log, func_get_args());
        }
    }
}