<?php
use App\Helpers\DataMover;
use App\Models\Amount;
use App\Models\ChannelMerchant;
use App\Models\ChannelMerchantRate;
use App\Models\ChannelPayConfig;
use App\Models\ChannelSettlementConfig;
use App\Models\Merchant;
use App\Models\MerchantAccount;
use App\Models\MerchantAmount;
use App\Models\MerchantChannel;
use App\Models\MerchantChannelSettlement;
use App\Models\MerchantRate;
use App\Models\PayNotifyTask;
use App\Models\PayPushTask;
use App\Models\PlatformPayOrder;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementActiveQueryTask;
use App\Models\SettlementFetchTask;
use App\Models\SettlementNotifyTask;
use App\Models\SettlementPushTask;
use App\Queues\PayNotifyExecutor;
use App\Queues\PayPushExecutor;
use App\Queues\SettlementActiveQueryExecutor;
use App\Queues\SettlementFetchExecutor;
use App\Queues\SettlementNotifyExecutor;
use App\Queues\SettlementPushExecutor;
require_once __DIR__ . '/../bootstrap/app.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'orderService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'orderService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'orderService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'orderService.workerman.log';

$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'] . 'orderService.log', $settings['level']));
    $logger->pushHandler(new Monolog\Handler\RotatingFileHandler($settings['path'] . 'orderService.log', 0, $settings['level']));//按天生成文件
    $logger->pushHandler(new Monolog\Handler\ErrorLogHandler(Monolog\Handler\ErrorLogHandler::OPERATING_SYSTEM, Monolog\Logger::INFO));
    return $logger;
};

$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = 'refreshCache';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    \Workerman\Lib\Timer::add(43200, function () {
        (new Merchant)->refreshCache();
        (new MerchantRate)->refreshCache();
        (new MerchantChannel)->refreshCache();
        (new MerchantChannelSettlement)->refreshCache();
        (new MerchantAccount)->refreshCache();
        (new ChannelMerchant)->refreshCache();
        (new ChannelMerchantRate)->refreshCache();
        (new MerchantAmount)->refreshCache();
        (new ChannelPayConfig)->refreshCache();
        (new ChannelSettlementConfig)->refreshCache();

        $data = Merchant::get();
        $amount = new Amount;
        foreach ($data as $v) {
            $amount->init($v->merchantId, $v->merchantNo);
        }
    });
};

if (getenv('DATA_MOVER_ENABLED') === 'true') {
    $worker = new \Workerman\Worker();
    $worker->count = 1;
    $worker->name = 'dataMover';
    $worker->user = getenv('SERVICE_USER');
    $worker->onWorkerStart = function ($worker) {
        \Workerman\Lib\Timer::add(3600, function () {
            $h = intval(date('H'));
            if ($h == 5) {
                $config = [
                    [
                        'm' => '\App\Models\PlatformPayOrder',
                        't' => 1,
                        'l' => 35 * 86400,
                        'p' => ['orderStatus' => 'Success'],
                    ],
                    [
                        'm' => '\App\Models\PlatformSettlementOrder',
                        't' => 2,
                        'l' => 35 * 86400,
                        'p' => ['orderStatus' => 'Success'],
                    ],
                    [
                        'm' => '\App\Models\AmountPay',
                        't' => 3,
                        'l' => 60 * 86400,
                    ],
                    [
                        'm' => '\App\Models\AmountSettlement',
                        't' => 4,
                        'l' => 60 * 86400,
                    ],
                    [
                        'm' => '\App\Models\PlatformPayOrder',
                        't' => 6,
                        'l' => 3 * 86400,
                        'p' => ['orderStatus' => 'Fail'],
                    ],
                    [
                        'm' => '\App\Models\PlatformSettlementOrder',
                        't' => 7,
                        'l' => 3 * 86400,
                        'p' => ['orderStatus' => 'Fail'],
                    ],
                ];
                (new DataMover($config))->run();
            }

            $config = [
                [
                    'm' => '\App\Models\PayNotifyTask',
                    't' => 8,
                    'l' => 86400,
                ],
                [
                    'm' => '\App\Models\PayPushTask',
                    't' => 9,
                    'l' => 86400,
                ],
                [
                    'm' => '\App\Models\SettlementNotifyTask',
                    't' => 11,
                    'l' => 86400,
                ],
                [
                    'm' => '\App\Models\SettlementPushTask',
                    't' => 12,
                    'l' => 86400,
                ],
                [
                    'm' => '\App\Models\SettlementActiveQueryTask',
                    't' => 13,
                    'l' => 86400,
                ],
            ];
            (new DataMover($config))->run();
        });
    };
}

$worker = new \Workerman\Worker();
$worker->count = 5;
$worker->name = 'checkTask';
$worker->user = getenv('SERVICE_USER');
$processId = 0;
$worker->onWorkerStart = function ($worker) {
    $processId = 0;

    if ($worker->id === $processId) {
        \Workerman\Lib\Timer::add(90, function () {
            if ((new SettlementActiveQueryExecutor)->isAllowPush() == false) {
                return;
            }
            SettlementActiveQueryTask::where('updated_at', '<=', date('YmdHis', time() - 120))
                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
                ->where('status', 'Execute')
                ->orderBy('id', 'asc')
                ->chunk(200, function ($task) {
                    foreach ($task ?? [] as $v) {
                        (new SettlementActiveQueryExecutor)->push($v->id, $v->platformOrderNo);
                    }
                });
        });
    }

    $processId++;
    if ($worker->id === $processId) {
        \Workerman\Lib\Timer::add(60, function () {
            if ((new SettlementPushExecutor)->isAllowPush() == false) {
                return;
            }
            SettlementPushTask::where('updated_at', '<=', date('YmdHis', time() - 120))
                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
                ->where('status', 'Execute')
                ->orderBy('id', 'asc')
                ->chunk(200, function ($task) {
                    foreach ($task ?? [] as $v) {
                        (new SettlementPushExecutor)->push($v->id, $v->platformOrderNo, $v->thirdParams, $v->standardParams);
                    }
                });
        });
    }

//    $processId++;
//    if ($worker->id === $processId) {
//        \Workerman\Lib\Timer::add(60, function () {
//            if ((new SettlementNotifyExecutor)->isAllowPush() == false) {
//                return;
//            }
//            SettlementNotifyTask::where('updated_at', '<=', date('YmdHis', time() - 120))
//                ->where('updated_at', '>=', date('YmdHis', time() - 15*20*60*60))
//                ->where('status', 'Execute')
//                ->orderBy('id', 'asc')
//                ->chunk(200, function ($task) {
//                    foreach ($task ?? [] as $v) {
//                        (new SettlementNotifyExecutor)->push($v->id, $v->platformOrderNo);
//                    }
//                });
//        });
//    }

    $processId++;
    if ($worker->id === $processId) {
        //支付回调任务
        \Workerman\Lib\Timer::add(60, function () {
            if ((new PayPushExecutor)->isAllowPush() == false) {
                return;
            }
            PayPushTask::where('updated_at', '<=', date('YmdHis', time() - 120))
                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
                ->where('status', 'Execute')
                ->orderBy('id', 'asc')
                ->chunk(200, function ($task) {
                    foreach ($task ?? [] as $v) {
                        (new PayPushExecutor)->push($v->id, $v->platformOrderNo, $v->thirdParams, $v->standardParams);
                    }
                });
        });
    }

    $processId++;
    if ($worker->id === $processId) {
        \Workerman\Lib\Timer::add(60, function () {
            //支付回调
            if ((new PayNotifyExecutor)->isAllowPush() == false) {
                return;
            }
            PayNotifyTask::where('updated_at', '<=', date('YmdHis', time() - 120))
                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
                ->where('status', 'Execute')
                ->orderBy('id', 'asc')
                ->chunk(200, function ($task) {
                    foreach ($task ?? [] as $v) {
                        (new PayNotifyExecutor)->push($v->id, $v->platformOrderNo);
                    }
                });
        });
    }

//    $processId++;
//    if ($worker->id === $processId) {
//        \Workerman\Lib\Timer::add(3600, function () {
//            global $app;
//            $logger = $app->getContainer()->logger;
//            PlatformSettlementOrder::where('updated_at', '<=', date('YmdHis', time() - 86400))
//                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
//                ->whereNotIn('orderStatus', ['Success', 'Fail', 'Exception'])
//                ->orderBy('orderId', 'asc')
//                ->chunk(200, function ($task) use ($logger) {
//                    foreach ($task ?? [] as $v) {
//                        // (new PlatformSettlementOrder)->fail($v->toArray(), 'Expired');
//                        $model = new PlatformSettlementOrder;
//                        $res = $model->fail($v->toArray(), 'Expired');
//                        if (!$res) {
//                            $logger->error("auto expired pay order:" . $this->getErrorMessage());
//                        }
//                    }
//                });
//        });
//    }

//    $processId++;
//    if ($worker->id === $processId) {
//        \Workerman\Lib\Timer::add(3600, function () {
//            global $app;
//            $logger = $app->getContainer()->logger;
//            PlatformPayOrder::where('updated_at', '<=', date('YmdHis', time() - 86400))
//                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
//                ->whereNotIn('orderStatus', ['Success', 'Fail', 'Expired'])
//                ->orderBy('orderId', 'asc')
//                ->chunk(200, function ($task) use ($logger) {
//                    foreach ($task ?? [] as $v) {
//                        $model = new PlatformPayOrder;
//                        $res = $model->fail($v->toArray(), 'Expired');
//                        if (!$res) {
//                            $logger->error("auto expired pay order:" . $this->getErrorMessage());
//                        }
//                    }
//                });
//        });
//    }

    $processId++;
    if ($worker->id === $processId) {
        \Workerman\Lib\Timer::add(60, function () {
            if ((new SettlementFetchExecutor)->isAllowPush() == false) {
                return;
            }
            SettlementFetchTask::where('updated_at', '<=', date('YmdHis', time() - 120))
                ->where('updated_at', '>=', date('YmdHis', time() - 2*24*60*60))
                ->where('status', 'Execute')
                ->orderBy('id', 'asc')
                ->chunk(200, function ($task) {
                    foreach ($task ?? [] as $v) {
                        (new SettlementFetchExecutor)->push($v->id, $v->platformOrderNo);
                    }
                });
        });
    }
};
\Workerman\Worker::runAll();
