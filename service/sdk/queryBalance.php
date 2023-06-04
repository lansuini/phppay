<?php

require 'gate.php';

# 网关地址
$gateway = 'http://gate.wldev01.com';

# 商户号
$merchantNo = "88888888";

# 密钥
$signKey = "4cb3d3f7048a428092dda2600981ba18";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->queryBalance([
    'merchantNo' => $merchantNo,
]);
print_r($res);
