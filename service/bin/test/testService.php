<?php

// $v = [
//     'rateType' => 'Rate',
//     'minServiceCharge' => 0.01,
//     'maxServiceCharge' => 100,
//     'rate' => 0.0006,
// ];

// $orderData = [
//     'orderAmount' => 1000,
// ];
// $serviceCharge = 0;
// if ($v['rate'] > 0) {
//     if ($v['rateType'] == 'Rate') {
//         $serviceCharge = $v['rate'] * $orderData['orderAmount'];
//     } else if ($v['rateType'] == 'FixedValue') {
//         $serviceCharge = $v['rate'];
//     } else {
//         // continue;
//     }
// }

// dump($serviceCharge);
// if ($v['minServiceCharge'] > 0 && $serviceCharge < $v['minServiceCharge']) {
//     $serviceCharge = $v['minServiceCharge'];
// }

// if ($v['maxServiceCharge'] > 0 && $serviceCharge > $v['maxServiceCharge']) {
//     $serviceCharge = $v['maxServiceCharge'];
// }

// dump($serviceCharge);

// $data = '[{"setId":28,"merchantId":1,"merchantNo":"88888888","bankCode":"'","cardType":"DEBIT","channel":"mockTest","channelMerchantId":1,"channelMerchantNo":"99999999","payChannelStatus":"Normal","payType":"OnlineWechatQR","openTimeLimit":1,"beginTime":1000,"endTime":2130,"openOneAmountLimit":1,"oneMaxAmount":5000,"oneMinAmount":300,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":1,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"},{"setId":29,"merchantId":1,"merchantNo":"88888888","bankCode":"'","cardType":"DEBIT","channel":"mockTest","channelMerchantId":1,"channelMerchantNo":"99999999","payChannelStatus":"Normal","payType":"OnlineAlipayH5","openTimeLimit":0,"beginTime":0,"endTime":0,"openOneAmountLimit":1,"oneMaxAmount":5000,"oneMinAmount":20,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":1,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"},{"setId":30,"merchantId":1,"merchantNo":"88888888","bankCode":"'","cardType":"DEBIT","channel":"mockTest","channelMerchantId":1,"channelMerchantNo":"99999999","payChannelStatus":"Normal","payType":"OnlineAlipayQR","openTimeLimit":0,"beginTime":0,"endTime":0,"openOneAmountLimit":1,"oneMaxAmount":5000,"oneMinAmount":20,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":1,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"},{"setId":31,"merchantId":1,"merchantNo":"88888888","bankCode":"'","cardType":"DEBIT","channel":"dsdfpay","channelMerchantId":2,"channelMerchantNo":"10000001","payChannelStatus":"Normal","payType":"OnlineWechatQR","openTimeLimit":1,"beginTime":0,"endTime":0,"openOneAmountLimit":1,"oneMaxAmount":5000,"oneMinAmount":300,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":0,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"},{"setId":32,"merchantId":1,"merchantNo":"88888888","bankCode":"'","cardType":"DEBIT","channel":"dsdfpay","channelMerchantId":2,"channelMerchantNo":"10000001","payChannelStatus":"Normal","payType":"OnlineAlipayH5","openTimeLimit":0,"beginTime":0,"endTime":0,"openOneAmountLimit":1,"oneMaxAmount":5000,"oneMinAmount":20,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":0,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"},{"setId":33,"merchantId":1,"merchantNo":"88888888","bankCode":"'","cardType":"DEBIT","channel":"dsdfpay","channelMerchantId":2,"channelMerchantNo":"10000001","payChannelStatus":"Normal","payType":"OnlineAlipayQR","openTimeLimit":0,"beginTime":0,"endTime":0,"openOneAmountLimit":1,"oneMaxAmount":5000,"oneMinAmount":20,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":0,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"},{"setId":34,"merchantId":1,"merchantNo":"88888888","bankCode":"ABC","cardType":"DEBIT","channel":"mockTest","channelMerchantId":1,"channelMerchantNo":"99999999","payChannelStatus":"Normal","payType":"EBank","openTimeLimit":0,"beginTime":0,"endTime":0,"openOneAmountLimit":1,"oneMaxAmount":2000,"oneMinAmount":1,"openDayAmountLimit":0,"dayAmountLimit":0,"openDayNumLimit":0,"dayNumLimit":0,"priority":0,"status":"Normal","created_at":"2019-04-17 09:54:08","updated_at":"2019-04-17 09:54:08"}]';
// $merchantChannelData = json_decode($data, true);
// $res = (new MerchantChannel)->fetchConfig($merchantNo="88888888", $merchantChannelData, $payType='OnlineAlipayH5', $payMoney=100, $bankCode='', $cardType = 'DEBIT');
// $data = ['platformOrderNo' => 'P20190418120938871264'];
// (new PayNotifyExecutor)->push(0, $data['platformOrderNo']);

// $data = '[{
//     "rateId": 166,
//     "bankCode": "",
//     "cardType": "DEBIT",
//     "beginTime": "2018-09-16",
//     "endTime": null,
//     "maxServiceCharge": 999999,
//     "minServiceCharge": 0.10000000000000001,
//     "merchantId": 1,
//     "merchantNo": "88888888",
//     "payType": "OnlineAlipayH5",
//     "productType": "Pay",
//     "rate": 0.0060000000000000001,
//     "rateType": "Rate",
//     "status": "Normal",
//     "created_at": "2019-04-17 15:21:52",
//     "updated_at": "2019-04-17 15:21:52"
// }]';
// $merchantRateData = json_decode($data, true);
// (new \App\Models\MerchantChannel)->fetchConfig($merchantNo = "88888888", $merchantRateData, $payType = 'OnlineAlipayH5', $payMoney = 100, $bankCode = '', $cardType = 'DEBIT');

use App\Channels\ChannelProxy;
use App\Helpers\Tools;
use App\Models\ChannelMerchantRate;
use App\Models\Merchant;
use App\Models\MerchantChannelSettlement;
use App\Models\PlatformSettlementOrder;

$logger = $app->getContainer()->logger;
$channels = $app->getContainer()->code['channel'];
$platformPayOrder = new PlatformSettlementOrder;
$orderData = $platformPayOrder->getCacheByPlatformOrderNo('S20190702212356641705');
$merchant = new Merchant();
$merchantData = $merchant->getCacheByMerchantNo($orderData['merchantNo']);

$code = 'SUCCESS';
$merchantChannel = new MerchantChannelSettlement();
$merchantChannelData = $merchantChannel->getCacheByMerchantNo($orderData['merchantNo']);
if (empty($merchantChannelData)) {
    $code = 'E2003';
}

if ($code == 'SUCCESS') {
    $merchantChannelConfig = $merchantChannel->fetchConfig($orderData['merchantNo'], $merchantChannelData, $settlementType = '', $orderData['orderAmount'], \App\Helpers\Tools::decrypt($orderData['bankAccountNo']));
    if (empty($merchantChannelConfig)) {
        $code = 'E2003';
        // $logger->debug("merchantChannelSettelement fetchConfig失败", $merchantChannelData);
    }
}
if ($code == 'SUCCESS') {
    $channelMerchantRate = new ChannelMerchantRate;
    $channelOrder = null;
    shuffle($merchantChannelConfig);
    // dump($channelMerchantRate);
    foreach ($merchantChannelConfig as $channelConfig) {

        $orderData['channel'] = $channelConfig['channel'];
        $orderData['channelMerchantId'] = $channelConfig['channelMerchantId'];
        $orderData['channelMerchantNo'] = $channelConfig['channelMerchantNo'];

        $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);
        $orderData['channelServiceCharge'] = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $orderData, 'Settlement');
        dump($orderData['channelServiceCharge']);
        $logger->debug("新订单号==" . $orderData['orderId'] . "==渠道号与ID=====" . $orderData['channelMerchantId'] . "===" . $orderData['channelMerchantNo'], []);
        if ($orderData['channelServiceCharge'] === null) {
            continue;
        }

        $channelOrder = (new ChannelProxy)->getSettlementOrder($orderData);
        dump($channelOrder);

        if ($channelOrder['status'] == 'Success' || $channelOrder['status'] == 'DirectSuccess' || $channelOrder['status'] == 'Exception') {
            break;
        }
    }

    if ($orderData['channelServiceCharge'] === null) {
        $code = 'E9001';
        // $logger->error('channelServiceCharge', $channelMerchantRateData);
    }

    if ($code == 'SUCCESS') {
        $model = new PlatformSettlementOrder;
        if ($channelOrder['status'] == 'Success') {
            if ($model->start($orderData, $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $channelOrder['orderNo'], $orderData['channelServiceCharge'])) {
                $orderData['channel'] != 'InnerChannel' && (new SettlementActiveQueryExecutor)->push(0, $orderData['platformOrderNo']);

                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '',
                ]);
            } else {
                //处理start失败，仍然查询代付订单
                $orderData['channel'] != 'InnerChannel' && (new SettlementActiveQueryExecutor)->push(0, $orderData['platformOrderNo']);

                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '处理start失败',
                ]);
            }
        } else if ($channelOrder['status'] == 'DirectSuccess') {
            if ($model->directSuccess($orderData, $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $channelOrder['orderNo'], $orderData['channelServiceCharge'])) {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '',
                ]);
            } else {
                //处理失败，仍然查询代付订单
                $orderData['channel'] != 'InnerChannel' && (new SettlementActiveQueryExecutor)->push(0, $orderData['platformOrderNo']);

                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '处理directSuccess失败',
                ]);
            }
        } else if ($channelOrder['status'] == 'Exception') {
            if ($model->exception($orderData, $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $orderData['channelServiceCharge'])) {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '请求第三方异常:' . $channelOrder['failReason'],
                ]);
            } else {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '处理exception失败:' . $channelOrder['failReason'],
                ]);
            }
        } else {
            if ($model->fail($orderData, $processType = 'Success', $channelOrder['orderNo'], $failReason = '自动处理', $channelNoticeTime = '', $auditPerson = '',
                $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $orderData['channelServiceCharge'])) {
                //发送通知给调用方
                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $orderData['platformOrderNo']);
                }

                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '请求第三方返回代付失败:' . $channelOrder['failReason'],
                ]);
            } else {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '处理fail失败' . $channelOrder['failReason'],
                ]);
            }
        }
    }
}
