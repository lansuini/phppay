<?php
namespace App\Channels;

use App\Channels\ChannelProxyException;
use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\PlatformPayOrder;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementRechargeOrder;
use App\Models\PlatformRechargeOrder;
use App\Queues\PayPushExecutor;
use App\Queues\SettlementPushExecutor;

class ChannelProxy
{
    protected $channelMerchantId;

    protected $channel;

    protected $queryPayOrderValidator;

    protected $status = ['Success', 'Execute', 'Fail'];

    protected $channelName;

    protected $ipWhite;

    protected $timeout = 15;

    protected $forceChannelMerchantId = 0;

    protected $forcePayModel = null;

    protected $forceOrderAmount = null;

    protected $openPay = false;

    protected $openSettlement = false;

    protected $openQuery = false;

    protected $apiCharset = 'utf8';

    protected $redis;

    protected $channelDefine;

    protected $logger;

    public function __construct()
    {
        global $app;
        $this->channelDefine = $app->getContainer()->code['channel'];
        $this->logger = $app->getContainer()->logger;
        $this->redis = $app->getContainer()->redis;
    }

    protected function initChannel($channelMerchantId)
    {
        $this->channelMerchantId = $this->forceChannelMerchantId > 0 ? $this->forceChannelMerchantId : $channelMerchantId;
        // echo 1;exit;
        $channelMerchantData = (new ChannelMerchant)->getCacheByChannelMerchantId($this->channelMerchantId);
        if (empty($channelMerchantData)) {
            throw new ChannelProxyException("channelMerchant不存在" . $this->channelMerchantId);
        }

        $dbParam = (array) json_decode(Tools::decrypt($channelMerchantData['param']), true);
        if (!empty($channelMerchantData['delegateDomain'])) {
            $dbParam['gateway'] = $channelMerchantData['delegateDomain'];
        }

        // $this->logger->debug('channelDefine:', $this->channelDefine);
        if (!isset($this->channelDefine[$channelMerchantData['channel']])) {
            throw new ChannelProxyException("channel没有定义" . $channelMerchantData['channel']);
        }

        $channelConfig = $this->channelDefine[$channelMerchantData['channel']];

        if (isset($channelConfig['open']) && $channelConfig['open'] == false) {
            throw new ChannelProxyException("channel已关闭:" . $channelMerchantData['channel']);
        }

        $param = array_merge($channelConfig['param'], $dbParam);

        if (!isset($param['gateway'])) {
            throw new ChannelProxyException("gateway没有定义:" . $channelMerchantData['channel']);
        }
        $param['name'] = $channelConfig['name'];
        $param['timeout'] = !isset($param['timeout']) ? $this->timeout : $param['timeout'];
        $this->channel = new $channelConfig['path']($param);
        $this->ipWhite = isset($param['ipWhite']) ? $param['ipWhite'] : '';
        $this->channelName = $channelConfig['name'];
        $this->openPay = $channelConfig['openPay'];
        $this->openSettlement = $channelConfig['openSettlement'];
        $this->openQuery = $channelConfig['openQuery'];
        $this->apiCharset = isset($channelConfig['apiCharset']) ? $channelConfig['apiCharset'] : $this->apiCharset;
    }

    protected function init($platformOrderNoOrOrderData)
    {
        $platformOrderNo = is_array($platformOrderNoOrOrderData) ? $platformOrderNoOrOrderData['platformOrderNo'] : $platformOrderNoOrOrderData;
        $orderData = is_array($platformOrderNoOrOrderData) ? $platformOrderNoOrOrderData : [];

        if (empty($orderData) && $platformOrderNo[0] == 'P') {
            $orderData = (new PlatformPayOrder)->getCacheByPlatformOrderNo($platformOrderNo);
        }

        if (empty($orderData) && $platformOrderNo[0] == 'S') {
            $orderData = (new PlatformSettlementOrder)->getCacheByPlatformOrderNo($platformOrderNo);
        }

        if (empty($orderData) && $platformOrderNo[0] == 'R') {
            $orderData = (new PlatformRechargeOrder)->getCacheByPlatformOrderNo($platformOrderNo);
        }

        if (empty($orderData)) {
            throw new ChannelProxyException("订单不存在:" . $platformOrderNo);
        }
        $orderData['channelMerchantId'] = $this->forceChannelMerchantId > 0 ?  $this->forceChannelMerchantId : $orderData['channelMerchantId'] ;
        if (empty($orderData['channelMerchantId']) && !$this->forceChannelMerchantId ) {
            throw new ChannelProxyException("订单channelMerchantId不存在" . $platformOrderNo);
        }

        $this->initChannel($orderData['channelMerchantId']);
        return [$platformOrderNo, $orderData];
    }

    protected function checkQueryPayOrder($res)
    {
        $status = ['Expired', 'WaitPayment', 'Success', 'Fail'];
        $check = [
            'status',
            'orderNo',
            'orderAmount',
            'failReason',
        ];

        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }
        if (!in_array($res['status'], $status)) {
            return false;
        }
        return true;
    }

    protected function checkQuerySettlementOrder($res)
    {
        $status = ['Success', 'Fail', 'Exception', 'Execute'];
        $check = [
            'status',
            'orderNo',
            'orderAmount',
            'failReason',
        ];

        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }
        if (!in_array($res['status'], $status)) {
            return false;
        }
        return true;
    }

    protected function checkQueryBalance($res)
    {
        $status = ['Success', 'Fail'];
        $check = [
            'status',
            'balance',
            'failReason',
        ];

        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }
        if (!in_array($res['status'], $status)) {
            return false;
        }
        return true;
    }

    protected function checkGetPayOrder($res)
    {
        $status = ['Expired', 'WaitPayment', 'Success', 'Fail'];
        $check = [
            'status',
            'orderNo',
            'payUrl',
            'failReason',
        ];
        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }
        if (!in_array($res['status'], $status)) {
            return false;
        }
        return true;
    }

    protected function checkGetRechargeOrder($res)
    {
        //增加状态DirectSuccess处理代付直接成功到账
        $status = ['Success', 'Fail', 'Exception'];
        $check = [
            'status',
            'payUrl',
            'orderNo',
            'failReason',
        ];

        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }
        if (!in_array($res['status'], $status)) {
            return false;
        }
        return true;
    }

    protected function checkGetSettlementOrder($res)
    {
        //增加状态DirectSuccess处理代付直接成功到账
        $status = ['Success', 'Fail', 'Exception', 'DirectSuccess'];
        $check = [
            'status',
            'orderAmount',
            'orderNo',
            'failReason',
        ];

        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }
        if (!in_array($res['status'], $status)) {
            return false;
        }
        return true;
    }

    protected function checkDoCallback($res)
    {
        global $app;

        $status = ['Success', 'Fail'];
        $check = [
            'status',
            'orderStatus',
            'orderNo',
            'orderAmount',
            'failReason',
        ];

        if (!is_array($res)) {
            return false;
        }

        foreach ($check as $k) {
            if (!isset($res[$k])) {
                return false;
            }
        }

        if (!in_array($res['status'], $status)) {
            return false;
        }

        if (!in_array($res['orderStatus'], $status)) {
            return false;
        }

        if (isset($res['payType']) && !in_array($res['payType'], $app->getContainer()->code['payType'])) {
            return false;
        }
        return true;
    }

    protected function isIpWhite()
    {
        if (empty($this->ipWhite)) {
            return true;
        }

        $ipWhites = $this->ipWhite;
        if(is_string($ipWhites)){
            $ipWhites = explode(',', $ipWhites);
        }
        $ipWhites = array_map('trim', $ipWhites);
        $ipWhites = array_filter($ipWhites);
        if (empty($ipWhites)) {
            return true;
        }

        return in_array(Tools::getIp(), $ipWhites);
    }

    public function queryPayOrder($platformOrderNoOrOrderData)
    {
        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);

        if (!$this->openQuery) {
            throw new ChannelProxyException("queryPayOrder查询关闭:" . $this->openQuery);
        }

        if (method_exists($this->channel, 'queryPayOrder')) {
            $res = $this->channel->queryPayOrder($platformOrderNo);
        } else {
            $res = $this->channel->queryOrder($platformOrderNo);
        }

        if (!$this->checkQueryPayOrder($res)) {
            throw new ChannelProxyException("queryPayOrder返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
        }

        return $res;
    }

    public function querySettlementOrder($platformOrderNoOrOrderData)
    {
        //余额下发状态查询
        if (is_array($platformOrderNoOrOrderData) && $platformOrderNoOrOrderData['platformOrderNo'][0] == 'I') {
            $this->initChannel($platformOrderNoOrOrderData['channelMerchantId']);
            $platformOrderNo = $platformOrderNoOrOrderData['platformOrderNo'];
        }else{
            list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);
        }

        if (!$this->openQuery) {
            throw new ChannelProxyException("querySettlementOrder查询关闭:" . $this->openQuery);
        }

        if (method_exists($this->channel, 'querySettlementOrder')) {
            $res = $this->channel->querySettlementOrder($platformOrderNo);
        } else {
            $res = $this->channel->queryOrder($platformOrderNo);
        }

        if (!$this->checkQuerySettlementOrder($res)) {
            throw new ChannelProxyException("querySettlementOrder返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
        }

        return $res;
    }

    public function queryBalance($channelMerchantId = 0)
    {
        if (empty($this->channel) && $channelMerchantId == 0) {
            throw new ChannelProxyException("queryBalance需要定义channelMerchantId");
        }
        $this->initChannel($channelMerchantId);

        if (!$this->openQuery) {
            throw new ChannelProxyException("queryBalance查询关闭:" . $this->openQuery);
        }

        $res = $this->channel->queryBalance();
        if (!$this->checkQueryBalance($res)) {
            throw new ChannelProxyException("queryBalance返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
        }

        return $res;
    }

    public function uploadCheckFile($channelMerchantId = 0, $date)
    {
        if (empty($this->channel) && $channelMerchantId == 0) {
            throw new ChannelProxyException("uploadCheckFile需要定义channelMerchantId");
        }
        $this->initChannel($channelMerchantId);
        if(!method_exists($this->channel,'uploadCheckFile')){
            return ['success'=>0,'msg'=>"上游不支持对接单下载",'url'=> ''];
        }
        $res = $this->channel->uploadCheckFile($date);  //对账单下载地址url
        return ['success'=>1,'msg'=>"SUCCESS",'url'=> $res];
    }

    public function getRechargeOrder($platformOrderNoOrOrderData){

        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);
        $orderData['orderAmount'] = $this->forceOrderAmount ? $this->forceOrderAmount : $orderData['orderAmount'];

        if(empty($orderData)){
            throw new ChannelProxyException("getRechargeOrder订单数据为空");
        }

        $res = $this->channel->getRechargeOrder($orderData);


    if (!$this->checkGetRechargeOrder($res)) {
        throw new ChannelProxyException("getRechargeOrder返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
    }
        return $res;
    }

    //依据订单初始化相应的数据并动态调用传过来的channel方法
    public function trendsAction($platformOrderNo, $action, $param)
    {
        try {
            list($platformOrderNo, $orderData) = $this->init($platformOrderNo);
            return $this->channel->$action($orderData, $param);
        } catch (\Exception $e) {
            $this->logger->error("channelProxy $action exception:" . $e->getMessage());
        }

        return $this->apiCharset;
    }

    public function getPayOrder($platformOrderNoOrOrderData)
    {
        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);
        $orderData['payModel'] = $this->forcePayModel ? $this->forcePayModel : $orderData['payModel'];
        $orderData['orderAmount'] = $this->forceOrderAmount ? $this->forceOrderAmount : $orderData['orderAmount'];

        if (!$this->openPay) {
            throw new ChannelProxyException("getPayOrder关闭:" . $this->openPay);
        }

        if (method_exists($this->channel, 'getNotDirectPayOrder')) {
            $res = $this->channel->getNotDirectPayOrder($orderData);
        } else {
            $res = $this->channel->getPayOrder($orderData);
        }

        if (!$this->checkGetPayOrder($res)) {
            throw new ChannelProxyException("getPayOrder返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
        }

        return $res;
    }

    public function getSettlementOrder($platformOrderNoOrOrderData)
    {

        try {
            //余额下发
            if (is_array($platformOrderNoOrOrderData) && strpos($platformOrderNoOrOrderData['platformOrderNo'],'I') != false) {
                $orderData = $platformOrderNoOrOrderData;
                $this->initChannel($orderData['channelMerchantId']);
            }else{
                list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);
                $orderData['orderAmount'] = $this->forceOrderAmount ? $this->forceOrderAmount : $orderData['orderAmount'];
            }


            if (!$this->openSettlement) {
                throw new ChannelProxyException("getSettlementOrder关闭:" . $this->openSettlement);
            }
        } catch (\Exception $e) {
            $res = [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => $e->getMessage(),
            ];

            return $res;
        }

        try {
            $res = $this->channel->getSettlementOrder($orderData);
        } catch (\Exception $e) {
            $this->logger->debug('请求超时'.$orderData['platformOrderNo'].':'.$e->getMessage(), $orderData);
//            $res = [
//                'status' => 'Exception',
//                'orderAmount' => 0,
//                'orderNo' => '',
//                'failReason' => $e->getMessage(),
//            ];

            // 超时直接返回成功，通过查询接口获取最终状态
            $res = [
                'status' => 'Success',
                'orderAmount' => 0,
                'orderNo' => '',//第三方系统订单号
                'failReason' => '请求超时，需要查询确认是否成功',
                'pushChannelTime' => date('YmdHis'),
            ];
        }

        if (!$this->checkGetSettlementOrder($res)) {
            $res = [
                'status' => 'Exception',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => 'getSettlementOrder返回参数格式不正确！',
            ];
        }

        return $res;
    }

    public function doPayCallback($platformOrderNoOrOrderData, $request, $response)
    {
        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);
        if (!$this->isIpWhite()) {
            $this->logger->error($orderData['channel'] . ' doPayCallback ipwhite:' . $orderData['platformOrderNo']);
            return $this->channel->errorResponse($response);
        }

        if ($orderData['orderStatus'] == 'Success') {
            //$this->logger->error($orderData['channel'] . ' doPayCallback error:' . $orderData['platformOrderNo']);
            return $this->channel->successResponse($response);
        }

        if (method_exists($this->channel, 'doPayCallback')) {
            $res = $this->channel->doPayCallback($orderData, $request);
        } else {
            $res = $this->channel->doCallback($orderData, $request);
        }

        if (!$this->checkDoCallback($res)) {
            throw new ChannelProxyException("doPayCallback返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
        }

        if ($res['orderAmount'] > 0 && !$this->channel->isAllowPayOrderOrderAmountNotEqualRealOrderAmount() && $res['orderAmount'] != $orderData['orderAmount']) {
            $this->logger->error($orderData['channel'] . ' 订单金额不一致:' . $orderData['platformOrderNo'], $res);
            return $this->channel->errorResponse($response);
        }

        if ($res['status'] == 'Success' && $this->isAllowPushTask($orderData['platformOrderNo'])) {
            (new PayPushExecutor)->push(0, $orderData['platformOrderNo'], $request->getParams(), $res);
        }

        $this->logger->debug('doPayCallback', $res);
        return $res['status'] == 'Success' ? $this->channel->successResponse($response) : $this->channel->errorResponse($response);
    }

    /**
     * 代付回调
     * @param $platformOrderNoOrOrderData
     * @param $request
     * @param $response
     * @return mixed
     * @throws \App\Channels\ChannelProxyException
     */
    public function doSettlementCallback($platformOrderNoOrOrderData, $request, $response)
    {
        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);
        if (!$this->isIpWhite()) {
            $this->logger->error($orderData['channel'] . ' doSettlementCallback ipwhite:' . $orderData['platformOrderNo']);
            return $this->channel->errorResponse($response);
        }

        if ($orderData['orderStatus'] == 'Success' || $orderData['orderStatus'] == 'Fail') {
            $this->logger->debug($orderData['channel'] . ' doSettlementCallback order error:' . $orderData['platformOrderNo']);
            return $this->channel->successResponse($response);
        }

        if (method_exists($this->channel, 'doSettlementCallback')) {
            $res = $this->channel->doSettlementCallback($orderData, $request);
        } else {
            $res = $this->channel->doCallback($orderData, $request);
        }

        if (!$this->checkDoCallback($res)) {
            throw new ChannelProxyException("doSettlementCallback返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
        }

        if ($res['status'] == 'Success' && $this->isAllowPushTask($orderData['platformOrderNo'])) {
            if ($this->apiCharset == 'gbk') {
                $reqParams = Tools::gbkToUtf8($request->getParams());
            } else {
                $reqParams = $request->getParams();
            }

            (new SettlementPushExecutor)->push(0, $orderData['platformOrderNo'], $reqParams, $res);
        }
        $this->logger->debug($orderData['channel'] . ' doSettlementCallback', $res);
        return $res['status'] == 'Success' ? $this->channel->successResponse($response) : $this->channel->errorResponse($response);

    }

    public function doRechargeCallback($platformOrderNoOrOrderData, $request, $response){

        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);

//        if (!$this->isIpWhite()) {
//            $this->logger->error($orderData['channel'] . ' doRechargeCallback ipwhite:' . $orderData['platformOrderNo']);
//            return $this->channel->errorResponse($response);
//        }

        if (method_exists($this->channel, 'doRechargeCallback')) {
            $res = $this->channel->doRechargeCallback($orderData, $request);
        } else {
            throw new ChannelProxyException("doRechargeCallback 未定义:" . $orderData['channel']);
        }

        $this->logger->debug($orderData['channel'] . ' doRechargeCallback', $res);

        if (isset($res['orderStatus']) && !empty($res['orderStatus'])) {

            (new PlatformRechargeOrder)->updateOrderStatusByOrderNo($res['orderNo'],$res['orderStatus'],$res['orderAmount']);
        }

        return $res;
    }

    public function doSettlementRechargeCallback($platformOrderNoOrOrderData, $request, $response){

        list($platformOrderNo, $orderData) = $this->init($platformOrderNoOrOrderData);

//        if (!$this->isIpWhite()) {
//            $this->logger->error($orderData['channel'] . ' doSettlementRechargeCallback ipwhite:' . $orderData['platformOrderNo']);
//            return $this->channel->errorResponse($response);
//        }

        if (method_exists($this->channel, 'doSettlementRechargeCallback')) {
            $res = $this->channel->doSettlementRechargeCallback($orderData, $request);
        } else {
            throw new ChannelProxyException("doSettlementRechargeCallback 未定义:" . $orderData['channel']);
        }

//        if (!$this->checkDoCallback($res)) {
//            throw new ChannelProxyException("doSettlementCallback返回格式异常:" . json_encode($res, JSON_UNESCAPED_UNICODE));
//        }
//        print_r($res);exit;
        $this->logger->debug($orderData['channel'] . ' doSettlementCallback', $res);

        if (isset($res['orderStatus']) && !empty($res['orderStatus'])) {

            (new SettlementRechargeOrder)->updateOrderStatusByOrderNo($res['orderNo'],$res['orderStatus'],$res['orderAmount'],$this->channelConfig);
        }

        return $res;
    }

    protected function isAllowPushTask($platformOrderNo)
    {
        $r = $this->redis->incr('cincr:' . $platformOrderNo);
        $this->redis->expire('cincr:' . $platformOrderNo, 30);
        if ($r == 1) {
            return true;
        }
        return false;
    }

    public function getChannel()
    {
        return $this->channel;
    }

    public function setForceChannelMerchantId($channelMerchantId)
    {
        $this->forceChannelMerchantId = $channelMerchantId;
        return $this;
    }

    public function setForcePayModel($payModel)
    {
        $this->forcePayModel = $payModel;
        return $this;
    }

    public function setForceOrderAmount($orderAmount)
    {
        $this->forceOrderAmount = $orderAmount;
        return $this;
    }

    public function getApiCharset($platformOrderNoOrOrderData)
    {
        try {
            $this->init($platformOrderNoOrOrderData);
        } catch (\Exception $e) {
            $this->logger->error('channelProxy getApiCharset exception:' . $e->getMessage());
        }

        return $this->apiCharset;
    }

    protected function returnQueryBalance($status, $balance, $failReason = '')
    {
        $output = compact(
            'status',
            'balance',
            'failReason'
        );
        $output['failReason'] = mb_substr($output['failReason'], 0, 255);
        return $output;
    }

    protected function returnPayOrder($status, $payUrl, $orderNo, $failReason = '')
    {
        $output = compact(
            'status',
            'payUrl',
            'orderNo',
            'failReason'
        );
        $output['failReason'] = mb_substr($output['failReason'], 0, 255);
        return $output;
    }

    protected function returnSettlementOrder($status, $orderNo, $orderAmount = 0, $failReason = '')
    {
        $output = compact(
            'status',
            'orderAmount',
            'orderNo',
            'failReason'
        );
        $output['failReason'] = mb_substr($output['failReason'], 0, 255);
        return $output;
    }

    protected function returnCallback($status, $orderStatus, $orderNo, $orderAmount = 0, $failReason = '', $payType = '')
    {
        $output = compact(
            'status',
            'orderStatus',
            'orderNo',
            'orderAmount',
            'failReason'
        );
        $payType && $output['payType'] = $payType;
        $output['failReason'] = mb_substr($output['failReason'], 0, 255);
        return $output;
    }
}
