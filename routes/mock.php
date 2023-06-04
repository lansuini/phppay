<?php

use App\Controllers\Mock\PayController;
use App\Controllers\Mock\SettlementController;
use App\Controllers\Mock\TestController;

$app->get('/pay/successUrl', PayController::class . ':successUrl');
$app->get('/pay/successQR', PayController::class . ':successQR');
$app->get('/pay/error', PayController::class . ':error');
$app->get('/pay/timeout', PayController::class . ':timeout');
$app->get('/pay/page/{orderNo}', PayController::class . ':page');
$app->get('/pay/notify/{orderNo}', PayController::class . ':notify');

$app->get('/test', TestController::class . ':index');

$app->get('/settlement/successUrl', SettlementController::class . ':successUrl');
