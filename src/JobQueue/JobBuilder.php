<?php

namespace JobQueue;


abstract class JobBuilder {

    private $block = null;
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
        ];
    }

    public function setBlock($key) {

        $this->block = $key;
        return $this;
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