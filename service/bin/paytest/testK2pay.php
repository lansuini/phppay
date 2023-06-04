<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require __DIR__ . '/../../sdk/gate.php';

# 网关地址
$gateway = 'http://gate.xddzfcsz.com';

# 商户号
$merchantNo = "10000011";

# 密钥
$signKey = "67c7a4b23461805bbeb574e13f7bd54d";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);
// $res = $ddp->getPayOrder([
//     'payType' => 'OnlineAlipayH5',
//     'tradeSummary' => '交易摘要',
//     'merchantParam' => '',
//     'userTerminal' => 'PC',
//     // 'merchantNo' => '10000001',
// ]);

$res = $ddp->getPayOrder([
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => 20221222161811494687,
    'merchantReqTime' => 20221222161811,
    'orderAmount' => 10.00,
    'tradeSummary' => '我是摘要',
    'payModel' => 'Direct',
    'payType' => 'gcash',
    'bankCode' => '',
    'cardType' => 'DEBIT',
    'userTerminal' => 'Phone',
    'userIp' => '127.0.0.1',
//    'thirdUserId' => '1',
    'cardHolderName' => '',
    'cardNum' => '',
//    'idType' => '01',
    'idNum' => '',
    'cardHolderMobile' => '',
    'frontNoticeUrl' => '',
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://mockmerchant.wldev01.com/merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
