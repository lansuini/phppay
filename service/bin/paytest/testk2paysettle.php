<?php

require __DIR__ . '/../../sdk/gate.php';

# 网关地址
$gateway = 'http://gate.ddzfcs.com';

# 商户号
$merchantNo = "10000001";

# 密钥
$signKey = "02453ae5797d7e05874040aeb0e03a76";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->getSettlementOrder([
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => 100.00,
    'tradeSummary' => '我是代付摘要',
    'bankCode' => 'ICBC',
    'bankName' => '中国工商银行',
    'bankAccountNo' => '6212252410000986969',
    'bankAccountName' => '胡钦涛',
    'province' => '河北省',
    'city' => '石家庄',
    'orderReason' => '测试代付',
    'requestIp' => '159.138.86.177',
    'backNoticeUrl' => 'http://http://merchant.ddzfcs.com//merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
