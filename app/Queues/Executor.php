<?php
namespace App\Queues;

abstract class Executor
{
    protected $queueName;

    protected $redis;

    protected $logger;

    protected $db;

    public function __construct()
    {
        global $app;
        $this->redis = $app->getContainer()->redis;
        $this->logger = $app->getContainer()->logger;
        $this->db = $app->getContainer()->database;
    }

    // abstract public function push($platformOrderNo, $params);

    abstract public function pop();

    public function refreshLastExecutorTime()
    {
        $this->redis->setex($this->queueName . ":lasttime", 60, time());
    }

    public function isAllowPush() {
        $lastTime = (int) $this->redis->get($this->queueName . ":lasttime");
        if (time() - 30 > $lastTime) {
            return false;
        }
        return true;
    }
    
    public function clear()
    {
        $this->redis->del($this->queueName);
    }
}
