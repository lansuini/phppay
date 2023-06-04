<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
require __DIR__ . '/../../sdk/gate.php';

# 网关地址
$gateway = 'http://gate.xddzfcsz.com';

# 商户号
$merchantNo = "10000004";

# 密钥
$signKey = "e478ed585b5b9698ef8afd83eeb6544e";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->getSettlementOrder([
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => 1.00,
    'tradeSummary' => '我是代付摘要',
    'bankCode' => 'ALIPAY',
    'bankName' => '石家庄鹿泉支行',
    'bankAccountNo' => 'hpspdff8577@163.com',
    'bankAccountName' => '河南畅旺实业有限公司',
    'province' => '河北省',
    'city' => '石家庄',
    'orderReason' => '测试代付',
    'requestIp' => '127.0.0.1',
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://merchant.xddzfcsz.com/merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
