<?php

require 'vendor/autoload.php';

use Phacterl\Scheduler;

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

class Counter extends Actor {

    protected $state;

    public function init($args) {
        return array('count' => 0);
    }

    public function receive() {
        return array('incr', 'decr', 'get_count');
    }

    public function handle_incr($msg, $state) {
        echo "handle_incr: ", $msg, "\n";
        $state['count'] += $msg;
        return $state;
    }

    public function handle_decr($msg, $state) {
        echo "handle_decr: ", $msg, "\n";
        $state['count'] -= $msg;
        return $state;
    }

    public function handle_get_count($msg, $state) {
        echo "handle_get_count: ", $msg['sender'], "\n";
        $this->runtime->send($msg['sender'], new Message('count', $state['count']));
        return $state;
    }
}

class CounterClient extends Actor {

    public function init($args) {
        $this->runtime->send($args['count_server'], new Message('incr', rand(1, 10)));
        $this->runtime->send($args['count_server'], new Message('incr', rand(1, 10)));
        $this->runtime->send($args['count_server'], new Message('decr', rand(1, 10)));
        $this->runtime->send($args['count_server'], new Message('get_count', array('sender' => $this->self())));
        return array('server' => $args['count_server']);
    }

    public function receive() {
        return array('count');
    }

    public function handle_count($msg, $state){
        echo sprintf("%s: got count %d\n", $this->self(), $msg);
        sleep(2);
        $next = rand(0, 2);
        switch ($next) {
        case 0:
            $this->runtime->send($state['server'], new Message('incr', rand(1, 20)));
            break;
        case 1:
            $this->runtime->send($state['server'], new Message('decr', rand(1, 20)));
            break;
        case 2:
            $this->runtime->send($state['server'], new Message('get_count', array('sender' => $this->self())));
            break;
        }
        return $state;
    }
}

$scheduler = new Scheduler();
$counter = $scheduler->spawn('Counter', array());
$scheduler->spawn('CounterClient', array('count_server' => $counter));
$scheduler->spawn('CounterClient', array('count_server' => $counter));
$scheduler->spawn('CounterClient', array('count_server' => $counter));

$scheduler->run();