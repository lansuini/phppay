<?php

require __DIR__ . './gate.php';

# 网关地址
$gateway = 'http://gate.wldev01.com';

# 商户号
$merchantNo = "88888907";

# 密钥
$signKey = "4cb3d3f7048a428092dda2600981ba18";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->getSettlementOrder([
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => 100.00,
    'tradeSummary' => '我是代付摘要',
    'bankCode' => 'ICBC',
    'bankName' => '兴宁支行',
    'bankAccountNo' => '6222032007001334680',
    'bankAccountName' => '何文渊',
    'province' => '广东省',
    'city' => '梅州市',
    'orderReason' => '测试代付',
    'requestIp' => '159.138.86.177',
    'backNoticeUrl' => 'http://mockmerchant.wldev01.com/merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
