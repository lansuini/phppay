<?php

use App\Controllers\GM\AccountController;
use App\Controllers\GM\AgentController;
use App\Controllers\GM\BalanceadjustmentController;
use App\Controllers\GM\BaseDataController;
use App\Controllers\GM\ChannelController;
use App\Controllers\GM\ChartController;
use App\Controllers\GM\CheckController;
use App\Controllers\GM\FinanceController;
use App\Controllers\GM\LoginController;
use App\Controllers\GM\ManagerController;
use App\Controllers\GM\MerchantController;
use App\Controllers\GM\PayOrderController;
use App\Controllers\GM\SettlementOrderController;
use App\Controllers\GM\RechargeOrderController;
use App\Controllers\GM\BalanceIssueController;
use App\Controllers\GM\TransformController;
use App\Controllers\GM\BankController;
use App\Helpers\Tools;

$app->get('/', LoginController::class . ':index');
$app->get('/api/manager/login', LoginController::class . ':login');
$app->get('/logout', LoginController::class . ':logout');

$app->get('/payorder', PayOrderController::class . ':index');
$app->get('/payorder/detail', PayOrderController::class . ':detail');

$app->get('/api/getbasedata.bak', function ($request, $response, $args) {
    if ($request->getParam('requireItems') == 'payOrderStatus,payType,channel') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata.json'));
    } else if ($request->getParam('requireItems') == 'settlementOrderStatus,bankCode') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata2.json'));
    } else if ($request->getParam('requireItems') == 'bankrollType,bankrollDirection') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata3.json'));
    } else if ($request->getParam('requireItems') == 'accountType,financeType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata4.json'));
    } else if ($request->getParam('requireItems') == 'commonStatus,switchType,settlementType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata5.json'));
    } else if ($request->getParam('requireItems') == 'merchantUserLevel,merchantUserStatus') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata6.json'));
    } else if ($request->getParam('requireItems') == 'platformType,commonStatus,openType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata7.json'));
    } else if ($request->getParam('requireItems') == 'productType,rateType,payType,commonStatus') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata8.json'));
    } else if ($request->getParam('requireItems') == 'channel') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata9.json'));
    } else if ($request->getParam('requireItems') == 'channel,commonStatus,switchType,settlementType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata10.json'));
    }else if ($request->getParam('requireItems') == 'commonStatus,switchType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata13.json'));
    }else if ($request->getParam('requireItems') == 'commonStatus,switchType,agentType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata13.json'));
    } else if ($request->getParam('requireItems') == 'dealType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata4.json'));
    } else if ($request->getParam('requireItems') == 'withdrawOrderType') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata13.json'));
    } else if ($request->getParam('requireItems') == 'psqlBankCode') {
        return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-getbasedata-psqlBankcode.json'));
    }

});
$app->get('/head', LoginController::class . ':head');
$app->get('/api/getbasedata', BaseDataController::class . ':index');
$app->get('/api/getrechargeOrderdata', BaseDataController::class . ':rechargeOrder');
$app->get('/api/getquickDefined', BaseDataController::class . ':quickDefined');
$app->get('/api/notify', BaseDataController::class . ':notify');

$app->get('/api/payorder/search', PayOrderController::class . ':search');
$app->get('/api/payorder/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-payorder-search.json'));
});

$app->get('/api/payorder/detail.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-payorder-detail.json'));
});
$app->get('/api/payorder/detail', PayOrderController::class . ':getDetail');
$app->post('/api/payorder/perfect', PayOrderController::class . ':perfect');
$app->get('/api/payorder/notify', PayOrderController::class . ':notify');

$app->get('/payorder/makeupcheck', PayOrderController::class . ':makeUpCheck');
$app->get('/api/payorder/getMakeUp', PayOrderController::class . ':getMakeUp');
$app->get('/api/payorder/domakeUpCheck', PayOrderController::class . ':domakeUpCheck');
$app->get('/api/payorder/getMakeUpList', PayOrderController::class . ':getMakeUpList');

$app->get('/api/payorder/getsuborderno', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-payorder-getsuborderno.json'));
});

$app->get('/settlementorder', SettlementOrderController::class . ':index');
$app->get('/api/settlementorder/getStatus', SettlementOrderController::class . ':getStatus');
$app->get('/settlementorder/detail', SettlementOrderController::class . ':detail');
$app->get('/api/settlementorder/detail', SettlementOrderController::class . ':getDetail');
$app->get('/api/settlementorder/notify', SettlementOrderController::class . ':notify');
$app->post('/api/settlementorder/perfect', SettlementOrderController::class . ':perfect');
$app->post('/api/settlementorder/lock', SettlementOrderController::class . ':lock');
$app->post('/api/settlementorder/unlock', SettlementOrderController::class . ':unlock');
$app->get('/api/settlementorder/offlineSettlement', SettlementOrderController::class . ':offlineSettlement');
$app->post('/api/settlementorder/systemSettlement/{orderId}', SettlementOrderController::class . ':systemSettlement');

$app->get('/settlementorder/makeupcheck', SettlementOrderController::class . ':makeUpCheck');
$app->get('/api/settlementorder/getMakeUp', SettlementOrderController::class . ':getMakeUp');
$app->get('/api/settlementorder/domakeUpCheck', SettlementOrderController::class . ':domakeUpCheck');
$app->get('/api/settlementorder/getMakeUpList', SettlementOrderController::class . ':getMakeUpList');

$app->get('/api/settlementorder/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-settlementorder-search.json'));
});

$app->get('/api/settlementorder/search', SettlementOrderController::class . ':search');

$app->get('/api/settlementorder/timeInterval', SettlementOrderController::class . ':timeInterval');

$app->get('/api/settlementorder/getsuborderno', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-settlementorder-getsuborderno.json'));
});
$app->get('/api/settlementorder/detail.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-settlementorder-detail.json'));
});
//代付充值订单
$app->get('/rechargeorder', RechargeOrderController::class . ':index');
$app->get('/api/rechargeorder/search', RechargeOrderController::class . ':rechargeOrderSearch');

$app->get('/balanceadjustment', BalanceadjustmentController::class . ':index');
$app->get('/api/balanceadjustment/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-balanceadjustment-search.json'));
});
$app->get('/api/balanceadjustment/search', BalanceadjustmentController::class . ':search');
$app->get('/api/balanceadjustment/insert', BalanceadjustmentController::class . ':insert');
$app->get('/api/balanceadjustment/getRandom', BalanceadjustmentController::class . ':getRandom');
$app->get('/api/balanceadjustment/balanceUnaudit', BalanceadjustmentController::class . ':balanceUnaudit');
$app->get('/api/balanceadjustment/balanceAudit', BalanceadjustmentController::class . ':balanceAudit');
$app->get('/api/balanceadjustment/unFreeze', BalanceadjustmentController::class . ':unFreeze');
$app->get('/api/balanceadjustment/getbasedata', BalanceadjustmentController::class . ':getbasedata');
$app->get('/api/balanceadjustment/getRechargeRate', BalanceadjustmentController::class . ':getRechargeRate');

$app->get('/account', AccountController::class . ':index');
$app->get('/api/account/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-account-search.json'));
});
$app->get('/api/account/search', AccountController::class . ':search');

$app->get('/finance', FinanceController::class . ':index');
$app->get('/api/finance/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-finance-search.json'));
});
$app->get('/api/finance/search', FinanceController::class . ':search');

$app->get('/chart/payorderamount', ChartController::class . ':payOrderAmount');
$app->get('/api/payorder//amount.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-payorder-amount.json'));
});

$app->get('/api/payorder//amount', ChartController::class . ':getPayOrderAmount');

$app->get('/chart/settlementorderamount', ChartController::class . ':settlementOrderAmount');
$app->get('/chart/revenueChart', ChartController::class . ':revenueChart');

$app->get('/chart/channeldaily', ChartController::class . ':channelDaily');
$app->get('/api/chart/getchanneldaily', ChartController::class . ':getChannelDaily');

$app->get('/api/settlementorder//amount.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-settlementorder-amount.json'));
});

$app->get('/api/settlementorder//amount', ChartController::class . ':getSettlementOrderAmount');
$app->get('/api/business/revenueChart', ChartController::class . ':getRevenueChart');
//$app->get('/api/business/revenueChart', ChartController::class . ':getRevenueChart');

$app->get('/chart/businessamount', ChartController::class . ':businessAmount');
$app->get('/api/business//amount', ChartController::class . ':getBusinessAmount');

$app->get('/chart/channelamount', ChartController::class . ':channelAmount');
$app->get('/api/channel//amount', ChartController::class . ':getChannelAmount');
//商户管理-商户信息管理
$app->get('/merchant', MerchantController::class . ':index');
$app->get('/api/merchant/detail.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-detail.json'));
});
$app->get('/api/merchant/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-search.json'));
});
$app->get('/api/merchant/search', MerchantController::class . ':search');
$app->get('/api/merchant/getnextmerchantno.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-getnextmerchantno.json'));
});
$app->get('/api/merchant/getnextmerchantno', MerchantController::class . ':getnextmerchantno');
$app->get('/api/merchant/insert', MerchantController::class . ':insert');
$app->get('/api/merchant/update', MerchantController::class . ':update');
$app->get('/api/merchant/resetset', MerchantController::class . ':resetset');
$app->get('/api/merchant/detail', MerchantController::class . ':getDetail');
//商户管理-商户用户管理
$app->get('/merchant/user', MerchantController::class . ':user');
$app->get('/api/merchant/user/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-user-search.json'));
});
$app->get('/api/merchant/user/search', MerchantController::class . ':userSearch');
$app->get('/api/merchant/user/update', MerchantController::class . ':userUpdate');
$app->get('/api/merchant/user/googleAuthSecretKey', MerchantController::class . ':googleAuthSecretKey');
$app->get('/api/merchant/user/resetloginpwd', MerchantController::class . ':resetloginpwd');
$app->get('/api/merchant/user/resetsecurepwd', MerchantController::class . ':resetsecurepwd');
$app->get('/api/merchant/user/resetloginpwd.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-user-resetloginpwd.json'));
});
$app->get('/merchant/audit', MerchantController::class . ':audit');
$app->get('/api/merchant/audit/password', MerchantController::class . ':auditpassword');
$app->get('/api/merchant/audit/resetpassword', MerchantController::class . ':resetpassword');
$app->get('/api/merchant/audit/disagreepassword', MerchantController::class . ':disagreepassword');

$app->get('/api/merchant/user/resetsecurepwd.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-user-resetloginpwd.json'));
});
//商户管理-电子商务平台管理
$app->get('/merchant/platform', MerchantController::class . ':platform');
$app->get('/api/merchant/platform/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-platform-search.json'));
});
$app->get('/api/merchant/platform/search', MerchantController::class . ':platformSearch');
$app->get('/api/merchant/platform/detail.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-platform-detail.json'));
});
$app->get('/api/merchant/platform/getsignkey.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-platform-getsignkey.json'));
});
$app->get('/api/merchant/platform/getnextmerchantno', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-platform-getnextmerchantno.json'));
});
$app->get('/api/merchant/platform/detail', MerchantController::class . ':platformDetail');
$app->get('/api/merchant/platform/update', MerchantController::class . ':platformUpdate');
$app->get('/api/merchant/platform/getsignkey', MerchantController::class . ':getsignkey');
$app->get('/api/merchant/platform/resetsignkey', MerchantController::class . ':resetsignkey');
//商户管理-商户费率管理
$app->get('/merchant/rate', MerchantController::class . ':rate');
$app->get('/api/merchant/rate/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-rate-search.json'));
});
$app->get('/api/merchant/rate/search', MerchantController::class . ':rateSearch');
$app->post('/api/merchant/rate/import', MerchantController::class . ':rateImport');
$app->get('/api/merchant/rate/export', MerchantController::class . ':rateExport');

$app->get('/decrypt', \App\Controllers\GM\DecryptController::class . ':index');
$app->post('/api/decrypt/file', \App\Controllers\GM\DecryptController::class . ':file');

//商户管理-商户转换费率配置
$app->get('/transform/rate', TransformController::class.':rate');
$app->get('/api/transform/rate/search', TransformController::class . ':rateSearch');
$app->get('/api/transform/rate/change', TransformController::class . ':rateChange');

//商户管理-商户支付渠道配置
$app->get('/merchant/paychannel', MerchantController::class . ':paychannel');
$app->get('/api/merchant/paychannel/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-paychannel-search.json'));
});
$app->get('/api/merchant/paychannel/search', MerchantController::class . ':paychannelSearch');
$app->post('/api/merchant/paychannel/import', MerchantController::class . ':paychannelImport');
$app->get('/api/merchant/paychannel/export', MerchantController::class . ':paychannelExport');

$app->get('/merchant/rechargechannel', MerchantController::class . ':rechargechannel');
$app->get('/api/merchant/rechargechannel/search', MerchantController::class . ':rechargechannelSearch');
$app->post('/api/merchant/rechargechannel/import', MerchantController::class . ':rechargechannelImport');
$app->get('/api/merchant/rechargechannel/export', MerchantController::class . ':rechargechannelExport');
$app->post('/api/merchant/rechargechannel/batchUpdate', MerchantController::class . ':rechargechannelBatchUpdate');
//消息公告
$app->get('/merchant/notice', MerchantController::class . ':notice');
$app->get('/api/merchant/notice', MerchantController::class . ':noticeSearch');
$app->get('/api/merchant/notice/create', MerchantController::class . ':createNotice');
$app->get('/api/merchant/notice/publish', MerchantController::class . ':publishNotice');
$app->get('/api/merchant/notice/delete', MerchantController::class . ':deleteNotice');
$app->get('/api/merchant/notice/getMerchantNo', MerchantController::class . ':getMerchantNo');


$app->get('/merchant/settlementchannel', MerchantController::class . ':settlementchannel');
$app->get('/api/merchant/settlementchannel/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-merchant-settlementchannel-search.json'));
});
$app->get('/api/merchant/settlementchannel/search', MerchantController::class . ':settlementchannelSearch');
$app->get('/api/merchant/settlementchannel/del', MerchantController::class . ':settlementchannelDel');
$app->post('/api/merchant/settlementchannel/import', MerchantController::class . ':settlementchannelImport');
$app->get('/api/merchant/settlementchannel/export', MerchantController::class . ':settlementchannelExport');
$app->post('/api/merchant/settlementchannel/batchUpdate', MerchantController::class . ':settlementchannelBatchUpdate');
//渠道商户信息管理
$app->get('/channel/merchant', ChannelController::class . ':merchant');
$app->get('/api/channel/merchant/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-channel-merchant-search.json'));
});
$app->get('/api/channel/merchant/search', ChannelController::class . ':merchantSearch');
//批量更新状态
$app->get('/api/channel/merchant/batchUpdate', ChannelController::class . ':batchUpdate');

$app->get('/api/channel/merchant/detail.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-channel-merchant-detail.json'));
});
$app->get('/api/channel/merchant/queryBalance', ChannelController::class . ':queryBalance');
$app->get('/api/channel/merchant/detail', ChannelController::class . ':getDetail');
$app->get('/api/channel/merchant/uploadCheckFile', ChannelController::class . ':uploadCheckFile');
$app->get('/api/channel/merchant/addBalance', ChannelController::class . ':addBalance');//手动充值余额
$app->get('/api/channel/merchant/resetset', ChannelController::class . ':resetset');
$app->post('/api/channel/merchant/insert', ChannelController::class . ':insert');
$app->post('/api/channel/merchant/update', ChannelController::class . ':update');
$app->get('/api/channel/merchant/getChannelParameter', ChannelController::class . ':getChannelParameter');

$app->get('/channel/paychannel', ChannelController::class . ':paychannel');
$app->get('/api/channel/paychannel/search', ChannelController::class . ':paychannelSearch');
$app->post('/api/channel/paychannel/import', ChannelController::class . ':paychannelImport');
$app->get('/api/channel/paychannel/export', ChannelController::class . ':paychannelExport');

$app->get('/channel/settlementchannel', ChannelController::class . ':settlementchannel');
$app->get('/api/channel/settlementchannel/search', ChannelController::class . ':settlementchannelSearch');
$app->post('/api/channel/settlementchannel/import', ChannelController::class . ':settlementchannelImport');
$app->get('/api/channel/settlementchannel/export', ChannelController::class . ':settlementchannelExport');

$app->get('/channel/settlementbalance', BalanceIssueController::class . ':settlementBalance');
$app->get('/api/channel/settlementbalance/search', BalanceIssueController::class . ':settlementBalanceSearch');
$app->get('/api/channel/settlementbalance/update', BalanceIssueController::class . ':settlementBalanceUpdate');
$app->get('/api/channel/settlementbalance/withdraw', BalanceIssueController::class . ':settlementBalanceWithdraw');
$app->get('/api/channel/settlementbalance/record/{channelId}', BalanceIssueController::class . ':settlementBalanceRecord');
$app->get('/api/channel/settlementbalance/withdrawSubmit', BalanceIssueController::class . ':submitBalanceWithdraw'); //审核渠道提款通过

$app->get('/channel/issuerecord', BalanceIssueController::class . ':issueRecord');
$app->get('/api/channel/issuerecord/search', BalanceIssueController::class . ':issueRecordSearch');
$app->get('/api/channel/issuerecord/orderquery', BalanceIssueController::class . ':orderQuery');

$app->get('/channel/rate', ChannelController::class . ':rate');
$app->get('/api/channel/rate/search.bak', function ($request, $response, $args) {
    return $response->write(file_get_contents(dirname(__FILE__) . '/testData/api-channel-rate-search.json'));
});
$app->get('/api/channel/rate/search', ChannelController::class . ':rateSearch');
$app->post('/api/channel/rate/import', ChannelController::class . ':rateImport');
$app->get('/api/channel/rate/export', ChannelController::class . ':rateExport');

$app->get('/manager/changeloginname', ManagerController::class . ':changeloginname');
$app->get('/api/manager/changeloginname', ManagerController::class . ':doChangeloginname');

$app->get('/manager/adminList', ManagerController::class . ':adminList');
$app->get('/api/manager//getmanagerlist', ManagerController::class . ':getmanagerlist');
$app->get('/api/manager/update', ManagerController::class . ':adminupdate');
$app->get('/api/manager/delaccount', ManagerController::class . ':delaccount');
$app->get('/manager/notices', ManagerController::class . ':noticesView');
$app->get('/api/manager/notices', ManagerController::class . ':notices');
$app->get('/api/manager/notice', ManagerController::class . ':notice');

//代付黑名单
$app->get('/manager/blackUserSettlement', ManagerController::class . ':blackUserSettlement');
$app->get('/api/manager/blackUserSettlement', ManagerController::class . ':blackUserSettlementList');
$app->get('/api/manager/blackUserSettlement/create', ManagerController::class . ':createBlackUserSettlement');
$app->get('/api/manager/blackUserSettlement/update', ManagerController::class . ':updateBlackUserSettlement');
$app->get('/api/manager/blackUserSettlement/delete', ManagerController::class . ':deleteBlackUserSettlement');


$app->get('/manager/changeloginpwd', ManagerController::class . ':changeloginpwd');
$app->get('/api/manager/changeloginpwd', ManagerController::class . ':doChangeloginpwd');

$app->get('/manager/messageInfoPage', ManagerController::class . ':messageInfoPage');
$app->get('/api/manager/messageInfo', ManagerController::class . ':messageInfo');
//$app->get('/api/manager/message', ManagerController::class . ':message');
$app->get('/api/manager/editRemarks', ManagerController::class . ':editRemarks');
$app->get('/api/manager/editEmail', ManagerController::class . ':editEmail');
$app->get('/api/manager/getEmail', ManagerController::class . ':getEmail');


$app->get('/ip/permission', function ($request, $response, $args) {
    return $response->withStatus(500)->write("IP not accessible (your ip address:" . Tools::getIp() . ")");
});
// $app->get('/', HomeController::class . ':index');

$app->get('/api/manager/googleauth', LoginController::class . ':doGoogleAuth');
$app->get('/googleauth', LoginController::class . ':googleAuth');

$app->get('/manager/bindgoogleauth', ManagerController::class . ':bindgoogleauth');
$app->get('/api/manager/bindgoogleauth', ManagerController::class . ':doBindgoogleauth');

// 银行管理
$app->get('/manager/bank', BankController::class . ':index');
$app->get('/api/manager/bank/search', BankController::class . ':search');
$app->get('/api/manager/bank/edit', BankController::class . ':edit');

$app->get('/check', CheckController::class . ':index');
$app->get('/api/check/search', CheckController::class . ':search');
$app->get('/api/check/modifyCheckPwd', CheckController::class . ':modifyCheckPwd');

$app->get('/check/makeup', CheckController::class . ':makeup');
$app->get('/api/check/getMakeUp', CheckController::class . ':getmakeUp');

//代理模块
$app->get('/agent', AgentController::class . ':index');
$app->get('/api/agent/search', AgentController::class . ':search');//代理账号信息查询
$app->get('/api/agent/editMoney', AgentController::class . ':editMoney');//资金管理操作
$app->get('/api/agent/updatePwd', AgentController::class . ':updatePwd');//重置代理登录密码和支付密码
$app->get('/api/agent/addAccount', AgentController::class . ':addAccount');//新增代理账号
$app->get('/api/agent/editAccount', AgentController::class . ':editAccount');//新增代理账号

$app->get('/agent/rate', AgentController::class . ':rate');//代理费率
$app->get('/api/agent/rateSearch', AgentController::class . ':rateSearch');//代理费率
$app->post('/api/agent/import', AgentController::class . ':rateImport');//代理费率配置上传
$app->get('/api/agent/export', AgentController::class . ':rateExport');//代理费率配置上传

$app->get('/agent/dataReport', AgentController::class . ':dataReport');//代理数据报表
$app->get('/api/agent/dataReport/search', AgentController::class . ':dataReportSearch');//代理数据报表查询
$app->get('/agent/agentFinance', AgentController::class . ':agentFinance');//代理资金记录
$app->get('/api/agent/agentFinance/search', AgentController::class . ':agentFinanceSearch');//代理资金记录查询
$app->get('/agent/withdrawOrder', AgentController::class . ':withdrawOrder');//代理提款申请记录
$app->get('/api/agent/withdrawOrder/search', AgentController::class . ':withdrawOrderSearch');//代理提款申请记录查询
$app->get('/api/agent/orderStatus', AgentController::class . ':orderStatus');//代理提款申请驳回/通过
$app->get('/api/agent/withdrewChannel', AgentController::class . ':withdrewChannel');//代理提款申请驳回/通过
$app->get('/api/agent/submitWithdrewByChannel', AgentController::class . ':submitWithdrewByChannel');//代理提款申请驳回/通过

$app->get('/manager/getAccountLoginLogView', ManagerController::class . ':getAccountLoginLogView');//管理员登录日志
$app->get('/api/manager/getAccountLoginLog', ManagerController::class . ':getAccountLoginLog');//管理员登录日志

$app->get('/manager/getAccountActionLogView', ManagerController::class . ':getAccountActionLogView');//管理员操作日志
$app->get('/api/manager/getAccountActionLog', ManagerController::class . ':getAccountActionLog');//管理员操作日志

