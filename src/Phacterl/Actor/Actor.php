<?php

namespace Phacterl\Actor;

abstract class Actor {

    protected $id;
    protected $runtime;

    public function setId($id) {
        $this->id = $id;
    }

    public function setScheduler($runtime) {
        $this->runtime = $runtime;
    }

    public function self() {
        return $this->id;
    }

    abstract public function init($args);
    abstract public function receive();
}