<?php
namespace App\Logics;
use App\Models\AmountPay;
use App\Models\AmountSettlement;
use App\Models\PlatformRechargeOrder;
use App\Models\PlatformPayOrder;
use App\Models\PlatformSettlementOrder;
use App\Models\ChannelDailyStats;
use App\Models\ChannelMerchant;
use Illuminate\Support\Facades\DB;

/**
 * 上游渠道模块
 */
class ChannelLogic extends Logic {
    //所有的统计
    public function getStats(){
        $today = date('Y-m-d');
        $yday = date('Y-m-d', strtotime("-1 day"));//昨天
        //支付最早日期
        $payData = AmountPay::orderBy('id', 'asc')->first();
        if(empty($payData)){
            $payDay = $today;
        }else{
            $payData = $payData->toArray();
            $payDay = $payData['accountDate'];
        }
        //代付最早日期
        $setlementData = AmountSettlement::orderBy('id', 'asc')->first();
        if(empty($setlementData)){
            $setlementDay = $today;
        }else{
            $setlementData = $setlementData->toArray();
            $setlementDay = $setlementData['accountDate'];
        }
        //充值最早日期
        $chargeData = PlatformRechargeOrder::orderBy('updated_at', 'asc')->first();
        if(empty($chargeData)){
            $chargeDay = $today;
        }else{
            $chargeData = $chargeData->toArray();
            $chargeDay = date('Y-m-d', strtotime($chargeData['updated_at']));
        }
        $tmp = $payDay < $setlementDay ? $payDay : $setlementDay;
        $startDay = $tmp < $chargeDay ? $tmp : $chargeDay;
        if($startDay == $today){
            return false;
        }
        while($startDay){
            $this->dayStats($startDay);
            $eDay = date('Y-m-d', strtotime("+1 day", strtotime($startDay)));//明天
            if($eDay >= $yday){
                break;
            }else{
                $startDay = $eDay;
            }
        }
    }

    //根据日期获取商户的支付，代付，充值
    public function dayStats($startDay){
        $channels = ChannelMerchant::get(['channelMerchantId', 'channelMerchantNo'])->toArray();

        foreach ($channels as $k=>$channel){
            $where = [
                ['channelMerchantNo', $channel['channelMerchantNo']],
                ['orderStatus', 'Success'],
                ['updated_at', '>=', $startDay.' 00:00:00'],
                ['updated_at', '<=', $startDay.' 23:59:59'],
            ];
            //支付
            $payData = PlatformPayOrder::where($where)->selectRaw('count(orderId) as pcount, sum(realOrderAmount) as pamount, sum(serviceCharge) as pcharge, sum(channelServiceCharge) as pservice, sum(agentFee) as agentPayFees')->first();
            if(empty($payData)){
                $payData = ['pcount'=>0, 'pamount'=>'0.00', 'pcharge'=>'0.00', 'pservice'=>'0.00', 'agentPayFees'=>'0.00'];
            }else{
                $payData = $payData->toArray();
            }
            //代付
            $setlementData = PlatformSettlementOrder::where($where)->selectRaw('count(orderId) as scount, sum(realOrderAmount) as samount, sum(serviceCharge) as scharge, sum(channelServiceCharge) as sservice, sum(agentFee) as agentsettlementFees')->first();
            if(empty($setlementData)){
                $setlementData = ['scount'=>0, 'samount'=>'0.00', 'scharge'=>'0.00', 'sservice'=>'0.00', 'agentsettlementFees'=>'0.00'];
            }else{
                $setlementData = $setlementData->toArray();
            }
            //充值
            $chargeData = PlatformRechargeOrder::where($where)->selectRaw('count(id) as ccount, sum(realOrderAmount) as camount, sum(serviceCharge) as ccharge, sum(channelServiceCharge) as cservice, sum(agentFee) as agentchargeFees')->first();
            if(empty($chargeData)){
                $chargeData = ['ccount'=>0, 'camount'=>'0.00', 'ccharge'=>'0.00', 'cservice'=>'0.00', 'agentchargeFees'=>'0.00'];
            }else{
                $chargeData = $chargeData->toArray();
            }
            if((empty($payData['pamount']) || $payData['pamount']=='0.00') && (empty($setlementData['samount']) || $setlementData['samount']=='0.00') && (empty($chargeData['camount']) || $chargeData['camount']=='0.00')){
                continue;
            }else{
                ChannelDailyStats::insert([
                    'channelMerchantId'=>$channel['channelMerchantId'],
                    'channelMerchantNo'=>$channel['channelMerchantNo'],
                    'payCount'=>$payData['pcount'] ? $payData['pcount'] : 0,
                    'payAmount'=>$payData['pamount'] ? $payData['pamount'] : 0,
                    'payServiceFees'=>$payData['pcharge'] ? $payData['pcharge'] : 0,
                    'payChannelServiceFees'=>$payData['pservice'] ? $payData['pservice'] : 0,
                    'agentPayFees'=>$payData['agentPayFees'] ? $payData['agentPayFees'] : 0,

                    'settlementCount'=>$setlementData['scount'] ? $setlementData['scount'] : 0,
                    'settlementAmount'=>$setlementData['samount'] ? $setlementData['samount'] : 0,
                    'settlementServiceFees'=>$setlementData['scharge'] ? $setlementData['scharge'] : 0,
                    'settlementChannelServiceFees'=>$setlementData['sservice'] ? $setlementData['sservice'] : 0,
                    'agentsettlementFees'=>$setlementData['agentsettlementFees'] ? $setlementData['agentsettlementFees'] : 0,

                    'chargeCount'=>$chargeData['ccount'] ? $chargeData['ccount'] : 0,
                    'chargeAmount'=>$chargeData['camount'] ? $chargeData['camount'] : 0,
                    'chargeServiceFees'=>$chargeData['ccharge'] ? $chargeData['ccharge'] : 0,
                    'chargeChannelServiceFees'=>$chargeData['cservice'] ? $chargeData['cservice'] : 0,
                    'agentchargeFees'=>$chargeData['agentchargeFees'] ? $chargeData['agentchargeFees'] : 0,

                    'accountDate'=>$startDay
                ]);
            }
        }
    }
}

