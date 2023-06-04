<?php

namespace App\Models;

use App\Helpers\Tools;
use Illuminate\Database\Eloquent\Model;

class PlatformSettlementOrder extends Model
{
    protected $table = 'platform_settlement_order';

    protected $primaryKey = 'orderId';

    protected $fillable = [
        'merchantId',
        'merchantNo',
        'merchantOrderNo',
        'merchantReqTime',
        'orderAmount',
        'serviceCharge',
        // 'settlementAccountType',
        // 't0ServiceCharge',
        // 't0SettlementAmount',
        // 't1ServiceCharge',
        // 'holidayServiceCharge',
        // 'holidaySettlementAmount',
        'failReason',
        'channel',
        'channelMerchantId',
        'channelSetId',
        'channelMerchantNo',
        'channelOrderNo',
        'channelNoticeTime',
        'orderReason',
        'orderStatus',
        'orderType',
        'backNoticeUrl',
        'platformOrderNo',
        'merchantParam',
        'bankLineNo',
        'bankCode',
        'bankName',
        'bankAccountName',
        'bankAccountNo',
        'city',
        'province',
        'userIp',
        'applyPerson',
        'applyIp',
        'accountDate',
        'auditPerson',
        'auditIp',
        'auditTime',
        'tradeSummary',
        'processType',
        'channelServiceCharge',
        'realOrderAmount',
        'callbackLimit',
        'callbackSuccess',
        'pushChannelTime',
        'agentFee'
    ];

    public static $voiceBalance = 5000;

    protected function isRollbackMerchantChannelSettleDayLimit($orderData)
    {
        if ($orderData['orderStatus'] == 'Fail'
            && !empty($orderData['pushChannelTime'])
            && Tools::isToday($orderData['pushChannelTime'])
            && !empty($orderData['channelNoticeTime'])
            && Tools::isToday($orderData['channelNoticeTime'])) {
            return true;
        }

        return false;
    }

    protected function isRollbackMerchantSettleDayLimit($orderData)
    {
        if ($orderData['orderStatus'] == 'Fail'
            && Tools::isToday($orderData['created_at'])
            && !empty($orderData['channelNoticeTime'])
            && Tools::isToday($orderData['channelNoticeTime'])) {
            return true;
        }

        return false;
    }

    public function create($request,
        $platformOrderNo,
        $orderAmount,
        // $isWorkerday,
        $settlementType,
        $serviceCharge,
        // $channelServiceCharge,
        $merchantNo,
        // $channel,
        // $channelMerchantId,
        // $channelMerchantNo,
        // $channelSetId,
        // $settlementAccountType,
        $applyPerson = '',
        $batchData = []    //用于批量代付时  传参数生成代付订单
    ) {
        $merchant = new Merchant;
        $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo', $merchantNo));
        //代理手续费
        $agentId = AgentMerchantRelation::where('merchantId',$merchantData['merchantId'])->value('agentId');
        if($agentId) {
            $agentLog = new AgentIncomeLog();
            //代付订单类型只有一种
            $agentFee = $agentLog->getFee($agentId,$merchantData['merchantId'],$platformOrderNo,$orderAmount,'settlement','D0Settlement');

            $agentName=Agent::where('id',$agentId)->value('loginName');
        }else {
            $agentFee = 0;
            $agentName='';
        }
        $batchData = array_merge([
            'bankAccountName' => '',
            'bankAccountNo' => '',
            'bankCode' => '',
            'bankName' => '',
            'city' => '',
            'province' => '',
            'orderReason' => '',
        ],$batchData);
        $data = [
            'merchantId' => $merchantData['merchantId'],
            'merchantNo' => $request->getParam('merchantNo', $merchantNo),
            'merchantOrderNo' => $request->getParam('merchantOrderNo', ''),
            'merchantReqTime' => $request->getParam('merchantReqTime', date('YmdHis')),
            'orderAmount' => $orderAmount,
            'tradeSummary' => $request->getParam('tradeSummary', ''),
            'orderReason' => $batchData['orderReason'] ?? $request->getParam('orderReason'),
            'bankAccountName' => $request->getParam('bankAccountName',$batchData['bankAccountName']),
//            'bankAccountNo' => Tools::encrypt($request->getParam('bankAccountNo')) ?? $batchData['bankAccountNo'],
            'bankAccountNo' => Tools::encrypt($request->getParam('bankAccountNo')) !='' ?  Tools::encrypt($request->getParam('bankAccountNo')): $batchData['bankAccountNo'],
            'bankCode' => $request->getParam('bankCode',$batchData['bankCode']),
            'bankName' => $request->getParam('bankName',$batchData['bankName']),
            'city' => $request->getParam('city',$batchData['city']),
            'province' => $request->getParam('province',$batchData['province']),
            // 'userTerminal' => $request->getParam('userTerminal'),
            'userIp' => $request->getParam('requestIp', '0.0.0.0'),
            'applyIp' => Tools::getIp(),
            'backNoticeUrl' => $request->getParam('backNoticeUrl', ''),
            'merchantParam' => $request->getParam('merchantParam', ''),
            // 'platformOrderNo' => Tools::getPlatformOrderNo('P'),
            'platformOrderNo' => $platformOrderNo,
            'orderStatus' => 'Transfered',
            // 'channel' => $channel,
            // 'channelMerchantId' => $channelMerchantId,
            'channelSetId' => 0,
            // 'channelMerchantNo' => $channelMerchantNo,
            // 'channelNoticeTime' => $request->getParam(''),
            // 'channelOrderNo' => $channelOrderNo,
            // 'pushChannelTime' => date('Y-m-d H:i:s'),
            'processType' => 'WaitPayment',
            'serviceCharge' => $serviceCharge,
            // 'channelServiceCharge' => $channelServiceCharge,
            // 'settlementAccountType' => $settlementAccountType,
            // 'transactionNo' => $request->getParam(''),
            // 'serviceCharge' => $request->getParam(''),
            'applyPerson' => $applyPerson,
            'realOrderAmount' => $orderAmount,
            'agentFee' => $agentFee,
            'agentName'=>$agentName
        ];

        if ($settlementType == "aliSettlement") {
            $data['bankCode'] = 'ALIPAY';
            $data['bankName'] = '';
            $data['city'] = '';
            $data['province'] = '';
            $data['bankAccountName'] = $request->getParam('aliAccountName',$batchData['aliAccountName']);
            $data['bankAccountNo'] = Tools::encrypt($request->getParam('aliAccountNo',$batchData['aliAccountNo']));
        }elseif ($settlementType == "manualSettlement"){
            $data['orderType'] = 'manualSettlement';
        }

        // if (!$isWorkerday) {
        //     $data['holidayServiceCharge'] = $serviceCharge;
        //     $data['holidaySettlementAmount'] = $orderAmount;
        // }

        // if ($settlementType == 'OverplusT1Settlement') {
        //     $data['t1ServiceCharge'] = $serviceCharge;
        //     $data['t1SettlementAmount'] = $orderAmount;
        // }

        $createdOrder = self::firstOrCreate($data);

        $this->setCacheByPlatformOrderNo($platformOrderNo, $createdOrder);
        return $createdOrder;
    }

    public function getErrorMessage()
    {
        return $this->errors;
    }

    public function success($orderData, $orderAmount = 0, $processType = 'Success', $channelOrderNo = '', $failReason = '自动处理', $channelNoticeTime = '', $auditPerson = '', $channelServiceCharge = 0)
    {
        global $app;

        $db = $app->getContainer()->database;
        $logger = $app->getContainer()->logger;
        $redis = $app->getContainer()->redis;

        try {
            $db->getConnection()->beginTransaction();
//            $merchantRate = new MerchantRate();
            $ap = new AmountSettlement;
            $ppo = new PlatformSettlementOrder;
            // $merchantAmount = new MerchantAmount;
            $merchantData = (new Merchant)->getCacheByMerchantId($orderData['merchantId']);
            $channelOrderNo = empty($channelOrderNo) ? $orderData['channelOrderNo'] : $channelOrderNo;
            $channelNoticeTime = empty($channelNoticeTime) ? date('YmdHis') : $channelNoticeTime;
            $accountDate = Tools::getAccountDate($merchantData['settlementTime'], $channelNoticeTime);

            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();

            AmountSettlement::updateOrCreate([
                'merchantId' => $orderData['merchantId'],
                'merchantNo' => $orderData['merchantNo'],
                'channelMerchantId' => $orderData['channelMerchantId'],
                'channelMerchantNo' => $orderData['channelMerchantNo'],
                'accountDate' => $accountDate,
            ]);
            $apLockData = $ap->where('merchantId', $orderData['merchantId'])
                ->where('channelMerchantId', $orderData['channelMerchantId'])
                ->where('accountDate', $accountDate)
                ->lockForUpdate()
                ->first();

            if (empty($orderDataLock) || in_array($orderDataLock->orderStatus, ['Success', 'Fail'])) {
                throw new \Exception("数据已处理，或不存在:" . $orderData['platformOrderNo']);
            }
            $orderDataLock->channelNoticeTime = $channelNoticeTime;
            !empty($channelOrderNo) && $orderDataLock->channelOrderNo = $channelOrderNo;
            $orderDataLock->processType = $processType;
            $orderDataLock->failReason = $failReason;
            $orderDataLock->orderStatus = 'Success';
            $orderDataLock->accountDate = $accountDate;
            if ($orderAmount > 0 && $orderAmount != $orderDataLock->orderAmount) {
                $orderDataLock->realOrderAmount = $orderAmount;
            }else{
                $orderDataLock->realOrderAmount = $orderDataLock->orderAmount;
            }

            if (!empty($auditPerson)) {
                $orderDataLock->auditPerson = $auditPerson;
                $orderDataLock->auditIp = Tools::getIp();
                $orderDataLock->auditTime = date('YmdHis');
            }
            $orderDataLock->isLock = 0;
            $orderDataLock->lockUser = null;
            $channelServiceCharge && $orderDataLock->channelServiceCharge = $channelServiceCharge;

            $orderDataLock->save();
            $apLockData->transferTimes = $apLockData->transferTimes + 1;
            $apLockData->amount = $apLockData->amount + $orderDataLock->orderAmount;
            $apLockData->serviceCharge = $apLockData->serviceCharge + $orderDataLock->serviceCharge;
            $apLockData->channelServiceCharge = $apLockData->channelServiceCharge + $orderDataLock->channelServiceCharge;
            $apLockData->save();

            // $merchantAmountData = $merchantAmount->where('merchantId', $orderData['merchantId'])->lockForUpdate()->first();
            // $merchantAmountData->settledAmount = $merchantAmountData->settledAmount + $orderDataLock->orderAmount + $orderDataLock->serviceCharge;
            // $merchantAmountData->save();
            // $merchantAmountData->refreshCache(['merchantId' => $merchantAmountData->merchantId]);

            $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
            // (new MerchantChannelSettlement)->incrCacheByDayAmountLimit($orderData['merchantNo'], $orderData['channelMerchantNo'], intval($orderData['orderAmount'] * 100));
            // (new MerchantChannelSettlement)->incrCacheByDayNumLimit($orderData['merchantNo'], $orderData['channelMerchantNo']);
            //代付成功减存储的余额
            $accountBalance = MerchantChannelSettlement::where('channelMerchantId', $orderData['channelMerchantId'])->value('accountBalance');
            if($accountBalance != 0 ) {
                $b_temp = intval($accountBalance - $orderDataLock->realOrderAmount);
                if($b_temp > 0) {
                    MerchantChannelSettlement::where('channelMerchantId', $orderData['channelMerchantId'])->update([
                        'accountBalance' => $b_temp,
                    ]);
                }
                if($b_temp < self::$voiceBalance) {
                    $redis->setex('cacheAlipayBalance', 7*60*60*24, $orderData['channelMerchantId']);
                }
            }

            //代理手续费
            $agentId = AgentMerchantRelation::where('merchantId',$orderData['merchantId'])->value('agentId');
            if($agentId ||isset($orderData['agentFee']) && $orderData['agentFee'] > 0) {
                $agentLog = new AgentIncomeLog();
                $agentLog->updateIncomeLog($orderData['merchantId'],$orderData['platformOrderNo'],$orderAmount,'settlement');
            }

            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $db->getConnection()->rollback();
            return false;
        }
        return true;
    }

    public function fail($orderData, $processType = 'Success', $channelOrderNo = '', $failReason = '自动处理', $channelNoticeTime = '', $auditPerson = '',
        $channel = '',
        $channelMerchantId = '',
        $channelMerchantNo = '',
        $channelServiceCharge = 0
    ) {
        global $app;
        $db = $app->getContainer()->database;
        $logger = $app->getContainer()->logger;
        try {
            $db->getConnection()->beginTransaction();
            $merchantRate = new MerchantRate();
            // $ap = new AmountSettlement;
            $ppo = new PlatformSettlementOrder;
            $finance = new Finance;
            $merchantAmount = new MerchantAmount;
            $merchantData = (new Merchant)->getCacheByMerchantId($orderData['merchantId']);
            $channelNoticeTime = empty($channelNoticeTime) ? date('YmdHis') : $channelNoticeTime;
            $channelOrderNo = empty($channelOrderNo) ? $orderData['channelOrderNo'] : $channelOrderNo;
            $accountDate = Tools::getAccountDate($merchantData['settlementTime'], $channelNoticeTime);
            $failReason = \mb_substr($failReason, 0, 255);
            // $apData = AmountSettlement::updateOrCreate(['merchantId' => $orderData['merchantId'],
            //     'merchantNo' => $orderData['merchantNo'],
            //     'accountDate' => $accountDate]);
            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();
            // $apLockData = $ap->where('id', $apData->id)->lockForUpdate()->first();
            $merchantAmountData = $merchantAmount->where('merchantId', $orderData['merchantId'])->lockForUpdate()->first();
            if (empty($orderDataLock) || in_array($orderDataLock->orderStatus, ['Success', 'Fail'])) {
                throw new \Exception("数据已处理，或不存在:" . $orderData['platformOrderNo']);
            }
            $orderDataLock->channelNoticeTime = $channelNoticeTime;
            $orderDataLock->processType = $processType;
            $orderDataLock->failReason = $failReason;
            $channel && $orderDataLock->channel = $channel;
            $channelMerchantId && $orderDataLock->channelMerchantId = $channelMerchantId;
            $channelMerchantNo && $orderDataLock->channelMerchantNo = $channelMerchantNo;
            $channelServiceCharge && $orderDataLock->channelServiceCharge = $channelServiceCharge;
            !empty($channelOrderNo) && $orderDataLock->channelOrderNo = $channelOrderNo;
            $orderDataLock->orderStatus = 'Fail';
            $orderDataLock->accountDate = $accountDate;

            if (!empty($auditPerson)) {
                $orderDataLock->auditPerson = $auditPerson;
                $orderDataLock->auditIp = Tools::getIp();
                $orderDataLock->auditTime = date('YmdHis');
            }
            $orderDataLock->isLock = 0;
            $orderDataLock->lockUser = null;
            $orderDataLock->save();
            // $apLockData->amount = $apLockData->amount - $orderDataLock->orderAmount;
            // $apLockData->serviceCharge = $apLockData->serviceCharge - $orderDataLock->serviceCharge;
            // $apLockData->save();

            $merchantAmountData->settlementAmount = $merchantAmountData->settlementAmount + $orderDataLock->orderAmount + $orderDataLock->serviceCharge;
            // $merchantAmountData->settledAmount = $merchantAmountData->settledAmount - $orderDataLock->orderAmount - $orderDataLock->serviceCharge;

            // if (Tools::isToday($orderDataLock->created_at)) {
            //     $merchantAmountData->todaySettlementAmount = $merchantAmountData->todaySettlementAmount - $orderDataLock->orderAmount - $orderDataLock->serviceCharge;
            //     $merchantAmountData->todayServiceCharge = $merchantAmountData->todayServiceCharge - $orderDataLock->serviceCharge;

            //     $merchantAmountData->todaySettlementAmount = $merchantAmountData->todaySettlementAmount < 0 ? 0 : $merchantAmountData->todaySettlementAmount;
            //     $merchantAmountData->todayServiceCharge = $merchantAmountData->todayServiceCharge < 0 ? 0 : $merchantAmountData->todayServiceCharge;
            // }

            $merchantAmountData->save();

            $fd = [
                [
                    'merchantId' => $orderDataLock->merchantId,
                    'merchantNo' => $orderDataLock->merchantNo,
                    'platformOrderNo' => $orderDataLock->platformOrderNo,
                    'amount' => $orderDataLock->orderAmount,
                    'balance' => $merchantAmountData->settlementAmount - $orderDataLock->serviceCharge,
                    'financeType' => 'PayIn',
                    'accountDate' => $accountDate,
                    'accountType' => 'SettledAccount',
                    'sourceId' => $orderDataLock->orderId,
                    'sourceDesc' => '结算返还服务',
                    'summary' => $auditPerson ? '手动处理:' . $failReason : '',
                    'merchantOrderNo' => $orderDataLock->merchantOrderNo,
                    'operateSource' => empty($orderDataLock->merchantOrderNo) ? 'merchant' : 'ports',
                ],
            ];

            if ($orderDataLock->serviceCharge > 0) {
                $fd[] = [
                    'merchantId' => $orderDataLock->merchantId,
                    'merchantNo' => $orderDataLock->merchantNo,
                    'platformOrderNo' => $orderDataLock->platformOrderNo,
                    'amount' => $orderDataLock->serviceCharge,
                    'balance' => $merchantAmountData->settlementAmount,
                    'financeType' => 'PayIn',
                    'accountDate' => $accountDate,
                    'accountType' => 'ServiceChargeAccount',
                    'sourceId' => $orderDataLock->orderId,
                    'sourceDesc' => '结算返还手续费',
                    'summary' => $auditPerson ? '手动处理:' . $failReason : '',
                    'merchantOrderNo' => $orderDataLock->merchantOrderNo,
                    'operateSource' => empty($orderDataLock->merchantOrderNo) ? 'merchant' : 'ports',
                ];
            }
            Finance::insert($fd);

            $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
            $merchantAmountData->refreshCache(['merchantId' => $merchantAmountData->merchantId]);

            if (Tools::isToday($accountDate)) {
                AmountPay::where('merchantId', $orderData['merchantId'])
                    ->where('accountDate', $accountDate)
                    ->update(['balance' => $merchantAmountData->settlementAmount]);
            }

            if ($this->isRollbackMerchantChannelSettleDayLimit($orderDataLock)) {
                (new MerchantChannelSettlement)->incrCacheByDayAmountLimit($orderData['merchantNo'], $orderData['channelMerchantNo'], -intval($orderData['orderAmount'] * 100));
                (new MerchantChannelSettlement)->incrCacheByDayNumLimit($orderData['merchantNo'], $orderData['channelMerchantNo'], -1);
                (new ChannelSettlementConfig)->incrCacheByDayAmountLimit($orderData['channelMerchantNo'], -intval($orderData['orderAmount'] * 100));
                (new ChannelSettlementConfig)->incrCacheByDayNumLimit($orderData['channelMerchantNo'], -1);
                (new ChannelSettlementConfig)->incrCacheByCardDayAmountLimit(Tools::decrypt($orderDataLock->bankAccountNo), $orderDataLock->channelMerchantNo, -intval($orderDataLock->orderAmount * 100));
                (new ChannelSettlementConfig)->incrCacheByCardDayNumLimit(Tools::decrypt($orderDataLock->bankAccountNo), $orderDataLock->channelMerchantNo, -1);
            }

            if ($this->isRollbackMerchantSettleDayLimit($orderDataLock)) {
                (new Merchant)->incrCacheByDaySettleAmountLimit($orderData['merchantNo'], -intval($orderData['orderAmount'] * 100));
            }

            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $db->getConnection()->rollback();
            return false;
        }
        return true;
    }

    public function start($orderData, $channel, $channelMerchantId, $channelMerchantNo, $channelOrderNo, $channelServiceCharge)
    {
        global $app;

        $db = $app->getContainer()->database;
        $logger = $app->getContainer()->logger;
        try {
            $db->getConnection()->beginTransaction();
            $ppo = new PlatformSettlementOrder;
            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();
            if (empty($orderDataLock) || in_array($orderDataLock->orderStatus, ['Success', 'Fail', 'Exception'])) {
                throw new \Exception("数据已处理，或不存在:" . $orderData['platformOrderNo']);
            }
            $orderDataLock->channel = $channel;
            $orderDataLock->channelOrderNo = $channelOrderNo;
            $orderDataLock->channelMerchantId = $channelMerchantId;
            $orderDataLock->channelMerchantNo = $channelMerchantNo;
            $orderDataLock->processType = 'serviceQuery';
            $orderDataLock->pushChannelTime = date('YmdHis');
            $orderDataLock->channelServiceCharge = $channelServiceCharge;
            $orderDataLock->save();

            (new MerchantChannelSettlement)->incrCacheByDayAmountLimit($orderDataLock->merchantNo, $orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new MerchantChannelSettlement)->incrCacheByDayNumLimit($orderDataLock->merchantNo, $orderDataLock->channelMerchantNo);
            (new ChannelSettlementConfig)->incrCacheByDayAmountLimit($orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new ChannelSettlementConfig)->incrCacheByDayNumLimit($orderDataLock->channelMerchantNo);
            (new ChannelSettlementConfig)->incrCacheByCardDayAmountLimit(Tools::decrypt($orderDataLock->bankAccountNo), $orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new ChannelSettlementConfig)->incrCacheByCardDayNumLimit(Tools::decrypt($orderDataLock->bankAccountNo), $orderDataLock->channelMerchantNo);

            $ppo->setCacheByPlatformOrderNo($orderDataLock->platformOrderNo, $orderDataLock->toArray());
            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $db->getConnection()->rollback();
            return false;
        }
        return true;
    }

    public function directSuccess($orderData, $channel, $channelMerchantId, $channelMerchantNo, $channelOrderNo, $channelServiceCharge, $orderAmount = 0, $processType = 'Success', $failReason = '自动处理', $channelNoticeTime = '', $auditPerson = '')
    {
        global $app;

        $db = $app->getContainer()->database;
        $logger = $app->getContainer()->logger;
        try {
            $db->getConnection()->beginTransaction();
            $ap = new AmountSettlement;
            $merchantData = (new Merchant)->getCacheByMerchantId($orderData['merchantId']);
            $channelNoticeTime = empty($channelNoticeTime) ? date('YmdHis') : $channelNoticeTime;
            $accountDate = Tools::getAccountDate($merchantData['settlementTime'], $channelNoticeTime);
            $ppo = new PlatformSettlementOrder;
            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();

            AmountSettlement::updateOrCreate([
                'merchantId' => $orderData['merchantId'],
                'merchantNo' => $orderData['merchantNo'],
                'channelMerchantId' => $orderData['channelMerchantId'],
                'channelMerchantNo' => $orderData['channelMerchantNo'],
                'accountDate' => $accountDate,
            ]);

            $apLockData = $ap->where('merchantId', $orderData['merchantId'])
                ->where('channelMerchantId', $orderData['channelMerchantId'])
                ->where('accountDate', $accountDate)
                ->lockForUpdate()
                ->first();

            if (empty($orderDataLock) || in_array($orderDataLock->orderStatus, ['Success', 'Fail', 'Exception'])) {
                throw new \Exception("数据已处理，或不存在:" . $orderData['platformOrderNo']);
            }
            $orderDataLock->channel = $channel;
            $orderDataLock->channelOrderNo = $channelOrderNo;
            $orderDataLock->channelMerchantId = $channelMerchantId;
            $orderDataLock->channelMerchantNo = $channelMerchantNo;
            $orderDataLock->processType = $processType;
            $orderDataLock->pushChannelTime = date('YmdHis');
            $orderDataLock->channelServiceCharge = $channelServiceCharge;
            $orderDataLock->channelNoticeTime = $channelNoticeTime;
            $orderDataLock->failReason = $failReason;
            $orderDataLock->orderStatus = 'Success';
            $orderDataLock->accountDate = $accountDate;

            if ($orderAmount > 0 && $orderAmount != $orderDataLock->orderAmount) {
                $orderDataLock->realOrderAmount = $orderAmount;
            }

            if (!empty($auditPerson)) {
                $orderDataLock->auditPerson = $auditPerson;
                $orderDataLock->auditIp = Tools::getIp();
                $orderDataLock->auditTime = date('YmdHis');
            }

            $orderDataLock->save();

            $ppo->setCacheByPlatformOrderNo($orderDataLock->platformOrderNo, $orderDataLock->toArray());

            $apLockData->transferTimes = $apLockData->transferTimes + 1;
            $apLockData->amount = $apLockData->amount + $orderDataLock->orderAmount;
            $apLockData->serviceCharge = $apLockData->serviceCharge + $orderDataLock->serviceCharge;
            $apLockData->channelServiceCharge = $apLockData->channelServiceCharge + $orderDataLock->channelServiceCharge;
            $apLockData->save();

            (new MerchantChannelSettlement)->incrCacheByDayAmountLimit($orderDataLock->merchantNo, $orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new MerchantChannelSettlement)->incrCacheByDayNumLimit($orderDataLock->merchantNo, $orderDataLock->channelMerchantNo);

            //代理手续费
            $agentId = AgentMerchantRelation::where('merchantId',$orderData['merchantId'])->value('agentId');
            if($agentId ||isset($orderData['agentFee']) && $orderData['agentFee'] > 0) {
                $agentLog = new AgentIncomeLog();
                $agentLog->updateIncomeLog($orderData['merchantId'],$orderData['platformOrderNo'],$orderAmount,'settlement');
            }

            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $db->getConnection()->rollback();
            return false;
        }
        return true;
    }

    public function exception($orderData,
        $channel = '',
        $channelMerchantId = '',
        $channelMerchantNo = '',
        $channelServiceCharge = 0,
        $failReason = '') {
        global $app;

        $db = $app->getContainer()->database;
        $logger = $app->getContainer()->logger;
        try {
            $db->getConnection()->beginTransaction();
            $ppo = new PlatformSettlementOrder;
            $orderDataLock = $ppo->where('orderId', $orderData['orderId'])->lockForUpdate()->first();
            if (empty($orderDataLock) || in_array($orderDataLock->orderStatus, ['Success', 'Fail'])) {
                throw new \Exception("数据已处理，或不存在:" . $orderData['platformOrderNo']);
            }
            $orderDataLock->processType = 'serviceQuery';
            $orderDataLock->orderStatus = 'Exception';
            $channel && $orderDataLock->channel = $channel;
            $channelMerchantId && $orderDataLock->channelMerchantId = $channelMerchantId;
            $channelMerchantNo && $orderDataLock->channelMerchantNo = $channelMerchantNo;
            $channelServiceCharge && $orderDataLock->channelServiceCharge = $channelServiceCharge;
            $orderDataLock->pushChannelTime = date('YmdHis');
            $orderDataLock->failReason = \mb_substr($failReason, 0, 255);
            $orderDataLock->save();

            (new MerchantChannelSettlement)->incrCacheByDayAmountLimit($orderDataLock->merchantNo, $orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new MerchantChannelSettlement)->incrCacheByDayNumLimit($orderDataLock->merchantNo, $orderDataLock->channelMerchantNo);
            (new ChannelSettlementConfig)->incrCacheByDayAmountLimit($orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new ChannelSettlementConfig)->incrCacheByDayNumLimit($orderDataLock->channelMerchantNo);
            (new ChannelSettlementConfig)->incrCacheByCardDayAmountLimit(Tools::decrypt($orderDataLock->bankAccountNo), $orderDataLock->channelMerchantNo, intval($orderDataLock->orderAmount * 100));
            (new ChannelSettlementConfig)->incrCacheByCardDayNumLimit(Tools::decrypt($orderDataLock->bankAccountNo), $orderDataLock->channelMerchantNo);

            $ppo->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderDataLock->toArray());
            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $this->errors = $e->getMessage();
            $db->getConnection()->rollback();
            return false;
        }
        return true;
    }

    public function setCacheByPlatformOrderNo($platformOrderNo, $data)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex("settlementorder:" . $platformOrderNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
        if (!empty($data['merchantOrderNo'])) {
            $redis->setex("settlementorder:m:" . $data['merchantNo'] . ":" . $data['merchantOrderNo'], 7 * 86400, $platformOrderNo);
        }
    }

    public function getCacheByPlatformOrderNo($platformOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("settlementorder:" . $platformOrderNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantOrderNo($merchantNo, $merchantOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $platformOrderNo = $redis->get("settlementorder:m:" . $merchantNo . ":" . $merchantOrderNo);
        if (empty($platformOrderNo)) {
            return [];
        }
        return $this->getCacheByPlatformOrderNo($platformOrderNo);
    }
}
