<?php

namespace Phacterl\Message;

class Message {

    protected $tag;
    protected $data;

    public function __construct($tag, $data) {
        $this->tag = $tag;
        $this->data = $data;
    }

    public function getTag() {
        return $this->tag;
    }

    public function getData() {
        return $this->data;
    }
}
