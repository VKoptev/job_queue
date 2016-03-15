<?php

namespace JobQueue;


/**
 * Class JobBase
 * @package App\Queue
 * @property int type
 * @property int status
 * @property array data
 */
abstract class JobBase {

    use MongoTrait;

    const STATUS_NEW        = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_SUCCESS    = 2;
    const STATUS_ERROR      = 3;
    const STATUS_BLOCKED    = 4;

    const MAX_STEP_RERUN = 10;

    private $data = [];

    public function __construct($data) {

        $this->mongoInit();
        // fill this
        $this->data = $data;
    }

    public function run() {

        $this->startJob();
        $this->execute();
    }

    public function __get($name) {

        return array_key_exists($name, $this->data) ? $this->data[$name] : null;
    }

    public function __isset($name) {

        return array_key_exists($name, $this->data);
    }

    abstract protected function execute();

    protected function processJob($status = self::STATUS_IN_PROCESS, $result = []) {

        $this->collection()->update(['_id' => new \MongoId($this->_id)], ['$set' => ['status' => $status, 'updated' => new \MongoDate(), 'result' => $result]]);
    }

    protected function startJob($result = []) {

        if ($this->block) {
            if ($this->collection()->count(['_id' => ['$ne' => new \MongoId($this->_id)], 'block' => $this->block]) > 0) {
                $this->processJob(self::STATUS_BLOCKED, $result);
                throw new JobBlockedException();
            }
        }
        $this->processJob();
    }

    protected function finishJob($result = []) {

        $this->processJob(self::STATUS_SUCCESS, $result);
    }

    protected function failJob($result = []) {

        $job = $this->data;

        if(!empty($job['rerun'])){
            $rerunNextStep = empty($job['rerun_step']) ? 1 : intval($job['rerun_step']) + 1;

            if($rerunNextStep <= self::MAX_STEP_RERUN){
                $job['rerun_step'] = $rerunNextStep;
                $job['original'] = (string)$job['_id'];
                unset($job['_id']);
                $this->saveJob($job);
            }
        }

        $this->processJob(self::STATUS_ERROR, $result);
    }

    /**
     * @param array $job
     */
    protected function saveJob($job = []){

        $job['created'] = new \MongoDate();
        $job['updated'] = new \MongoDate();
        $job['status'] = self::STATUS_NEW;

        if(!empty($job['rerun_step'])){
            $job['start'] = new \MongoDate(time() + $this->getFibonacciDelay(intval($job['rerun_step'])));
        }

        $this->collection()->save($job);
    }

    /**
     * @param $n
     * @return int|null
     */
    private function getFibonacciDelay($n){
        if($n){

            if(in_array($n, [1,2])){
                return 1;
            }

            $a = 1; $b = 1;
            for ($i = 3; $i <= min($n, self::MAX_STEP_RERUN); $i++) {
                $b = $a + $b;
                $a = $b - $a;

            }
            return $b;
        }

        return null;
    }
}