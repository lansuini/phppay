<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require __DIR__ . './gate.php';

# 网关地址
$gateway = 'http://gate.wldev01.com';

# 商户号
$merchantNo = "88888907";

# 密钥
$signKey = "4cb3d3f7048a428092dda2600981ba18";

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
    'orderAmount' => 900.00,
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
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://mockmerchant.wldev01.com/merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
