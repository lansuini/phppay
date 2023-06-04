<?php
error_reporting(E_ALL);

# 网关地址
$setId = 19474;

$amount = 500;
$model = new \App\Models\MerchantChannelRecharge();
$merchantChannelRecharge = $model->where('setId',$setId)->first();

$merchantRateModel = new \App\Models\MerchantRate();
$merchantRateConfig = $merchantRateModel->where('merchantNo',$merchantChannelRecharge->merchantNo)
    ->where('status','Normal')
    ->where('productType','Recharge')
    ->where('payType',$merchantChannelRecharge->payType)
    ->first();


$rechargeOrder = new \App\Models\PlatformRechargeOrder();
$rechargeOrderN0 = 'R'.date('YmdHis') . rand(10000,999999);

$rechargeOrder->platformOrderNo = $rechargeOrderN0;
$rechargeOrder->merchantNo = $merchantChannelRecharge['merchantNo'];
$rechargeOrder->merchantId = $merchantChannelRecharge['merchantId'];
$rechargeOrder->channelMerchantId = $merchantChannelRecharge['channelMerchantId'];
$rechargeOrder->channelMerchantNo = $merchantChannelRecharge['channelMerchantNo'];
$rechargeOrder->orderAmount = $amount;
$rechargeOrder->realOrderAmount = $amount;
$rechargeOrder->serviceCharge = 0;
$rechargeOrder->channelServiceCharge = 0;
$rechargeOrder->channel = $merchantChannelRecharge['channel'];
$rechargeOrder->channelSetId = $merchantChannelRecharge['setId'];
$rechargeOrder->orderStatus = 'Transfered';
$rechargeOrder->payType = $merchantChannelRecharge->payType;
$rechargeOrder->orderReason = $params['orderReason'];
$rechargeOrder->agentFee = 0;

$merchantRateConfigTemp['rateType'] = 'FixedValue';
$merchantRateConfigTemp['rate'] = '0';
$merchantRateConfigTemp['fixed'] = '0';

$channelRateConfigTemp['rateType'] = 'FixedValue';
$channelRateConfigTemp['rate'] ='0';
$channelRateConfigTemp['fixed'] = '0';
$rechargeOrder->rateTemp = json_encode(['merchant'=>$merchantRateConfigTemp,'channel'=>$channelRateConfigTemp]);

$rechargeOrder->save();
$rechargeOrder->setCacheByPlatformOrderNo($rechargeOrderN0,$rechargeOrder->toArray());

$res = (new \App\Channels\ChannelProxy())->getRechargeOrder($rechargeOrder->toArray());

print_r($res);
echo 'finish', PHP_EOL;
