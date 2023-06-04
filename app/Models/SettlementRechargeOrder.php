<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/7/31
 * Time: 16:40
 */

namespace App\Models;

use App\Models\MerchantAmount;
use Illuminate\Database\Eloquent\Model;

class SettlementRechargeOrder extends Model
{

    protected $table = 'settlement_recharge_order';

//    protected $primaryKey = 'id';

    protected $fillable = [
      'settlementRechargeOrderNo',
      'merchantNo',
      'merchantId',
      'orderAmount',
      'realOrderAmount',
      'channel',
      'channelMerchantId',
      'channelSetId',
      'channelMerchantNo',
      'channelNoticeTime',
      'created_at',
      'updated_at',
      'orderStatus',
      'type',
        'agentFee'
    ];

    public function updateOrderStatusByOrderNo($platformOrderNo,$orderStatus,$realOrderAmount,$channelConfig = []){

        global $app;
        $logger = $app->getContainer()->logger;
        $db = $app->getContainer()->database;
        $orderData = $this->getCacheByPlatformOrderNo($platformOrderNo);
        if(!$orderData){
            $logger->info("代付充值订单不存在：$platformOrderNo");
            return true;
        }
        if($orderData['orderStatus'] != 'Transfered'){
            $logger->info("代付充值订单状态已改变：$platformOrderNo");
            return true;
        }

        $rateConfig = isset($channelConfig[$orderData['type']]) && !empty($channelConfig[$orderData['type']]) ? $channelConfig[$orderData['type']] : [] ;

        if(!$rateConfig){
            $logger->info("代付费率配置为空：$platformOrderNo");
            return false;
        }
        try {

            $db->getConnection()->beginTransaction();
            $orderDataLock = self::where('settlementRechargeOrderNo', $platformOrderNo)->lockForUpdate()->first();

            $orderDataLock->orderStatus = $orderStatus;
            $orderDataLock->realOrderAmount = $realOrderAmount;
            $orderDataLock->serviceCharge = $rateConfig['xiayouFixed'] + ($realOrderAmount * $rateConfig['xiayouPercent']) ;
            $orderDataLock->channelserviceCharge = $rateConfig['shangyouFixed'] + ($realOrderAmount * $rateConfig['shangyouPercent']) ;
            $orderDataLock->chargeAmount = $realOrderAmount - $orderDataLock->serviceCharge ;
            $orderDataLock->channelNoticeTime = date('Y-m-d H:i:s');
            $orderDataLock->save();

            $res = self::where('settlementRechargeOrderNo',$platformOrderNo)->update(['orderStatus'=>$orderStatus,'realOrderAmount'=>$realOrderAmount]);
            if($orderStatus == 'Success'){
                $MerchantAmount = MerchantAmount::where('merchantId',$orderData['merchantId'])->where('merchantNo',$orderData['merchantNo'])->lockForUpdate()->first();

                $financePayInModel = new Finance();
                $financePayInModel->amount = $realOrderAmount;
                $financePayInModel->balance = $MerchantAmount->settlementAmount + $realOrderAmount;
                $financePayInModel->merchantId = $orderData['merchantId'];
                $financePayInModel->merchantNo = $orderData['merchantNo'];
                $financePayInModel->summary = '接口充值';
                $financePayInModel->sourceDesc = '接口充值加钱';
                $financePayInModel->sourceId = $orderData['id'];
                $financePayInModel->financeType = 'PayIn';
                $financePayInModel->platformOrderNo = $platformOrderNo;
                $financePayInModel->accountDate = date('Y-m-d H:i:s');
                $financePayInModel->accountType = 'SettlementAccount';
                $financePayInModel->operateSource = 'ports';

                $financePayInModel->save();

                $financePyOutModel = new Finance();
                $financePyOutModel->amount = $orderDataLock->serviceCharge;
                $financePyOutModel->balance = $financePayInModel->balance - $orderDataLock->serviceCharge;
                $financePyOutModel->merchantId = $orderData['merchantId'];
                $financePyOutModel->merchantNo = $orderData['merchantNo'];
                $financePyOutModel->summary = '接口充值手续费';
                $financePyOutModel->sourceDesc = '接口充值扣除手续费';
                $financePyOutModel->sourceId = $orderData['id'];
                $financePyOutModel->financeType = 'PayOut';
                $financePyOutModel->platformOrderNo = $platformOrderNo;
                $financePyOutModel->accountDate = date('Y-m-d H:i:s');
                $financePyOutModel->accountType = 'ServiceChargeAccount';
                $financePyOutModel->operateSource = 'ports';

                $MerchantAmount->settlementAmount = $MerchantAmount->settlementAmount + $realOrderAmount - $orderDataLock->serviceCharge;
                $MerchantAmount->save();
                $financePyOutModel->save();
            }
            $db->getConnection()->commit();
            $this->setCacheByPlatformOrderNo($platformOrderNo,$orderDataLock->toArray());
            (new MerchantAmount)->refreshCache(['merchantId' => $MerchantAmount->merchantId]);
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
        $redis->setex("settlementrechargeorder:" . $platformOrderNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
//        if (!empty($data['merchantOrderNo'])) {
//            $redis->setex("settlementrechargeorder:m:" . $data['merchantNo'] . ":" . $data['merchantOrderNo'], 7 * 86400, $platformOrderNo);
//        }
    }

    public function getCacheByPlatformOrderNo($platformOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("settlementrechargeorder:" . $platformOrderNo);
        return $data ? json_decode($data, true) : [];
    }
}