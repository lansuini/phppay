<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\Tools;
use Illuminate\Database\Eloquent\Model;

class PlatformPayOrder extends Model
{
    protected $table = 'platform_pay_order';

    protected $primaryKey = 'orderId';

    protected $fillable = [
        'merchantId',
        'merchantNo',
        'merchantOrderNo',
        'merchantReqTime',
        'orderAmount',
        'tradeSummary',
        'payModel',
        'payType',
        'bankCode',
        'cardType',
        'userTerminal',
        'userIp',
        'thirdUserId',
        'cardHolderName',
        'cardNum',
        'idType',
        'idNum',
        'cardHolderMobile',
        'frontNoticeUrl',
        'backNoticeUrl',
        'merchantParam',
        'platformOrderNo',
        'orderStatus',
        'channel',
        'channelMerchantId',
        'channelSetId',
        'channelMerchantNo',
        'channelNoticeTime',
        'channelOrderNo',
        'pushChannelTime',
        'processType',
        'transactionNo',
        'serviceCharge',
        'channelServiceCharge',
        'realOrderAmount',
        'callbackLimit',
        'callbackSuccess',
        'agentFee'
    ];

    protected $errors;

    public function create($request,
        $platformOrderNo,
        $orderAmount,
        $channel,
        $channelMerchantId,
        $channelMerchantNo,
        $channelSetId,
        $channelOrderNo,
        $serviceCharge,
        $channelServiceCharge) {
        $merchant = new Merchant;
        $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
        //代理手续费
        $agentId = AgentMerchantRelation::where('merchantId',$merchantData['merchantId'])->value('agentId');
        if($agentId) {
            $agentLog = new AgentIncomeLog();
            //代付订单类型只有一种
            $agentFee = $agentLog->getFee($agentId,$merchantData['merchantId'],$platformOrderNo,$orderAmount,'pay',$request->getParam('payType'),$request->getParam('bankCode'));

            $agentName=Agent::where('id',$agentId)->value('loginName');
        }else {
            $agentFee = 0;
            $agentName='';
        }

        $createdOrder = self::firstOrCreate([
            'merchantId' => $merchantData['merchantId'],
            'merchantNo' => $request->getParam('merchantNo'),
            'merchantOrderNo' => $request->getParam('merchantOrderNo'),
            'merchantReqTime' => $request->getParam('merchantReqTime'),
            'orderAmount' => $orderAmount,
            'tradeSummary' => $request->getParam('tradeSummary', ''),
            'payModel' => $request->getParam('payModel'),
            'payType' => $request->getParam('payType'),
            'bankCode' => $request->getParam('bankCode', ''),
            'cardType' => $request->getParam('cardType'),
            'userTerminal' => $request->getParam('userTerminal', ''),
            'userIp' => $request->getParam('userIp'),
            'thirdUserId' => $request->getParam('thirdUserId', ''),
            'cardHolderName' => $request->getParam('cardHolderName', ''),
            'cardNum' => Tools::encrypt($request->getParam('cardNum', '')),
            'idType' => $request->getParam('idType', ''),
            'idNum' => Tools::encrypt($request->getParam('idNum', '')),
            'cardHolderMobile' => Tools::encrypt($request->getParam('cardHolderMobile', '')),
            'frontNoticeUrl' => $request->getParam('frontNoticeUrl', ''),
            'backNoticeUrl' => $request->getParam('backNoticeUrl'),
            'merchantParam' => $request->getParam('merchantParam', ''),
            // 'platformOrderNo' => Tools::getPlatformOrderNo('P'),
            'platformOrderNo' => $platformOrderNo,
            'orderStatus' => 'WaitPayment',
            'channel' => $channel,
            'channelMerchantId' => $channelMerchantId,
            'channelSetId' => $channelSetId,
            'channelMerchantNo' => $channelMerchantNo,
            // 'channelNoticeTime' => $request->getParam(''),
            'channelOrderNo' => $channelOrderNo,
            'pushChannelTime' => date('Y-m-d H:i:s'),
            'processType' => 'WaitPayment',
            // 'transactionNo' => $request->getParam(''),
            'serviceCharge' => $serviceCharge,
            'channelServiceCharge' => $channelServiceCharge,
            'realOrderAmount' => $orderAmount,
            'agentFee' => $agentFee,
            'agentName'=>$agentName
        ]);

        $this->setCacheByPlatformOrderNo($platformOrderNo, $createdOrder);
        return $createdOrder;
    }

    public function getErrorMessage()
    {
        return $this->errors;
    }

    public function success($orderData, $orderAmount = 0, $processType = 'Success', $channelOrderNo = '', $channelNoticeTime = '')
    {
        global $AmountPayp;

        $db = $AmountPayp->getContainer()->database;
        $logger = $AmountPayp->getContainer()->logger;
        try {

            $db->getConnection()->beginTransaction();
            $merchantRate = new MerchantRate();
            $AmountPay = new AmountPay;
            $ppo = new PlatformPayOrder;
            $finance = new Finance;
            $merchantAmount = new MerchantAmount;
            $merchantData = (new Merchant)->getCacheByMerchantId($orderData['merchantId']);
            $channelNoticeTime = empty($channelNoticeTime) ? date('YmdHis') : $channelNoticeTime;
            $accountDate = Tools::getAccountDate($merchantData['settlementTime'], $channelNoticeTime);
            $channelOrderNo = empty($channelOrderNo) ? $orderData['channelOrderNo'] : $channelOrderNo;

            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();

            AmountPay::updateOrCreate([
                'merchantId' => $orderData['merchantId'],
                'merchantNo' => $orderData['merchantNo'],
                'channelMerchantId' => $orderData['channelMerchantId'],
                'channelMerchantNo' => $orderData['channelMerchantNo'],
                'payType' => $orderData['payType'],
                'accountDate' => $accountDate,
            ]);

            $AmountPayLockData = $AmountPay->where('merchantId', $orderData['merchantId'])
                ->where('channelMerchantId', $orderData['channelMerchantId'])
                ->where('payType', $orderData['payType'])
                ->where('accountDate', $accountDate)
                ->lockForUpdate()
                ->first();
            $merchantAmountData = $merchantAmount->where('merchantId', $orderData['merchantId'])->lockForUpdate()->first();

            if (empty($orderData) || $orderData['orderStatus'] != 'WaitPayment' || $orderData['processType'] != 'WaitPayment') {
                throw new \Exception("数据已处理，或不存在:" . $orderData['platformOrderNo']);
            }

            $orderDataLock->channelNoticeTime = $channelNoticeTime;
            $orderDataLock->orderStatus = 'Success';
            $orderDataLock->processType = $processType;
            $orderDataLock->accountDate = $accountDate;
            $orderDataLock->channelOrderNo = $channelOrderNo;

            if (!empty($orderData['payType']) && $orderDataLock->payType != $orderData['payType']) {
                $orderDataLock->payType = $orderData['payType'];
            }

            if ($orderAmount > 0 && $orderAmount != $orderData['orderAmount']) {
                $orderData['realOrderAmount'] = $orderAmount;
                $orderDataLock->realOrderAmount = $orderAmount;
                $merchantRateData = $merchantRate->getCacheByMerchantId($orderData['merchantId']);
                $orderDataLock->serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $orderData, 'Pay');

                $channelMerchantRate = new ChannelMerchantRate;
                $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($orderDataLock->channelMerchantId);

                if ($orderDataLock->serviceCharge === null) {
                    throw new \Exception("商户费率不存在:" . $orderData['platformOrderNo']);
                }

                $orderDataLock->channelServiceCharge = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $orderData, 'Pay');
                if ($orderDataLock->channelServiceCharge === null) {
                    throw new \Exception("渠道费率不存在");
                }
            }

            $orderDataLock->save();
            $AmountPayLockData->amount = $AmountPayLockData->amount + $orderDataLock->realOrderAmount;
            $AmountPayLockData->serviceCharge = $AmountPayLockData->serviceCharge + $orderDataLock->serviceCharge;
            $AmountPayLockData->channelServiceCharge = $AmountPayLockData->channelServiceCharge + $orderDataLock->channelServiceCharge;
            $AmountPayLockData->save();

            $fd = [
                [
                    'merchantId' => $orderDataLock->merchantId,
                    'merchantNo' => $orderDataLock->merchantNo,
                    'platformOrderNo' => $orderDataLock->platformOrderNo,
                    'amount' => $orderDataLock->realOrderAmount,
                    'balance' => $merchantAmountData->settlementAmount + $orderDataLock->realOrderAmount,
                    'financeType' => 'PayIn',
                    'accountDate' => $accountDate,
                    'accountType' => 'SettlementAccount',
                    'sourceId' => $orderDataLock->orderId,
                    'sourceDesc' => '支付服务',
                    'merchantOrderNo' => $orderDataLock->merchantOrderNo,
                    'operateSource' => 'ports',
                    'summary' => $orderDataLock->tradeSummary,
                ],

                [
                    'merchantId' => $orderDataLock->merchantId,
                    'merchantNo' => $orderDataLock->merchantNo,
                    'platformOrderNo' => $orderDataLock->platformOrderNo,
                    'amount' => $orderDataLock->serviceCharge,
                    'balance' => $merchantAmountData->settlementAmount + $orderDataLock->realOrderAmount - $orderDataLock->serviceCharge,
                    'financeType' => 'PayOut',
                    'accountDate' => $accountDate,
                    'accountType' => 'ServiceChargeAccount',
                    'sourceId' => $orderDataLock->orderId,
                    'sourceDesc' => '支付手续费',
                    'merchantOrderNo' => $orderDataLock->merchantOrderNo,
                    'operateSource' => 'ports',
                    'summary' => $orderDataLock->tradeSummary,

                ],
            ];
            Finance::insert($fd);

            $merchantAmountData->settlementAmount = $merchantAmountData->settlementAmount + $orderDataLock->realOrderAmount - $orderDataLock->serviceCharge;
            // $merchantAmountData->settledAmount = $merchantAmountData->settledAmount + ($orderDataLock->orderAmount - $orderDataLock->serviceCharge) * $merchantData['D0SettlementRate'];
            $merchantAmountData->save();

            $merchantAmountData->refreshCache(['merchantId' => $merchantAmountData->merchantId]);
//            $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
            (new MerchantChannel)->incrCacheByDayAmountLimit($orderData['merchantNo'], $orderData['channelMerchantNo'], $orderData['payType'], $orderData['bankCode'], $orderData['cardType'], intval($orderData['realOrderAmount'] * 100));
            (new MerchantChannel)->incrCacheByDayNumLimit($orderData['merchantNo'], $orderData['channelMerchantNo'], $orderData['payType'], $orderData['bankCode'], $orderData['cardType']);
            (new ChannelPayConfig)->incrCacheByDayAmountLimit($orderData['channelMerchantNo'], $orderData['payType'], $orderData['bankCode'], $orderData['cardType'], intval($orderData['realOrderAmount'] * 100));
            (new ChannelPayConfig)->incrCacheByDayNumLimit($orderData['channelMerchantNo'], $orderData['payType'], $orderData['bankCode'], $orderData['cardType']);

            AmountPay::where('merchantId', $orderData['merchantId'])
                ->where('accountDate', $accountDate)
                ->update(['balance' => $merchantAmountData->settlementAmount]);

            //代理手续费
            $agentId = AgentMerchantRelation::where('merchantId',$orderData['merchantId'])->value('agentId');
            if($agentId||isset($orderData['agentFee']) && $orderData['agentFee'] > 0) {
                $agentLog = new AgentIncomeLog();
                $agentLog->updateIncomeLog($orderData['merchantId'],$orderData['platformOrderNo'],$orderAmount,'pay');
            }

            $db->getConnection()->commit();

        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $logger->error('Exception:修改支付订单失败1' . $orderData['platformOrderNo'] . ':' .$e->getMessage());
            $db->getConnection()->rollback();
            return false;
        }
        $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
        return true;
    }

    public function fail($orderData, $processType = 'Success', $channelOrderNo = '', $channelNoticeTime = '')
    {
        global $AmountPayp;
        $db = $AmountPayp->getContainer()->database;
        $logger = $AmountPayp->getContainer()->logger;
        $redis = $AmountPayp->getContainer()->redis;
        $channelNoticeTime = empty($channelNoticeTime) ? date('YmdHis') : $channelNoticeTime;
        $merchantData = (new Merchant)->getCacheByMerchantId($orderData['merchantId']);
        $accountDate = Tools::getAccountDate($merchantData['settlementTime'], $channelNoticeTime);
        $channelOrderNo = empty($channelOrderNo) ? $orderData['channelOrderNo'] : $channelOrderNo;
        try {
            $db->getConnection()->beginTransaction();

            $ppo = new PlatformPayOrder;
            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();
            $orderDataLock->channelNoticeTime = $channelNoticeTime;
            $orderDataLock->orderStatus = 'Expired';
            $orderDataLock->processType = $processType;
            if ($orderDataLock->processType == 'Expired') {
                $orderDataLock->timeoutTime = date('YmdHis');
            }
            $orderDataLock->channelOrderNo = $channelOrderNo;
            $orderDataLock->save();
            $db->getConnection()->commit();
//            $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $logger->error('Exception:修改支付订单失败2' . $orderData['platformOrderNo'] . ':' .$e->getMessage());
            $db->getConnection()->rollback();
            return false;
        }
        $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
        return true;
    }

    public function setCacheByPlatformOrderNo($platformOrderNo, $data)
    {
        global $AmountPayp;
        $redis = $AmountPayp->getContainer()->redis;
        $redis->setex("payorder:" . $platformOrderNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
        if (!empty($data['merchantOrderNo'])) {
            $redis->setex("payorder:m:" . $data['merchantNo'] . ":" . $data['merchantOrderNo'], 7 * 86400, $platformOrderNo);
        }
    }

    public function getCacheByPlatformOrderNo($platformOrderNo)
    {
        global $AmountPayp;
        $redis = $AmountPayp->getContainer()->redis;
        $data = $redis->get("payorder:" . $platformOrderNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantOrderNo($merchantNo, $merchantOrderNo)
    {
        global $AmountPayp;
        $redis = $AmountPayp->getContainer()->redis;
        $platformOrderNo = $redis->get("payorder:m:" . $merchantNo . ":" . $merchantOrderNo);
        if (empty($platformOrderNo)) {
            return [];
        }
        return $this->getCacheByPlatformOrderNo($platformOrderNo);
    }
}
