<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require 'gate.php';

# 网关地址
$gateway = 'http://gate.wldev01.com';

# 商户号
$merchantNo = "88888888";

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

$params = [
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => 500.00,
    'tradeSummary' => '我是摘要',
    'payModel' => 'Direct',
    'payType' => 'EBank',
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
];
$json = '{
    "merchantNo":"10000016",
    "merchantOrderNo":"52033521",
    "merchantReqTime":"20220704165035",
    "orderAmount":"200",
    "tradeSummary":"assd",
    "payModel":"Direct",
    "payType":"gcash",
    "cardType":"DEBIT",
    "userTerminal":"PC",
    "userIp":"176.43.233.3",
    "backNoticeUrl":"http://test.com",
    "sign":"cf0aee37a3666ba1059416a5e23fe4c8"
}';
$params = json_decode($json,true);
unset($params['sign']);
$res = $ddp->getPayOrder($params);
exit;
$json = '{
    "merchantNo":"10000016",
    "merchantOrderNo":"52033521",
    "merchantReqTime":"20220704165035",
    "orderAmount":"200",
    "tradeSummary":"assd",
    "payModel":"Direct",
    "payType":"gcash",
    "cardType":"DEBIT",
    "userTerminal":"PC",
    "userIp":"176.43.233.3",
    "backNoticeUrl":"http://test.com",
    "sign":"cf0aee37a3666ba1059416a5e23fe4c8"
}';
$params = json_decode($json,true);
print_r($params);

print_r($res);
echo 'finish', PHP_EOL;
