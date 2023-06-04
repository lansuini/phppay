<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\Tools;
use Illuminate\Database\Eloquent\Model;

class PlatformRechargeOrder extends Model
{
    protected $table = 'platform_recharge_order';

    protected $primaryKey = 'id';

    protected $fillable = [
        'merchantId',
        'merchantNo',
        'orderAmount',
        'realOrderAmount',
        'payType',
        'platformOrderNo',
        'orderStatus',
        'channel',
        'channelMerchantId',
        'channelSetId',
        'channelMerchantNo',
        'rateTemp',
        'channelNoticeTime',
        'serviceCharge',
        'channelServiceCharge',
        'orderReason',
        'agentFee'
    ];

    protected $errors;

    public function updateOrderStatusByOrderNo($platformOrderNo,$orderStatus,$realOrderAmount){

        global $app;
        $logger = $app->getContainer()->logger;
        $db = $app->getContainer()->database;

        try {

            $db->getConnection()->beginTransaction();
            $orderDataLock = self::where('platformOrderNo', $platformOrderNo)->lockForUpdate()->first();

            if(!$orderDataLock){
                $logger->info("代付充值订单不存在：$platformOrderNo");
                $db->getConnection()->rollback();
                return false;
            }
            if($orderDataLock['orderStatus'] != 'Transfered'){
                $logger->info("代付充值订单状态已改变：$platformOrderNo");
                $db->getConnection()->rollback();
                return false;
            }
            $rateConfig = json_decode($orderDataLock->rateTemp,true);
            if(!$rateConfig){
                $logger->error("代付费率配置为空：$platformOrderNo");
                $db->getConnection()->rollback();
                return false;
            }
            $orderDataLock->orderStatus = $orderStatus;
            $orderDataLock->realOrderAmount = $realOrderAmount;
            if($orderDataLock->orderAmount != $realOrderAmount){
                $orderDataLock->serviceCharge = $rateConfig['merchant']['fixed'] + ($realOrderAmount * $rateConfig['merchant']['rate']) ;
                $orderDataLock->channelserviceCharge = $rateConfig['channel']['fixed'] + ($realOrderAmount * $rateConfig['channel']['rate']) ;
            }

            $orderDataLock->chargeAmount = $realOrderAmount - $orderDataLock->serviceCharge ;
            $orderDataLock->channelNoticeTime = date('Y-m-d H:i:s');
            $orderDataLock->save();

            self::where('platformOrderNo',$platformOrderNo)->update(['orderStatus'=>$orderStatus,'realOrderAmount'=>$realOrderAmount]);
            if($orderStatus == 'Success'){
                $MerchantAmount = MerchantAmount::where('merchantId',$orderDataLock['merchantId'])->where('merchantNo',$orderDataLock['merchantNo'])->lockForUpdate()->first();

                $financePayInModel = new Finance();
                $financePayInModel->amount = $realOrderAmount;
                $financePayInModel->balance = $MerchantAmount->settlementAmount + $realOrderAmount;
                $financePayInModel->merchantId = $orderDataLock['merchantId'];
                $financePayInModel->merchantNo = $orderDataLock['merchantNo'];
                $financePayInModel->summary = '接口充值';
                $financePayInModel->sourceDesc = '接口充值加钱';
                $financePayInModel->sourceId = $orderDataLock['id'];
                $financePayInModel->financeType = 'PayIn';
                $financePayInModel->platformOrderNo = $platformOrderNo;
                $financePayInModel->accountDate = date('Y-m-d H:i:s');
                $financePayInModel->accountType = 'SettlementAccount';
                $financePayInModel->operateSource = 'ports';

                $financePayInModel->save();

                $financePyOutModel = new Finance();
                $financePyOutModel->amount = $orderDataLock->serviceCharge;
                $financePyOutModel->balance = $financePayInModel->balance - $orderDataLock->serviceCharge;
                $financePyOutModel->merchantId = $orderDataLock['merchantId'];
                $financePyOutModel->merchantNo = $orderDataLock['merchantNo'];
                $financePyOutModel->summary = '接口充值手续费';
                $financePyOutModel->sourceDesc = '接口充值扣除手续费';
                $financePyOutModel->sourceId = $orderDataLock['id'];
                $financePyOutModel->financeType = 'PayOut';
                $financePyOutModel->platformOrderNo = $platformOrderNo;
                $financePyOutModel->accountDate = date('Y-m-d H:i:s');
                $financePyOutModel->accountType = 'ServiceChargeAccount';
                $financePyOutModel->operateSource = 'ports';

                $MerchantAmount->settlementAmount = $MerchantAmount->settlementAmount + $realOrderAmount - $orderDataLock->serviceCharge;
                $MerchantAmount->save();
                $financePyOutModel->save();
            }
            //代理手续费
            $agentId = AgentMerchantRelation::where('merchantId',$orderDataLock['merchantId'])->value('agentId');
            if($agentId || isset($orderDataLock['agentFee']) && $orderDataLock['agentFee'] > 0) {
                $agentLog = new AgentIncomeLog();
                $agentLog->updateIncomeLog($orderDataLock['merchantId'],$platformOrderNo,$realOrderAmount,'recharge');
            }
            $db->getConnection()->commit();
            $this->setCacheByPlatformOrderNo($platformOrderNo,$orderDataLock->toArray());
            (new MerchantAmount)->refreshCache(['merchantId' => $orderDataLock['merchantId']]);
            return true;
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            $db->getConnection()->rollback();
            return false;
        }
    }

    public function setCacheByPlatformOrderNo($platformOrderNo, $data)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex("rechargeorder:" . $platformOrderNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));

    }

    public function getCacheByPlatformOrderNo($platformOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("rechargeorder:" . $platformOrderNo);
        return $data ? json_decode($data, true) : [];
    }
}
