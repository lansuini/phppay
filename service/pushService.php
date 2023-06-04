<?php
use App\Queues\PayPushExecutor;
use App\Queues\SettlementPushExecutor;

require_once __DIR__ . '/../bootstrap/app.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'pushService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'pushService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'pushService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'pushService.workerman.log';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'pushService.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'pushService.log', 0, $settings['level']));//按天生成文件
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

$worker = new \Workerman\Worker();
$worker->count = getenv('SETTLEMENT_PUSH_WORKER_COUNT');
//$worker->count = 10;
$worker->name = 'settlementPushTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(0.5, function () {
        (new SettlementPushExecutor)->pop();
    });
};

$worker = new \Workerman\Worker();
$worker->count = getenv('PAY_PUSH_WORKER_COUNT');
//$worker->count = 1;
$worker->name = 'payPushTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(0.5, function () {
        (new PayPushExecutor)->pop();
    });
};

\Workerman\Worker::runAll();
