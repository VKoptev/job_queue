<?php

namespace JobQueue;


abstract class JobBuilder {

    private $block = null;
    private $rerun = true;
    private $data = [];

    public function __construct() {
    }

    public function __call($method, $args) {

        if (strpos($method, 'set') === 0) {
            $field = lcfirst(substr($method, 3));
            $this->setField($field, array_key_exists(0, $args) ? $args[0] : null);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getJob() {

        return [
            'status' => JobBase::STATUS_NEW,
            'data'   => $this->data,
            'type'   => $this->type(),
            'block'  => $this->block,
            'rerun'  => $this->rerun,
        ];
    }

    public function setBlock($key) {

        $this->block = $key;
        return $this;
    }

    /**
     * @param bool $value
     *
     */
    public function setRerun($value){
        $this->rerun = $value;
    }

    /**
     * @return string
     */
    abstract protected function type();

    /**
     * @param string $key
     * @param mixed $value
     */
    protected function setField($key, $value) {

        $this->data[$key] = $value;
    }
}