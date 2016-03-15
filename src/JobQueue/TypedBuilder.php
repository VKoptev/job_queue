<?php

namespace JobQueue;


class TypedBuilder extends JobBuilder {

    private $type;

    /**
     * @param array $data
     * @return TypedBuilder
     */
    public function setData(array $data) {

        foreach ($data as $key => $value) {
            $this->setField($key, $value);
        }
        return $this;
    }

    /**
     * @param $type
     * @return TypedBuilder
     */
    public function setType($type) {

        $this->type = $type;
        return $this;
    }

    protected function type() {

        return $this->type;
    }
}