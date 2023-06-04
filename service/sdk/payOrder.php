<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require 'gate.php';

# 网关地址
$gateway = $argv[1];

# 商户号
$merchantNo = $argv[2];

# 密钥
$signKey = $argv[3];

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
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => $argv[5] ?? 100.00,
    'tradeSummary' => '我是摘要',
    'payModel' => 'Direct',
    'payType' => $argv[4],
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
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://mockmerchant-test.wldev01.com/merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
