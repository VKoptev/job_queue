<?php

namespace JobQueue;


/**
 * Class JobBase
 * @package App\Queue
 * @property int type
 * @property int status
 * @property bool rerun
 * @property \MongoDate start
 * @property \MongoDate created
 * @property \MongoDate updated
 * @property array data
 */
abstract class JobBase {

    use MongoTrait;

    const STATUS_NEW        = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_SUCCESS    = 2;
    const STATUS_ERROR      = 3;
    const STATUS_BLOCKED    = 4;

    protected $maxStepOfRerun = 10;

    private $internalData = [];

    public function __construct($data) {

        $this->mongoInit();
        // fill this
        $this->internalData = $data;
    }

    public function run() {

        $this->startJob();
        $this->execute();
    }

    public function __get($name) {

        return array_key_exists($name, $this->internalData) ? $this->internalData[$name] : null;
    }

    public function __isset($name) {

        return array_key_exists($name, $this->internalData);
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

        if ($this->rerun) {
            $this->rerun();
        }
        $this->processJob(self::STATUS_ERROR, $result);
    }

    /**
     * Rerun current job
     */
    protected function rerun() {

        $rerunNextStep = empty($this->data['rerunStep']) ? 1 : intval($this->data['rerunStep']) + 1;

        if($rerunNextStep <= $this->maxStepOfRerun) {
            JobFabric::getInstance()->createJob(
                (new TypedBuilder())
                    ->setType($this->type)
                    ->setData($this->data)
                    ->setRerunStep($rerunNextStep)
                    ->setStart(time() + $this->getFibonacciDelay($rerunNextStep))
                    ->setOriginal($this->_id)
            );
        }
    }

    /**
     * @param int $n
     * @return int
     */
    private function getFibonacciDelay($n){

        $n = intval($n);

        $a = 1; $b = 1;
        for ($i = 3; $i <= min($n, $this->maxStepOfRerun); $i++) {
            $b = $a + $b;
            $a = $b - $a;
        }
        return $b;
    }
}