<?php

use App\Controllers\Merchant\BaseDataController;
use App\Controllers\Merchant\IndexController;
use App\Controllers\Merchant\LoginController;
use App\Controllers\Merchant\ManagerController;
use App\Controllers\Merchant\NoticeController;
use App\Controllers\Merchant\PayOrderController;
use App\Controllers\Merchant\SettlementOrderController;
use App\Controllers\Merchant\FinanceController;
use App\Controllers\Merchant\RechargeOrderController;
use App\Controllers\Merchant\ReportController;

use App\Helpers\Tools;

$app->get('/', LoginController::class . ':index');
$app->get('/api/manager/login', LoginController::class . ':login');
$app->get('/logout', LoginController::class . ':logout');

$app->get('/head', IndexController::class . ':head');
//消息提示
$app->get('/api/index/tips', IndexController::class . ':tips');
$app->get('/getsignkey', IndexController::class . ':getsignkey');

//获取公告信息
$app->get('/getNotice', IndexController::class . ':getNotice');

//余额转账
$app->get('/api/index/transform', IndexController::class . ':transform');
$app->get('/payorder', PayOrderController::class . ':index');
$app->get('/payorder/detail', PayOrderController::class . ':detail');
$app->get('/api/getbasedata.bak', function ($request, $response, $args) {
    if ($request->getParam('requireItems') == 'payOrderStatus,payType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata11.json'));
    } else if ($request->getParam('requireItems') == 'settlementOrderStatus,bankCode') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata12.json'));
    } else if ($request->getParam('requireItems') == 'bankCode') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata12.json'));
    } else if ($request->getParam('requireItems') == 'psqlBankCode') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata-psqlBankcode.json'));
    }
});
$app->get('/api/payorder/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-payorder-search.json'));
});
$app->get('/api/payorder/search', PayOrderController::class . ':search');
$app->get('/api/payorder/notify', PayOrderController::class . ':notify');

$app->get('/settlementorder', SettlementOrderController::class . ':index');
$app->get('/settlementorder/detail', SettlementOrderController::class . ':detail');
$app->get('/api/settlementorder/detail', SettlementOrderController::class . ':getDetail');

//每日报表
$app->get('/report', ReportController::class . ':index');
$app->get('/api/report', ReportController::class . ':getReport');

$app->get('/settlementorder/create', SettlementOrderController::class . ':create');
$app->get('/settlementbatchorder/create', \App\Controllers\Merchant\SettlementBatchOrderController::class . ':create');
$app->get('/api/settlementbatchorder/getBankCode', \App\Controllers\Merchant\SettlementBatchOrderController::class . ':getBankCode');
$app->get('/settlementorder/aliCreate', SettlementOrderController::class . ':aliCreate');
$app->get('/settlementorder/cardlist', SettlementOrderController::class . ':cardlist');
$app->get('/settlementorder/settlementChannel', SettlementOrderController::class . ':settlementChannel');
$app->get('/api/settlementorder/create', SettlementOrderController::class . ':doCreate');
$app->post('/api/settlementbatchorder/doCreate', \App\Controllers\Merchant\SettlementBatchOrderController::class . ':doCreate');
$app->get('/api/settlementorder/aliSettlement', SettlementOrderController::class . ':aliSettlement');
$app->get('/api/settlementorder/cardsearch', SettlementOrderController::class . ':cardSearch');
$app->get('/api/settlementorder/addMerchantCard', SettlementOrderController::class . ':addMerchantCard');
$app->get('/api/settlementorder/updateMerchantCard', SettlementOrderController::class . ':updateMerchantCard');
$app->get('/api/settlementorder/chooseMerchantCard', SettlementOrderController::class . ':chooseMerchantCard');

$app->post('/api/settlementbatchorder/inputDoCreate', \App\Controllers\Merchant\SettlementBatchOrderController::class . ':inputDoCreate');

$app->get('/api/settlementorder/deleteCard', SettlementOrderController::class . ':deleteCard');

$app->get('/api/settlementorder/channel/search', SettlementOrderController::class . ':settlementChannelSearch');
$app->get('/api/settlementorder/channel/rechargeCheck', SettlementOrderController::class . ':settlementChannelRechargeCheck');
$app->get('/api/settlementorder/channel/recharge', SettlementOrderController::class . ':settlementChannelRecharge');
$app->get('/api/settlementorder/search', SettlementOrderController::class . ':search');
$app->get('/api/settlementorder/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-settlementorder-search.json'));
});

//充值
$app->get('/rechargeorder/paychannel', RechargeOrderController::class . ':paychannel');
$app->get('/rechargeorder/create', RechargeOrderController::class . ':create');
$app->get('/api/rechargeorder/choosechannel', RechargeOrderController::class . ':chooseChannel');
$app->get('/api/rechargeorder/paychannel/search', RechargeOrderController::class . ':paychannelSearch');
$app->get('/api/rechargeorder/create', RechargeOrderController::class . ':doCreate');

//充值订单
$app->get('/rechargeorder', RechargeOrderController::class . ':rechargeOrder');
$app->get('/api/rechargeorder/search', RechargeOrderController::class . ':rechargeOrderSearch');

$app->get('/settlementorder/account', SettlementOrderController::class . ':account');//支付宝账号
$app->get('/api/settlementorder/getaccount', SettlementOrderController::class . ':getAccount');//获取支付宝账号信息
$app->get('/api/settlementorder/accountdetail', SettlementOrderController::class . ':accountDetail');//获取支付宝账号信息详情
$app->get('/api/settlementorder/getChannelParameter', SettlementOrderController::class . ':getChannelParameter');//获取支付宝账号配置字段
$app->get('/api/settlementorder/update', SettlementOrderController::class . ':update');//修改支付宝账号信息
$app->get('/api/settlementorder/insertAlipayAccount', SettlementOrderController::class . ':insertAlipayAccount');//新增支付宝账号信息



//$app->get('/changepayer', ManagerController::class . ':changepayer');
$app->get('/changesecurepwd', ManagerController::class . ':changesecurepwd');
$app->get('/changeloginpwd', ManagerController::class . ':changeloginpwd');

// $app->get('/api/changeloginname', ManagerController::class . ':doChangeloginname');
//$app->get('/api/changepayer', ManagerController::class . ':doChangepayer');
$app->get('/api/changeloginpwd', ManagerController::class . ':doChangeloginpwd');
$app->get('/api/changesecurepwd', ManagerController::class . ':doChangesecurepwd');

$app->get('/api/getbasedata', BaseDataController::class . ':index');
$app->get('/api/getbasedata/merchantChannel', BaseDataController::class . ':merchantChannel');
$app->get('/api/getrechargeOrderdata', BaseDataController::class . ':rechargeOrder');//代付充值订单

$app->get('/api/manager/googleauth', LoginController::class . ':doGoogleAuth');
$app->get('/googleauth', LoginController::class . ':googleAuth');

$app->get('/bindgoogleauth', ManagerController::class . ':bindgoogleauth');
$app->get('/api/manager/bindgoogleauth', ManagerController::class . ':doBindgoogleauth');

$app->get('/finance', FinanceController::class . ':index');
$app->get('/api/finance/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-finance-search.json'));
});
$app->get('/api/finance/search', FinanceController::class . ':search');

$app->get('/ip/permission', function ($request, $response, $args) {
    return $response->withStatus(500)->write("IP not accessible (your ip address:" . Tools::getIp() . ")");
});

//消息公告
$app->get('/notice', NoticeController::class . ':index');
$app->get('/api/notice/search', NoticeController::class . ':search');