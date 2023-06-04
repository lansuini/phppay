<?php

use App\Controllers\Gate\PageController;
use App\Controllers\Gate\PayGatewayController;
use App\Controllers\Gate\PaySpecialController;
use App\Controllers\Gate\PayController;

$app->get('/paygateway/order', PayGatewayController::class . ':order');
$app->post('/paygateway/order', PayGatewayController::class . ':order');
$app->post('/paygateway/pay', PayGatewayController::class . ':order');
$app->post('/paygateway/settlement', PayGatewayController::class . ':settlement');
$app->post('/paygateway/settlementPhp', PayGatewayController::class . ':settlementPhp');

$app->post('/paygateway/queryPayOrder', PayGatewayController::class . ':queryPayOrder');
$app->post('/paygateway/queryBalance', PayGatewayController::class . ':queryBalance');
$app->post('/paygateway/querySettlementOrder', PayGatewayController::class . ':querySettlementOrder');

$app->get('/page/qr/{platformOrderNo}', PageController::class . ':qr');
$app->get('/page/autoredirect/{platformOrderNo}', PageController::class . ':autoredirect');
$app->get('/page/jsqr', PageController::class . ':jsqr');

$app->get('/paySpecial/{action}/{platformOrderNo}', PaySpecialController::class . ':index');

$app->post('/paygateway/payOrder', PayController::class . ':payOrder');
$app->get('/paygateway/payOrder', PayController::class . ':payOrder');
$app->post('/paygateway/settlementOrder', PayController::class . ':settlementOrder');
