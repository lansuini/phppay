<?php

use App\Controllers\CB\IndexController;
use App\Controllers\Mock\TestController;

$app->get('/testMock', TestController::class . ':index');
$app->any('/pay/callback/{platformOrderNo}', IndexController::class . ':pay');
$app->any('/settlement/callback/{platformOrderNo}', IndexController::class . ':settlement');
$app->any('/settlementRecharge/callback/{settlementRechargeOrderNo}', IndexController::class . ':settlementRecharge');
$app->any('/recharge/callback/{rechargeOrderNo}', IndexController::class . ':recharge');

$app->get('/message', IndexController::class . ':message');
$app->any('/getEmail', IndexController::class . ':getEmail');
$app->get('/testR', IndexController::class . ':doCreate');

$app->get('/alipayEbank', IndexController::class . ':alipayEbank');
$app->post('/getAlipayEbankLimit', IndexController::class . ':getAlipayEbankLimit');
$app->post('/createAlipayEbank', IndexController::class . ':createAlipayEbank');


$app->get('/testOrder', IndexController::class . ':testOrder');
$app->get('/testRecharge', IndexController::class . ':testRecharge');
$app->get('/test123', IndexController::class . ':test123');
$app->get('/testLogin', IndexController::class . ':testLogin');

$app->get('/specialPay/{payType}/{platformOrderNo}', IndexController::class . ':specialPay');
$app->post('/comfirmRechargeOrder', IndexController::class . ':comfirmRechargeOrder');
$app->get('/waitRechargeOrder/{payType}/{platformOrderNo}', IndexController::class . ':waitRechargeOrder');