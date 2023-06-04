<?php

require 'gate.php';

# 网关地址
$gateway = 'http://gate.wldev01.com';

# 商户号
$merchantNo = "90000036";

# 密钥
$signKey = "b8291f7d3289e468501531c4e89252d5";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->querySettlementOrder([
    'merchantNo' => $merchantNo,
    // 'merchantOrderNo' => '000001',
    'platformOrderNo' => 'S20191203121758120895',
]);
print_r($res);
