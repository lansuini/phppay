<?php
use App\Models\MerchantDailyStats;
use App\Models\ChannelDailyStats;
use App\Logics\MerchantLogic;
use App\Logics\ChannelLogic;
/**
 * 报表服务
 */
require_once __DIR__ . '/../bootstrap/app.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'reportService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'reportService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'reportService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'reportService.workerman.log';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'reportService.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = 'reportMerchantDailyService';//商户每日数据统计
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(60*10, function () {
        global $container;
        $key = 'reportService:merchant:'.date('Ymd');
        $isDo = $container->redis->get($key);
        if(empty($isDo)){
            $container->redis->setex($key, 24*3600, 1);
            $merStats = new MerchantDailyStats();
            $merLogic = new MerchantLogic($container);

            $yday = date('Y-m-d', strtotime("-1 day"));//昨天
            $merData = $merStats->where('accountDate', $yday)->first();
            if(empty($merData)){
                $merLogic->dayStats($yday);
            }
            $container->logger->info($yday.' merchant daily report service finish');
        }
    });
};

$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = 'reportChannelDailyService';//渠道每日数据统计
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(3600, function () {
        global $container;
        $key = 'reportService:channel:'.date('Ymd');
        $isDo = $container->redis->get($key);
        if(empty($isDo)){
            $chanStats = new ChannelDailyStats();
            $chanLogic = new ChannelLogic($container);

            $yday = date('Y-m-d', strtotime("-1 day"));//昨天
            $chanData = $chanStats->where('accountDate', $yday)->first();
            if(empty($chanData)){
                $chanLogic->dayStats($yday);
            }
            $container->redis->setex($key, 24*3600, 1);
            $container->logger->info($yday.' channel daily report service finish');
        }
    });
};

$worker1 = new \Workerman\Worker();
$worker1->count = 1;
$worker1->name = 'reportChannelHourService';//渠道余额定时查询
$worker1->user = getenv('SERVICE_USER');
$worker1->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(3600, function () {
        global $container;
        $merLogic = new MerchantLogic($container);
        $merLogic->getChannels();
    });
};

$worker2 = new \Workerman\Worker();
$worker2->count = 2;
$worker2->name = 'AgentReportService';
$worker2->user = getenv('SERVICE_USER');
$worker2->onWorkerStart = function ($worker) {
    $process = 0;
    if($worker->id == $process) {
        \Workerman\Lib\Timer::add(20*60, function () {
            if ('05' == date('H')) { //凌晨5点统计 少的时候统计
                global $container;
                $key = 'reportService:agent:' . date('Ymd');
                $isDo = $container->redis->get($key);
                echo $key,PHP_EOL;
                if (empty($isDo)) {
                    echo 'start -- ',PHP_EOL;
                    $merLogic = new \App\Logics\AgentLogic($container);
                    $agents = \App\Models\Agent::get();
                    $day = date('Y-m-d', strtotime("-1 day"));
                    foreach ($agents as $agent) {
                        $merLogic->settleFees($agent, $day);
                    }
                    $day = date('Y-m-d', strtotime("-2 day"));  //修正
                    foreach ($agents as $agent) {
                        $merLogic->settleFees($agent, $day);
                    }
                    $container->redis->setex($key, 24 * 3600, 1);
                }
            }
        });
    }
    $process = 1;
    if($worker->id == $process) {
        \Workerman\Lib\Timer::add(5*60, function () {
            global $container;
            $t = new \App\Logics\MerchantLogic($container);
//            $data = \App\Models\ChannelBalanceIssue::where('type', '!=', 'system')->where('orderStatus', 'Transfered')
            $data = \App\Models\ChannelBalanceIssue::where('type', '!=', 'system')
                    ->offset(0)->limit(10)->orderBy('issueId','DESC')->get();
            echo 'start search',PHP_EOL;
            foreach ($data as $val) {
                $t->channelSettlementQuery($val);
            }

        });
    }
};
\Workerman\Worker::runAll();
