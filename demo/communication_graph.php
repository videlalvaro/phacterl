<?php

require __DIR__ . '/../vendor/autoload.php';

use Phacterl\Runtime\Scheduler;
use Phacterl\Actor\Actor;
use Phacterl\Message\Message;

class Process extends Actor {

    public function init($args) {
        return array(
            'name' => $args[0],
            'part' => false,
            'proc_known' => array($this->self()),
            'neighbors' => array(),
            'channels_known' => array()
        );
    }

    public function receive() {
        return array('neighbors', 'start', 'position');
    }

    public function start($state) {
        foreach ($state['neighbors'] as $idj) {
            $this->send($idj, new Message('position',
                    array('id' => $this->self(), 'neighbors' => $state['neighbors']))
            );
        }
        $state['part'] = true;
        return $state;
    }

    public function handle_neighbors($msg, $state) {
        $state['neighbors'] = $msg;
        foreach ($msg as $idj) {
            $ch = array($this->self(), $idj);
            sort($ch);
            $state['channels_known'][] = $ch;
        }
        return $state;
    }

    public function handle_start($msg, $state) {
        if (!$state['part']) {
            $state = $this->start($state);
        }

        return $state;
    }

    public function handle_position($msg, $state) {

        if (!$state['part']) {
            $state = $this->start($state);
        }

        if (!in_array($msg['id'], $state['proc_known'])) {
            $state['proc_known'][] = $msg['id'];

            foreach ($msg['neighbors'] as $idn) {
                $ch = array($msg['id'], $idn);
                sort($ch);
                $state['channels_known'][] = $ch;
            }

            foreach ($state['neighbors'] as $idy) {
                if ($idy != $msg['id']) {
                    $this->send($idy, new Message('position', $msg));
                }
            }

            $tmp_set = array();
            foreach ($state['channels_known'] as $pair) {
                $tmp_set = array_merge($tmp_set, $pair);
            }
            $tmp_set = array_unique($tmp_set);

            $diff = array_diff($tmp_set, $state['proc_known']);

            if (empty($diff)) {
                echo sprintf("process %s knows the communication graph \n", $state['name']);
            }
        }

        return $state;
    }
}

$scheduler = new Scheduler();

$a = $scheduler->spawn('Process', array('a'));
$b = $scheduler->spawn('Process', array('b'));
$c = $scheduler->spawn('Process', array('c'));
$d = $scheduler->spawn('Process', array('d'));
$e = $scheduler->spawn('Process', array('e'));
$f = $scheduler->spawn('Process', array('f'));
$g = $scheduler->spawn('Process', array('g'));
$h = $scheduler->spawn('Process', array('h'));

$scheduler->send($a, new Message('neighbors', array($b, $h)));
$scheduler->send($b, new Message('neighbors', array($a, $c)));
$scheduler->send($c, new Message('neighbors', array($b, $d)));
$scheduler->send($d, new Message('neighbors', array($c, $e)));
$scheduler->send($e, new Message('neighbors', array($d, $f)));
$scheduler->send($f, new Message('neighbors', array($e, $g)));
$scheduler->send($g, new Message('neighbors', array($f, $h)));
$scheduler->send($h, new Message('neighbors', array($g, $a)));

$scheduler->send($a, new Message('start', array()));
$scheduler->run();