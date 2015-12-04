<?php
namespace JobQueue;


class Worker {

    use LogTrait;

    private $jobId = null;

    public function __construct($jobId) {

        $this->jobId = $jobId;
    }

    public function run() {

        $job = JobFabric::getInstance()->getJob($this->jobId);
        if ($job instanceof JobBase) {
            $job->run();
        } else {
            throw new Exception('Bad job id');
        }
    }
}