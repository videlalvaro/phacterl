<?php

require __DIR__ . '/../vendor/autoload.php';

use Phacterl\Runtime\Scheduler;
use Phacterl\Actor\Actor;
use Phacterl\Message\Message;

class TNode extends Actor {

    public function init($args) {
        return array(
            'name' => $args[0],
            'parent' => null,
            'neighbors' => array(),
            'expected_msg' => 0,
            'children' => array(),
            'value' => null
        );
    }

    public function receive() {
        return array('neighbors', 'start', 'go', 'back');
    }

    public function handle_start($msg, $state) {
        $state['parent'] = $this->self();
        $state['children'] = array();
        $state['expected_msg'] = count($state['neighbors']);

        foreach ($state['neighbors'] as $idj) {
            $this->send($idj,
                new Message(
                    'go',
                    array('sender' => $this->self(), 'data' => $msg['data'], 'name' => $state['name'])
                )
            );
        }

        return $state;
    }

    public function handle_neighbors($msg, $state) {
        $state['neighbors'] = $msg;
        return $state;
    }

    public function handle_go($msg, $state) {
        if ($state['parent'] == null) {
            $state['parent'] = $msg['sender'];
            $state['children'] = array();
            $state['expected_msg'] = count($state['neighbors']) - 1;

            if ($state['expected_msg'] == 0) {
                $this->send($msg['sender'],
                    new Message(
                        'back',
                        array('sender' => $this->self(), 'value' => $this->getValue($state), 'name' => $state['name'])
                    )
                );
            } else {
                foreach ($state['neighbors'] as $k) {
                    if ($k != $msg['sender']) {
                        $this->send(
                            $k,
                            new Message(
                                'go',
                                array('sender' => $this->self(), 'data' => $msg['data'], 'name' => $state['name'])
                            )
                        );
                    }
                }
            }
        } else {
            $this->send($msg['sender'],
                new Message(
                    'back',
                    array('sender' => $this->self(), 'value' => array(), 'name' => $state['name'])
                )
            );
        }
        return $state;
    }

    public function handle_back($msg, $state) {
        $state['expected_msg']--;

        if (!empty($msg['value'])) {
            if (is_array($msg['value'])) {
                $state['children'] = array_merge($state['children'], $msg['value']);
            } else {
                $state['children'][] = $msg['value'];
            }
        }

        if ($state['expected_msg'] == 0) {
            // calc value_set of all the children in array with current value
            $pr = $state['parent'];
            if ($pr != $this->self()) {
                $state['children'][] = $this->getValue($state);
                $value_set = $state['children'];
                $this->send($pr, new Message('back', array('sender' => $this->self(), 'value' => $value_set, 'name' => $state['name'])));
            } elseif ($pr == $this->self()) {
                $r = $this->compute($state['children']);
                echo sprintf("proc %s computed: %s\n", $state['name'], $r);
                $this->stop();
            }
        }
        return $state;
    }

    protected function compute($value_set) {
        return array_sum($value_set);
    }

    protected function getValue($state) {
        return rand(1, 20);
    }
}

$scheduler = new Scheduler();

$a = $scheduler->spawn('TNode', array('a'));
$b = $scheduler->spawn('TNode', array('b'));
$c = $scheduler->spawn('TNode', array('c'));
$d = $scheduler->spawn('TNode', array('d'));
$e = $scheduler->spawn('TNode', array('e'));
$f = $scheduler->spawn('TNode', array('f'));
$g = $scheduler->spawn('TNode', array('g'));
$h = $scheduler->spawn('TNode', array('h'));

$scheduler->send($a, new Message('neighbors', array($b, $h)));
$scheduler->send($b, new Message('neighbors', array($a, $c)));
$scheduler->send($c, new Message('neighbors', array($b, $d)));
$scheduler->send($d, new Message('neighbors', array($c, $e)));
$scheduler->send($e, new Message('neighbors', array($d, $f)));
$scheduler->send($f, new Message('neighbors', array($e, $g)));
$scheduler->send($g, new Message('neighbors', array($f, $h)));
$scheduler->send($h, new Message('neighbors', array($g, $a)));

$scheduler->send($a, new Message('start', array('data' => 'sum')));
$scheduler->run();