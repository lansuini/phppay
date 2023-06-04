<?php
use App\Queues\PlatformNotifyExecutor;

require_once __DIR__ . '/../bootstrap/app.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'platformNotifyService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'platformNotifyService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'platformNotifyService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'platformNotifyService.workerman.log';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'platformNotifyService.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = 'platformNotifyTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    $proccId = 0;
    if ($worker->id === $proccId) {
        \Workerman\Lib\Timer::add(10, function () {
            //代付回调
            (new PlatformNotifyExecutor)->pop();
        });
    }
};

\Workerman\Worker::runAll();
