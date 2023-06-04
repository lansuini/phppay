<?php
// use Requests;
require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/sdk/gate.php';
\Workerman\Worker::$pidFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'mockPayService.workerman.pid' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'mockPayService.workerman.pid';
\Workerman\Worker::$logFile = is_dir(getenv('LOGGER_PATH')) ? getenv('LOGGER_PATH') . 'mockPayService.workerman.log' : __DIR__ . '/' . getenv('LOGGER_PATH') . 'mockPayService.workerman.log';

$worker = new \Workerman\Worker();
$worker->count = 20;
$worker->name = 'mockPay';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {
    // global $app;
    // $redis = $app->getContainer()->redis;
    \Workerman\Lib\Timer::add(1, function () {
        # 网关地址
        $gateway = getenv('GATE_DOMAIN');

        # 商户号
        $merchantNo = "88888888"  ;

        # 密钥
        $signKey = "4cb3d3f7048a428092dda2600981ba18";

        $ddp = new \DaDongPay($gateway, $merchantNo, $signKey);

        $res = $ddp->getPayOrder([
            'merchantNo' => $merchantNo,
            'merchantOrderNo' => '000001',
            'merchantReqTime' => date("YmdHis"),
            'orderAmount' => 100.00,
            'tradeSummary' => '我是摘要',
            'payModel' => 'Direct',
            'payType' => 'OnlineAlipayH5',
            'bankCode' => '',
            'cardType' => 'DEBIT',
            'userTerminal' => 'Phone',
            'userIp' => '127.0.0.1',
            'thirdUserId' => '1',
            'cardHolderName' => '',
            'cardNum' => '',
            'idType' => '01',
            'idNum' => '',
            'cardHolderMobile' => '',
            'frontNoticeUrl' => '',
            // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/success',
            'backNoticeUrl' => getenv('MOCK_MERCHANT_DOMAIN') . '/merchant/success',
            'merchantParam' => 'abc=1',
        ]);
        print_r($res);
        // if ($res['code'] == 'SUCCESS') {
        //     $redis->lpush('mockPay:queue', $res['platformOrderNo']);
        // }
    });
};

$worker = new \Workerman\Worker();
$worker->count = 20;
$worker->name = 'mockPayPop';
$worker->user = getenv('SERVICE_USER');
$worker->onWorkerStart = function ($worker) {

    \Workerman\Lib\Timer::add(1, function () {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->rpop('mockPay:queue');
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
