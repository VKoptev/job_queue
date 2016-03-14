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

        $job = $this->collection()->findOne(['_id' => new \MongoId($this->_id)]);

        if(!empty($job['rerun'])){
            unset($job['_id']);
        }

        $this->processJob(self::STATUS_ERROR, $result);
    }
}