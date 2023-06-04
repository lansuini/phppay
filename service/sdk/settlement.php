<?php

require 'gate.php';

# 网关地址
$gateway = 'http://gate.xddzfcsz.com';

# 商户号
$merchantNo = "10000010";

# 密钥
$signKey = "83f3739f72bc936c100989f591949045";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->getSettlementOrder([
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => 300.00,
    'tradeSummary' => '我是代付摘要',
    'bankCode' => 'RB',
    'bankName' => '某某分行',
    'bankAccountNo' => '1231231',
    'bankAccountName' => 'ra',
    'province' => '广东省',
    'city' => 'Pasig',
    'orderReason' => '测试代付',
    'requestIp' => '127.0.0.1',
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://cb.xddzfcsz.com/settlement/callback/test123123',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;
