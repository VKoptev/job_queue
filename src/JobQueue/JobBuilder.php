<?php

namespace JobQueue;

/**
 * Class JobBuilder
 * @package JobQueue
 * @method JobBuilder setRerunStep(int $step)
 */
abstract class JobBuilder {

    private $block = null;
    private $rerun = true;
    private $data = [];
    private $start = null;
    private $original = null;
    private $priority = 1;

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
            'status'    => JobBase::STATUS_NEW,
            'data'      => $this->data,
            'type'      => $this->type(),
            'start'     => $this->start,
            'block'     => $this->block,
            'rerun'     => $this->rerun,
            'original'  => $this->original,
            'priority'  => $this->getPriority(),
        ];
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setBlock($key) {

        $this->block = $key;
        return $this;
    }

    /**
     * @param int $start Timestamp
     * @return $this
     */
    public function setStart($start) {

        $this->start = intval($start) ?: null;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setRerun($value){

        $this->rerun = boolval($value);
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setOriginal($value){

        $this->original = $value;
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

    protected function getPriority() {

        return $this->priority;
    }
}
