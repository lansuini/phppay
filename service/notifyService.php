<?php
use App\Queues\PayNotifyExecutor;
use App\Queues\SettlementNotifyExecutor;

require_once __DIR__ . '/../bootstrap/app.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'notifyService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'notifyService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'notifyService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'notifyService.workerman.log';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'notifyService.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'notifyService.log', 0, $settings['level']));//按天生成文件
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

$worker = new \Workerman\Worker();
$worker->count = getenv('SETTLEMENT_NOTIFY_WORKER_COUNT');
$worker->name = 'settlementNotifyTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    $proccId = 0;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(0.2, function () {
            (new SettlementNotifyExecutor)->pop();
        });
    }

    $proccId++;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(0.2, function () {
            (new SettlementNotifyExecutor)->pop('settlementnotify:queue2');
        });
    }

    $proccId++;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(0.2, function () {
            (new SettlementNotifyExecutor)->pop('settlementnotify:queue3');
        });
    }

    $proccId++;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(0.2, function () {
            (new SettlementNotifyExecutor)->pop('settlementnotify:queue4');
        });
    }

    $proccId++;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(0.2, function () {
            (new SettlementNotifyExecutor)->pop('settlementnotify:queue5');
        });
    }

    $proccId++;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(0.2, function () {
            (new SettlementNotifyExecutor)->pop('settlementnotify:queue6');
        });
    }
};

$worker = new \Workerman\Worker();
$worker->count = getenv('PAY_NOTIFY_WORKER_COUNT');
$worker->name = 'payNotifyTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(0.2, function () {
        (new PayNotifyExecutor)->pop();
    });
};

\Workerman\Worker::runAll();
