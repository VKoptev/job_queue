<?php

namespace JobQueue;

/**
 * Trait MongoTrait
 * @package JobQueue
 */
trait MongoTrait {

    /**
     * @var \MongoDB
     */
    private $mongo = null;
    /**
     * @var string
     */
    private $collection = 'jobs';

    protected function mongoInit($require = true) {

        $this->mongo = Options::getInstance()->get('mongo');
        if ($require && (empty($this->mongo) || !($this->mongo instanceof \MongoDB))) {
            throw new Exception('Bad database options');
        }
        $this->collection = Options::getInstance()->get('mongo_collection', $this->collection);
    }

    /**
     * @return \MongoDB
     */
    protected function mongo() {

        return $this->mongo;
    }

    /**
     * @return \MongoCollection
     */
    protected function collection() {

        return $this->mongo()->{$this->collection};
    }

}