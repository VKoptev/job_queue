<?php
namespace JobQueue;


/**
 * Class Options
 * @package JobQueue
 * Options:
 * mongo - mongo database connection
 * worker_cmd - command for worker running
 * worker_max_count - (optional) max count of run workers; default: 10
 * log - (optional) log method
 * hovering_timeout - (optional) time of reuse "in process" jobs
 * job_types - (optional) array of key-value pairs; key - job int type; value - job class
 */
class Options {

    /**
     * @var Options
     */
    static private $instance = null;

    private $options = [];

    private function __construct($option) {

        $this->options = $option;
    }

    /**
     * Get JobQueue options
     * @param array $options
     * @return Options
     */
    static public function getInstance($options = []) {

        if (empty(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * Get option
     * @param string $key Key of options
     * @param mixed $default Default value
     * @return mixed
     */
    public function get($key, $default = null) {

        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    public function setOptions($options) {

        $this->options = $options;
    }
}