<?php
require_once __DIR__ . '/../bootstrap/app.php';

$suffix = '.php';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'runBin.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

if (!isset($argv[1])) {
    echo '请输入执行的bin名称', PHP_EOL;
    return;
}

$file = __DIR__ . '/bin/' . $argv[1] . $suffix;
if (!is_file($file)) {
    echo 'bin脚本不存在:' . $file, PHP_EOL;
    return;
}
require $file;
echo 'finish', PHP_EOL;
