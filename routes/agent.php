<?php

use App\Controllers\Agent\IndexController;
use App\Controllers\Agent\LoginController;
use App\Controllers\Agent\BankCardController;
use App\Controllers\Agent\BaseDataController;
use App\Controllers\Agent\WithdrawController;

use App\Helpers\Tools;

$app->get('/', LoginController::class . ':index');
$app->get('/api/manager/login', LoginController::class . ':login');
$app->get('/api/manager/modifyLoginPwd', LoginController::class . ':modifyLoginPwd');
$app->get('/api/manager/modifyPayPwd', LoginController::class . ':modifyPayPwd');
$app->get('/logout', LoginController::class . ':logout');

$app->get('/index', IndexController::class . ':index');
$app->get('/api/index/searchChart', IndexController::class . ':searchChart');
$app->get('/api/bankCard/setBank', BankCardController::class . ':setBank');
$app->get('/api/bankCard/search', BankCardController::class . ':search');
$app->get('/api/bankCard/delete', BankCardController::class . ':delete');
$app->get('/api/getbasedata', BaseDataController::class . ':index');
$app->get('/api/getbasedata/classItem', BaseDataController::class . ':classItem');
$app->get('/api/withdraw/search', WithdrawController::class . ':search');
$app->get('/api/withdraw/apply', WithdrawController::class . ':apply');

$app->get('/finance', \App\Controllers\Agent\FinanceController::class . ':index');
$app->get('/api/finance/search', \App\Controllers\Agent\FinanceController::class . ':search');
$app->get('/api/finance/unsettledAmount', \App\Controllers\Agent\FinanceController::class . ':unsettledAmount');

$app->get('/rate', \App\Controllers\Agent\RateController::class . ':index');
$app->get('/api/rate/search', \App\Controllers\Agent\RateController::class . ':search');

$app->get('/stats', \App\Controllers\Agent\StatsController::class . ':index');
$app->get('/api/stats/search', \App\Controllers\Agent\StatsController::class . ':search');

$app->get('/merchantRate', \App\Controllers\Agent\MerchantRateController::class . ':index');
$app->get('/api/merchantRate/search', \App\Controllers\Agent\MerchantRateController::class . ':search');
$app->get('/api/merchantRate/change', \App\Controllers\Agent\MerchantRateController::class . ':change');

$app->get('/merchant', \App\Controllers\Agent\MerchantController::class . ':index');
$app->get('/api/merchant/search', \App\Controllers\Agent\MerchantController::class . ':search');

$app->get('/ip/permission', function ($request, $response, $args) {
    return $response->withStatus(500)->write("IP not accessible (your ip address:" . Tools::getIp() . ")");
});