<?php
// use Requests;
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/sdk/gate.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'mockSettlementService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'mockSettlementService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'mockSettlementService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'mockSettlementService.workerman.log';

$worker = new \Workerman\Worker();
$worker->count = 3;
$worker->name = 'mockSettlement';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    // global $app;
    // $redis = $app->getContainer()->redis;
    \Workerman\Lib\Timer::add(1, function () {
        # 网关地址
        $gateway = getenv('GATE_DOMAIN');

        # 商户号
        $merchantNo = "88888888";

        # 密钥
        $signKey = "4cb3d3f7048a428092dda2600981ba18";

        $ddp = new \DaDongPay($gateway, $merchantNo, $signKey);

        $res = $ddp->getSettlementOrder([
            'merchantNo' => $merchantNo,
            'merchantOrderNo' => '1000001',
            'merchantReqTime' => date("YmdHis"),
            'orderAmount' => 100.00,
            'tradeSummary' => '我是代付摘要',
            'bankCode' => 'ABC',
            'bankName' => '某某分行',
            'bankAccountNo' => '1212121212121212',
            'bankAccountName' => '陈小春1',
            'province' => '广东省',
            'city' => '阳春市',
            'orderReason' => '测试代付',
            'requestIp' => '127.0.0.1',
            'backNoticeUrl' => getenv('MOCK_MERCHANT_DOMAIN') . '/merchant/success',
            'merchantParam' => 'abc=1',
        ]);
        print_r($res);
    });
};

$worker = new \Workerman\Worker();
$worker->count = 1;
$worker->name = 'mockSettlementPop';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {

    \Workerman\Lib\Timer::add(1, function () {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->rpop('mockSettlement:queue');
        if (empty($data)) {
            return;
        }
        $data = json_decode($data, true);
        print_r($data);
        if (isset($data['cb'])) {
            // echo "get:" . $data['cb'], PHP_EOL;
            try {
                $req = \Requests::get($data['cb'], [], ['timeout' => 10]);
                // file_get_contents($data['cb']);
                echo '【回调成功】:' . $data['cb'], PHP_EOL;
            } catch (\Exception $e) {
                echo '【回调失败】:' . $e->getMessage(), PHP_EOL;
            }
        }
    });
};
\Workerman\Worker::runAll();
