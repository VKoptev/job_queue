<?php

namespace JobQueue;

class JobFabric {

    use MongoTrait;

    /**
     * @var JobFabric
     */
    static private $instance = null;

    private $types = [];

    /**
     * @return JobFabric
     */
    static public function getInstance() {

        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct() {

        $this->mongoInit();
        $this->types = Options::getInstance()->get('job_types', []);
    }

    /**
     * @param $jobId
     * @return JobBase
     */
    public function getJob($jobId) {

        if (
            !\MongoId::isValid($jobId) ||
            !($data = $this->collection()->findOne(['_id' => new \MongoId($jobId)])) ||
            !($class = $this->getClass($data['type']))
        ) {
            return null;
        }

        return new $class($data);
    }

    public function createJob(JobBuilder $builder) {

        $doc = array_merge($builder->getJob(), [
            'created' => new \MongoDate(),
            'updated' => new \MongoDate(),
        ]);
        if (!empty($doc['start'])) {
            $doc['start'] = new \MongoDate($doc['start']);
        }
        $this->collection()->save($doc);
    }

    private function getClass($type) {

        $type = intval($type);
        return isset($this->types[$type]) ? $this->types[$type] : null;
    }
}