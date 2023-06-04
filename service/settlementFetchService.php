<?php
use App\Channels\ChannelProxy;
use App\Models\ChannelMerchant;
use App\Models\ChannelSettlementConfig;
use App\Models\MerchantChannelSettlement;
use App\Queues\SettlementActiveQueryExecutor;
use App\Queues\SettlementFetchExecutor;
require_once __DIR__ . '/../bootstrap/app.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'settlementFetch.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'settlementFetch.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'settlementFetch.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'settlementFetch.workerman.log';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'settlementFetch.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'settlementFetch.log', 0, $settings['level']));//按天生成文件
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = 'queryBalance';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(300, function () {
        global $app;
        $logger = $app->getContainer()->logger;
        $redis = $app->getContainer()->redis;
        $channels = $app->getContainer()->code['channel'];

        $data = ChannelMerchant::where('status', 'Normal')->inRandomOrder()->get();
        $settlement = new MerchantChannelSettlement();
        foreach ($data ?? [] as $v) {
            try {
                $ch = isset($channels[$v->channel]) ? $channels[$v->channel] : [];
                if (empty($ch)) {
                    continue;
                }

                if (!$ch['openSettlement'] || !$ch['openQuery']) {
                    continue;
                }
                $old = $settlement->getCacheByChannelMerchantNo($v->channelMerchantNo);

                if(!isset($old['accountBalance'])) continue;

                $balance = ((new ChannelProxy)->queryBalance($v->channelMerchantId));
                $balance = $balance['balance'];
                $logger->info('渠道ID-'.$v->channelMerchantId.'渠道号-'.$v->channelMerchantNo . "余额为======" . $balance );
                if($old['accountBalance'] == $balance) continue;

                MerchantChannelSettlement::where('channelMerchantId', $v->channelMerchantId)->update([
                    'accountBalance' => $balance,
                ]);
                ChannelSettlementConfig::where('channelMerchantId', $v->channelMerchantId)->update([
                    'accountBalance' => $balance,
                ]);
                (new ChannelSettlementConfig)->refreshCache(['channelMerchantId' => $v->channelMerchantId]);
                $merchantChantSettlements = MerchantChannelSettlement::where('channelMerchantId', $v->channelMerchantId)->get();
                if($merchantChantSettlements){
                    foreach ($merchantChantSettlements as $merchantChantSettlement){
                        $settlement->refreshCache(['merchantId'=>$merchantChantSettlement->merchantId]);
                    }
                }
//                echo '渠道号：'.$v->channelMerchantNo . "余额为======" . $balance;
            } catch (\Exception $e) {
                $logger->error("queryBalance error:" . $e->getMessage());
            }
            sleep(1);
        }

    });
};

$worker = new \Workerman\Worker();
$worker->count = getenv('SETTLEMENT_FETCH_WORKER_COUNT');
$worker->name = 'settlementFetchTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    //请求代付
    \Workerman\Lib\Timer::add(0.5, function () {
        (new SettlementFetchExecutor)->pop();
    });
};

$worker = new \Workerman\Worker();
$worker->count = 20;
$worker->name = 'settlementActiveQueryTaskWorker';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    //代付查询
    \Workerman\Lib\Timer::add(0.5, function () {
        (new SettlementActiveQueryExecutor)->pop();
    });
};

\Workerman\Worker::runAll();
