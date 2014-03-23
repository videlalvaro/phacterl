<?php

declare(ticks=1);

namespace Phacterl\Runtime;

class Scheduler
{
    protected $msg_count = 0;

    protected $pids = array();
    protected $processes = array();

    public function __construct() {
    }

    public function run() {
        register_tick_function(array($this, 'schedule'));
        while (true) {};
    }

    public function spawn($m, $args = array()) {
        $p = new $m();
        $id = spl_object_hash($p);
        $p->setId($id);
        $p->setScheduler($this);
        $this->addProcess($id, $p);
        $this->initProcess($p, $args);
        $this->registerProcessCallbacks($p);
        $this->pids[] = $id;
        return $id;
    }

    public function initProcess($p, $args) {
        $this->processes[$p->self()]['state'] = $p->init($args);
    }

    public function registerProcessCallbacks($p) {
        $this->processes[$p->self()]['receive'] = $p->receive();
    }

    public function addProcess($id, $p) {
        $this->processes[$id] = array(
            'p' => $p,
            'receive' => array(),
            'inbox' => array(),
            'state' => null
        );
    }

    public function send($pid, $msg) {
        if (isset($this->processes[$pid])) {
            $this->processes[$pid]['inbox'][] = $msg;
            $this->msg_count++;
        }
    }

    public function schedule() {
        if ($this->msg_count > 0 && !empty($this->processes)) {
            $pid = array_shift($this->pids);
            $p_meta = $this->processes[$pid];

            if (!empty($p_meta['inbox'])) {
                $msg = array_shift($p_meta['inbox']);
                $restack = true;
                foreach ($p_meta['receive'] as $receive) {
                    if ($receive == $msg->getTag()) {
                        $m = 'handle_' . $receive;
                        $args = array($msg->getData(), $p_meta['state']);
                        $state = call_user_func_array(array($p_meta['p'], $m), $args);
                        $p_meta['state'] = $state;
                        $restack = false;
                        break;
                    }
                }

                if ($restack) {
                    array_push($p_meta['inbox'], $msg);
                } else {
                    $this->msg_count--;
                }

                $this->processes[$pid] = $p_meta;
            }
            array_push($this->pids, $pid);
        }
    }
}