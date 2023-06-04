<?php

namespace App\Controllers\Gate;

use App\Channels\ChannelProxy;
use App\Controllers\Controller;
use App\Helpers\Tools;
use App\Models\AmountPay;
use App\Models\ChannelMerchantRate;
use App\Models\Finance;
use App\Models\Merchant;
use App\Models\MerchantAmount;
use App\Models\MerchantChannel;
use App\Models\MerchantChannelSettlement;
use App\Models\MerchantRate;
use App\Models\Banks;
use App\Models\PlatformPayOrder;
use App\Models\PlatformSettlementOrder;
use App\Models\BlackUserSettlement;
use App\Queues\SettlementFetchExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class PayGatewayController extends Controller
{
    public function order(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $msg = '';
        $data = [];
//        echo json_encode($this->c->code['payType']);exit;
        $payTypeCode = array_keys($this->c->code['payType']);
        $cardTypeCode = array_keys($this->c->code['cardType']);
        $userTerminalCode = array_keys($this->c->code['userTerminal']);
        $payModelCode = array_keys($this->c->code['payModel']);
        $channels = $this->c->code['channel'];
        $logger = $this->c->logger;
        $db = $this->c->database;

        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'pay';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->info("支付请求");
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'merchantOrderNo' => Validator::noWhitespace()->notBlank(),
            'merchantReqTime' => Validator::date()->noWhitespace()->notBlank(),
            'orderAmount' => Validator::floatVal()->noWhitespace()->notBlank(),
            'tradeSummary' => Validator::noWhitespace()->notBlank(),
            'payModel' => Validator::in($payModelCode)->noWhitespace()->notBlank(),
            'payType' => Validator::in($payTypeCode)->noWhitespace()->notBlank(),
            'cardType' => Validator::in($cardTypeCode)->noWhitespace()->notBlank(),
            'userTerminal' => Validator::in($userTerminalCode)->noWhitespace()->notBlank(),
            'userIp' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
            'backNoticeUrl' => Validator::url()->noWhitespace()->notBlank(),
            'sign' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
            $msg = json_encode($validator->getErrors());
            $logger->info($validator->getErrors());
            // print_r($validator->getErrors());
        }

//        if ($code == 'SUCCESS') {
//            $st = strtotime($request->getParam('merchantReqTime'));
//            if ($st > time() + 3 * 60 || $st < time() - 3 * 60) {
//                $code = 'E1001';
//                $msg = 'merchantReqTime不正确-'.$st;
//            }
//        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openPay']) {
                $code = 'E2002';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::isAllowIPAccess($request->getParam('userIp'))) {
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantRate = new MerchantRate();
            $merchantRateData = $merchantRate->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantRateData)) {
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannel();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantChannelData)) {
                $code = 'E2003';
                $logger->debug("merchantChannel不存在");
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $request->getParam('payType'), $request->getParam('orderAmount'), $request->getParam('bankCode'), $request->getParam('cardType'), $channels);
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannel fetchConfig失败", $merchantChannelData);
            }
        }

        if ($code == 'SUCCESS') {
            $channelConfig = $merchantChannel->getRandConfig($merchantChannelConfig);
            if (!isset($channels[$channelConfig['channel']])) {
                $code = 'E2003';
                $logger->debug("merchantChannel channel查找失败");
            } else {
                $channel = $channels[$channelConfig['channel']];
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('backNoticeUrl'))) {
            $backNoticeUrl = parse_url($request->getParam('backNoticeUrl'));
            $host = isset($backNoticeUrl['host']) ? $backNoticeUrl['host'] : '';
            $domain = trim(str_replace(['http://', 'https://'], '', $merchantData['domain']));
            if ($merchantData['openCheckDomain'] && !empty($merchantData['domain']) && $host != $domain) {
                $code = 'E2103';
                $logger->debug("回调域名校验失败", ['domain' => $domain, 'backNoticeUrl' => $request->getParam('backNoticeUrl')]);
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('merchantOrderNo'))) {
            $d = (new PlatformPayOrder)->getCacheByMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if (!empty($d)) {
                $code = 'E2100';
            }
        }

        if ($code == 'SUCCESS') {
            try {
                $params = $request->getParams();
                $params['platformOrderNo'] = Tools::getPlatformOrderNo('P');
                $params['channelMerchantId'] = $channelConfig['channelMerchantId'];
                $channelOrder = (new ChannelProxy)->getPayOrder($params);
                if ($channelOrder['status'] != 'Success') {
                    throw new \Exception($channelOrder['failReason']);
                }
            } catch (\Exception $e) {
                $logger->debug("getPayOrder失败:" . $e->getMessage());
                $code = 'E1004';
            }
        }

        if ($code == 'SUCCESS') {
            $channelMerchantRate = new ChannelMerchantRate;
            $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

            // if ($request->getParam('merchantNo') == '88888888') {
            //     $logger->debug("merchantRateData test", $merchantRateData);
            // }

            try {
                $channelSetId = $channelConfig['setId'];
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Pay');
                if ($serviceCharge === null) {
                    throw new \Exception("支付getServiceCharge异常");
                }

                $channelServiceCharge = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $params, 'Pay');
                if ($channelServiceCharge === null) {
                    throw new \Exception("getChannelServiceCharge异常");
                }
                $db->getConnection()->beginTransaction();
                $request->channel = $channelConfig['channel'];
                $channelOrderNo = $channelOrder['orderNo'];
                $data = (new PlatformPayOrder())->create($request,
                    $params['platformOrderNo'],
                    bcmul($params['orderAmount'] , 100 ,0)/100,
                    $channelConfig['channel'],
                    $channelConfig['channelMerchantId'],
                    $channelConfig['channelMerchantNo'],
                    $channelSetId,
                    $channelOrderNo,
                    $serviceCharge,
                    $channelServiceCharge
                );
                $db->getConnection()->commit();
            } catch (\Exception $e) {
                $logger->debug("create失败" . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }

//        if ($code == 'SUCCESS' && $request->getMethod() == 'GET') {
//            Tools::JsRedirect($channelOrder['payUrl']);
//        }

        if ($code == 'SUCCESS') {
            $biz = [
                'platformOrderNo' => $data->platformOrderNo,
                'payUrl' => $channelOrder['payUrl'],
            ];
            $sign = Tools::getSign($biz, Tools::decrypt($merchantData['signKey']));

            // (new PlatformPayOrder())->success($data->toArray(), $data->orderAmount);
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            if($code == 'E1001'){
                $return = [
                    'code' => $code,
                    'msg' => $msg,
                ];
            }else{
                $return = [
                    'code' => $code,
                    'msg' => $this->c->code['status'][$code],
                ];
            }
            return $response->withStatus(200)->withJson($return);
        }
    }

    public function pay(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $msg = '';
        $data = [];
//        echo json_encode($this->c->code['payType']);exit;
        $payTypeCode = array_keys($this->c->code['payType']);
        $cardTypeCode = array_keys($this->c->code['cardType']);
        $userTerminalCode = array_keys($this->c->code['userTerminal']);
        $payModelCode = array_keys($this->c->code['payModel']);
        $channels = $this->c->code['channel'];
        $logger = $this->c->logger;
        $db = $this->c->database;

        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'pay';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->info("支付请求");
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'merchantOrderNo' => Validator::noWhitespace()->notBlank(),
            'merchantReqTime' => Validator::date()->noWhitespace()->notBlank(),
            'orderAmount' => Validator::floatVal()->noWhitespace()->notBlank(),
            'tradeSummary' => Validator::noWhitespace()->notBlank(),
            'payModel' => Validator::in($payModelCode)->noWhitespace()->notBlank(),
            'payType' => Validator::in($payTypeCode)->noWhitespace()->notBlank(),
            'cardType' => Validator::in($cardTypeCode)->noWhitespace()->notBlank(),
            'userTerminal' => Validator::in($userTerminalCode)->noWhitespace()->notBlank(),
            'userIp' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
            'backNoticeUrl' => Validator::url()->noWhitespace()->notBlank(),
            'sign' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
            $msg = json_encode($validator->getErrors());
            $logger->info($validator->getErrors());
            // print_r($validator->getErrors());
        }

//        if ($code == 'SUCCESS') {
//            $st = strtotime($request->getParam('merchantReqTime'));
//            if ($st > time() + 3 * 60 || $st < time() - 3 * 60) {
//                $code = 'E1001';
//                $msg = 'merchantReqTime不正确-'.$st;
//            }
//        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openPay']) {
                $code = 'E2002';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::isAllowIPAccess($request->getParam('userIp'))) {
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantRate = new MerchantRate();
            $merchantRateData = $merchantRate->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantRateData)) {
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannel();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantChannelData)) {
                $code = 'E2003';
                $logger->debug("merchantChannel不存在");
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $request->getParam('payType'), $request->getParam('orderAmount'), $request->getParam('bankCode'), $request->getParam('cardType'), $channels);
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannel fetchConfig失败", $merchantChannelData);
            }
        }

        if ($code == 'SUCCESS') {
            $channelConfig = $merchantChannel->getRandConfig($merchantChannelConfig);
            if (!isset($channels[$channelConfig['channel']])) {
                $code = 'E2003';
                $logger->debug("merchantChannel channel查找失败");
            } else {
                $channel = $channels[$channelConfig['channel']];
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('backNoticeUrl'))) {
            $backNoticeUrl = parse_url($request->getParam('backNoticeUrl'));
            $host = isset($backNoticeUrl['host']) ? $backNoticeUrl['host'] : '';
            $domain = trim(str_replace(['http://', 'https://'], '', $merchantData['domain']));
            if ($merchantData['openCheckDomain'] && !empty($merchantData['domain']) && $host != $domain) {
                $code = 'E2103';
                $logger->debug("回调域名校验失败", ['domain' => $domain, 'backNoticeUrl' => $request->getParam('backNoticeUrl')]);
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('merchantOrderNo'))) {
            $d = (new PlatformPayOrder)->getCacheByMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if (!empty($d)) {
                $code = 'E2100';
            }
        }

        if ($code == 'SUCCESS') {
            try {
                $params = $request->getParams();
                $params['platformOrderNo'] = Tools::getPlatformOrderNo('P');
                $params['channelMerchantId'] = $channelConfig['channelMerchantId'];
                $channelOrder = (new ChannelProxy)->getPayOrder($params);
                if ($channelOrder['status'] != 'Success') {
                    throw new \Exception($channelOrder['failReason']);
                }
            } catch (\Exception $e) {
                $logger->debug("getPayOrder失败:" . $e->getMessage());
                $code = 'E1004';
            }
        }

        if ($code == 'SUCCESS') {
            $channelMerchantRate = new ChannelMerchantRate;
            $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

            // if ($request->getParam('merchantNo') == '88888888') {
            //     $logger->debug("merchantRateData test", $merchantRateData);
            // }

            try {
                $channelSetId = $channelConfig['setId'];
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Pay');
                if ($serviceCharge === null) {
                    throw new \Exception("支付getServiceCharge异常");
                }

                $channelServiceCharge = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $params, 'Pay');
                if ($channelServiceCharge === null) {
                    throw new \Exception("getChannelServiceCharge异常");
                }
                $db->getConnection()->beginTransaction();
                $request->channel = $channelConfig['channel'];
                $channelOrderNo = $channelOrder['orderNo'];
                $data = (new PlatformPayOrder())->create($request,
                    $params['platformOrderNo'],
                    bcmul($params['orderAmount'] , 100 ,0)/100,
                    $channelConfig['channel'],
                    $channelConfig['channelMerchantId'],
                    $channelConfig['channelMerchantNo'],
                    $channelSetId,
                    $channelOrderNo,
                    $serviceCharge,
                    $channelServiceCharge
                );
                $db->getConnection()->commit();
            } catch (\Exception $e) {
                $logger->debug("create失败" . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }

//        if ($code == 'SUCCESS' && $request->getMethod() == 'GET') {
//            Tools::JsRedirect($channelOrder['payUrl']);
//        }

        if ($code == 'SUCCESS') {
            $biz = [
                'platformOrderNo' => $data->platformOrderNo,
                'payUrl' => $channelOrder['payUrl'],
            ];
            $sign = Tools::getSign($biz, Tools::decrypt($merchantData['signKey']));

            // (new PlatformPayOrder())->success($data->toArray(), $data->orderAmount);
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            if($code == 'E1001'){
                $return = [
                    'code' => $code,
                    'msg' => $msg,
                ];
            }else{
                $return = [
                    'code' => $code,
                    'msg' => $this->c->code['status'][$code],
                ];
            }
            return $response->withStatus(200)->withJson($return);
        }
    }

    public function queryPayOrder(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'sign' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
        }

        if (empty($request->getParam('merchantOrderNo')) && empty($request->getParam('platformOrderNo'))) {
            $code = 'E1001';
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openQuery']) {
                $code = 'E2002';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('platformOrderNo'))) {
            $data = (new PlatformPayOrder)->getCacheByPlatformOrderNo($request->getParam('platformOrderNo'));
            if (empty($data)) {
                $code = 'E2101';
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('merchantOrderNo'))) {
            $data = (new PlatformPayOrder)->getCacheByMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if (empty($data)) {
                $code = 'E2101';
            }

        }

        if ($code == 'SUCCESS') {
            if ($data['merchantNo'] != $request->getParam('merchantNo')) {
                $code = 'E2101';
            }
        }

        if ($code == 'SUCCESS') {
            $biz = [
                'merchantNo' => $data['merchantNo'],
                'merchantOrderNo' => $data['merchantOrderNo'],
                'platformOrderNo' => $data['platformOrderNo'],
                'orderStatus' => $data['orderStatus'],
            ];

            if ($biz['orderStatus'] == 'Success') {
                $biz['payTime'] = date('YmdHis', strtotime($data['channelNoticeTime']));
            }

            $sign = Tools::getSign($biz, $merchantData['signKey']);
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
            ]);
        }
    }

    public function querySettlementOrder(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'sign' => Validator::noWhitespace()->notBlank(),
        ]);
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'querySettlementOrder';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->debug("代付请求");

        if (!$validator->isValid()) {
            $code = 'E1001';
        }

        if (empty($request->getParam('merchantOrderNo')) && empty($request->getParam('platformOrderNo'))) {
            $code = 'E1001';
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openQuery']) {
                $code = 'E2002';
            } else if (!Tools::isIpWhite($merchantData)) {
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('platformOrderNo'))) {
            $data = (new PlatformSettlementOrder)->getCacheByPlatformOrderNo($request->getParam('platformOrderNo'));
            if (empty($data)) {
                $code = 'E2101';
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('merchantOrderNo'))) {
            $data = (new PlatformSettlementOrder)->getCacheByMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if (empty($data)) {
                $code = 'E2101';
            }
        }

        if ($code == 'SUCCESS') {
            if ($data['merchantNo'] != $request->getParam('merchantNo')) {
                $code = 'E2101';
            }
        }

        if ($code == 'SUCCESS') {
            $orderMsg = '';
            switch ($data['orderStatus']) {
                case 'WaitTransfer':
                case 'Transfered': $orderMsg = '代付中';break;
                case 'Success': $orderMsg = '代付成功';break;
                case 'Fail': $orderMsg = $data['failReason'];break;
            }
            $biz = [
                'merchantNo' => $data['merchantNo'],
                'merchantOrderNo' => $data['merchantOrderNo'],
                'platformOrderNo' => $data['platformOrderNo'],
                'orderAmount' => $data['orderAmount'],
//                'realOrderAmount' => $data['realOrderAmount'],
                'orderStatus' => ($data['orderStatus'] == 'Exception' ? 'Transfered' : $data['orderStatus']),
                'orderMsg' => $orderMsg,
            ];

            if ($biz['orderStatus'] == 'Success') {
                $biz['payTime'] = date('YmdHis', strtotime($data['channelNoticeTime']));
            }

            $sign = Tools::getSign($biz, $merchantData['signKey']);
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            $fileReason = '';
            if(isset($data['merchantOrderNo'])){
                $fileReason = trim(PlatformSettlementOrder::where('merchantOrderNo',$data['merchantOrderNo'])->value('failReason'),'自动处理-代付失败,');
            }
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
            ]);
        }
    }

    public function queryBalance(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'sign' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openQuery']) {
                $code = 'E2002';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantAmountData = (new MerchantAmount)->getCacheByMerchantId($merchantData['merchantId']);
            $biz = [
                'merchantNo' => $merchantData['merchantNo'],
                'balance' => sprintf("%.2f", $merchantAmountData['settlementAmount']),
            ];

            $sign = Tools::getSign($biz, $merchantData['signKey']);
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
            ]);
        }
    }

    public function settlement(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $msg = '';
        $data = [];
        $bankCode = array_keys($this->c->code['bankCode']);
        $channels = $this->c->code['channel'];
        $logger = $this->c->logger;

        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->debug("代付请求");

        $db = $this->c->database;
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'merchantOrderNo' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'merchantReqTime' => Validator::date('YmdHis')->noWhitespace()->notBlank(),
            'orderAmount' => Validator::floatVal()->noWhitespace()->notBlank(),
            'tradeSummary' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'bankAccountNo' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'bankAccountName' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'province' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'city' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'bankName' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
//            'bankCode' => Validator::in($bankCode)->noWhitespace()->notBlank(),
            'bankCode' => Validator::in($bankCode)->notBlank(),
            'orderReason' => Validator::stringType()->length(1, 100)->noWhitespace()->notBlank(),
            'requestIp' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
            'backNoticeUrl' => Validator::stringType()->length(1, 200)->url()->noWhitespace()->notBlank(),
            'merchantParam' => Validator::length(0, 500)->noWhitespace(),
            'sign' => Validator::stringType()->length(32, 32)->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
            $msg = json_encode($validator->getErrors());
            $logger->debug('valid', $validator->getErrors());
            // print_r($validator->getErrors());
        }

        if ($code == 'SUCCESS') {
            $st = strtotime($request->getParam('merchantReqTime'));
            if ($st > time() + 3 * 60 || $st < time() - 3 * 60) {

                $logger->error("merchantReqTime不正确");
                $code = 'E1001';
                $msg = 'merchantReqTime不正确-'.$st;
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $is_exist = $merchant->getCacheMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if($is_exist == false){//订单号已存在
                $code = 'E2100';
                $logger->error("代付请求：商户订单号重复");
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            $settlementOpenSwitch = 'openSettlement';
            $aliCode = 'E2002';
            if($request->getParam('bankCode') == "ALIPAY") {
                $settlementOpenSwitch = 'openAliSettlement';
                $aliCode = 'E2007';
            }
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData[$settlementOpenSwitch]) {
                $code = $aliCode;
            } else if (!Tools::isIpWhite($merchantData)) {
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $logger->error("代付验签失败");
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS') {
            if ($merchant->isExceedDaySettleAmountLimit($request->getParam('orderAmount'), $merchantData)) {
                $logger->debug("超过商户单日累计代付总金额限制");
                $code = 'E2107';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::isAllowIPAccess($request->getParam('requestsIp'))) {
                $logger->error("isAllowIPAccess");
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantRate = new MerchantRate();
            $merchantRateData = $merchantRate->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantRateData)) {
                $logger->error("代付请求：商户未配置费率");
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], '', $merchantData);
            $params = $request->getParams();
            $orderAmount = bcmul($request->getParam('orderAmount'), 100 ,0)/100;
            $params['orderAmount'] = $orderAmount;
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');
            if ($request->getParam('orderAmount') + $serviceCharge > $amountData['availableBalance']) {
                $logger->error("代付余额不足");
                $code = 'E2105';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannelSettlement();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantChannelData)) {
                $logger->error("代付请求：未配置商户代付通道");
                $code = 'E2003';
            }
        }

        if ($code == 'SUCCESS') {
            $bankCode = $request->getParam('bankCode');
            if($bankCode != 'ALIPAY'){//判断银行是否维护
                $bankModel = new Banks();
                if($bankModel->is_open($bankCode) === false){
                    $logger->error("代付银行维护中");
                    $code = 'E2110';
                }
            }
        }

        //是否是支付宝代付到银行卡
        $alipayToBank = false;
        if ($code == 'SUCCESS') {
            $settlementType = '';
            $b = $request->getParam('bankCode');
            $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $request->getParam('bankAccountNo'), $channels, $request->getParam('bankCode'));
            if(in_array($b,array_keys(Merchant::$aliBanks)) && empty($merchantChannelConfig) && isset($merchantData['openAliSettlement']) && $merchantData['openAliSettlement']){     //若无任何可代付到银行卡的上游，读取支付宝代付银行卡配置
                $alipayToBank = true;
                $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $request->getParam('bankAccountNo'), $channels, 'ALIPAY');
            }
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannelSettelement fetchConfig失败", $merchantChannelData);
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('backNoticeUrl'))) {
            $backNoticeUrl = parse_url($request->getParam('backNoticeUrl'));
            $host = isset($backNoticeUrl['host']) ? $backNoticeUrl['host'] : '';
            $domain = trim(str_replace(['http://', 'https://'], '', $merchantData['domain']));
            if ($merchantData['openCheckDomain'] && !empty($merchantData['domain']) && $host != $domain) {
                $code = 'E2103';
                $logger->debug("回调域名校验失败", ['domain' => $domain, 'backNoticeUrl' => $request->getParam('backNoticeUrl')]);
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('merchantOrderNo'))) {
            $d = (new PlatformSettlementOrder)->getCacheByMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if (!empty($d)) {
                $code = 'E2100';
                $logger->error("代付请求：商户订单号重复");
            }
        }

        if ($code == 'SUCCESS') {
            $blackUserSettlement = new BlackUserSettlement();
            $isblackUserExists = $blackUserSettlement->checkBlackUser($request->getParam('bankCode'),$request->getParam('bankAccountName'),$request->getParam('bankAccountNo'));
            if($isblackUserExists){
                $code = 'E2201';
                $logger->error("代付请求：代付黑名单用户！");
            }
        }

        if ($code == 'SUCCESS') {//风控消息
            $warning = new \App\Logics\WarningLogic($this->c);
            $notify_params = [
                'merchantId' => $merchantData['merchantId'],//商户id
                'merchantNo' => $merchantData['merchantNo'],//商户号
                'accountName' => $request->getParam('bankAccountName'),//支付宝姓名
                'accountNo' => $request->getParam('bankAccountNo'),//支付宝账户
                'orderAmount' => $request->getParam('orderAmount'),//出款金额
                'settlement_type' => 'API出款',//出款类型
            ];
            $warning->merchant_notify($notify_params);
        }

        if ($code == 'SUCCESS') {
            // $channelMerchantRate = new ChannelMerchantRate;
            // $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

            try {
                $db->getConnection()->beginTransaction();
                $accountDate = Tools::getAccountDate($merchantData['settlementTime']);
                $merchantAmount = new MerchantAmount;
                $merchantAmountLockData = $merchantAmount->where('merchantId', $merchantData['merchantId'])->lockForUpdate()->first();

                $orderAmount = bcmul($request->getParam('orderAmount'),100,0) / 100;
                $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], $merchantAmountLockData->toArray(), $merchantData);

                $platformOrderNo = Tools::getPlatformOrderNo('S');
                $params = $request->getParams();
                $params['orderAmount'] = $orderAmount;
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');

                if ($serviceCharge === null) {
                    throw new \Exception("代付getServiceCharge异常");
                }

                if ($orderAmount + $serviceCharge > $amountData['availableBalance']) {
                    throw new \Exception("余额异常");
                }

                // $channelServiceCharge = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $params, 'Settlement');
                // if ($channelServiceCharge === null) {
                //     throw new \Exception("getChannelServiceCharge异常");
                // }
                $PlatformSettlementOrder = new PlatformSettlementOrder();
                if(!$merchantData['openAutoSettlement']  || (getenv('AUTO_SETTLEMENT_AMOUNT',0) && $orderAmount < getenv('AUTO_SETTLEMENT_AMOUNT',0)))  $settlementType = 'manualSettlement';
                $data = $PlatformSettlementOrder->create($request,
                    $platformOrderNo,
                    $orderAmount,
                    // $isWorkerday,
                    $settlementType,
                    $serviceCharge,
                    // $channelServiceCharge,
                    $merchantData['merchantNo'],
                    // $channelConfig['channel'],
                    // $channelConfig['channelMerchantId'],
                    // $channelConfig['channelMerchantNo'],
                    // $channelConfig['setId'],
                    // $channelConfig['settlementAccountType']
                    'API接口发起'
                );

                $merchantAmountLockData->settlementAmount = $merchantAmountLockData->settlementAmount - $orderAmount - $serviceCharge;
                // $merchantAmountLockData->settledAmount = $merchantAmountLockData->settledAmount + $orderAmount + $serviceCharge;
                // $merchantAmountLockData->todaySettlementAmount = $merchantAmountLockData->todaySettlementAmount + $orderAmount + $serviceCharge;
                // $merchantAmountLockData->todayServiceCharge = $merchantAmountLockData->todayServiceCharge + $serviceCharge;
                $merchantAmountLockData->save();

                Finance::insert([
                    [
                        'merchantId' => $data->merchantId,
                        'merchantNo' => $data->merchantNo,
                        'platformOrderNo' => $platformOrderNo,
                        'amount' => $orderAmount,
                        'balance' => $merchantAmountLockData->settlementAmount + $serviceCharge,
                        'financeType' => 'PayOut',
                        'accountDate' => $accountDate,
                        'accountType' => 'SettlementAccount',
                        'sourceId' => $data->orderId,
                        'sourceDesc' => '结算服务',
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'ports',
                        'summary' => $data->tradeSummary,
                    ],
                    [
                        'merchantId' => $data->merchantId,
                        'merchantNo' => $data->merchantNo,
                        'platformOrderNo' => $platformOrderNo,
                        'amount' => $serviceCharge,
                        'balance' => $merchantAmountLockData->settlementAmount,
                        'financeType' => 'PayOut',
                        'accountDate' => $accountDate,
                        'accountType' => 'ServiceChargeAccount',
                        'sourceId' => $data->orderId,
                        'sourceDesc' => '结算手续费',
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'ports',
                        'summary' => $data->tradeSummary,
                    ],
                ]);

                AmountPay::where('merchantId', $data->merchantId)
                    ->where('accountDate', $accountDate)
                    ->update(['balance' => $merchantAmountLockData->settlementAmount]);
                if($alipayToBank === true) {  //支付宝代付到银行卡，暂时不需要实际执行代付操作，人工打卡操作
                    $channel = current($merchantChannelConfig);
                    $PlatformSettlementOrder->start($data,'alipay',$channel['channelMerchantId'],$channel['channelMerchantNo'],$platformOrderNo,$serviceCharge);
                    $this->c->redis->incr('cacheSettlementKey');
                }elseif (!$merchantData['openAutoSettlement'] || (getenv('AUTO_SETTLEMENT_AMOUNT',0) && $orderAmount < getenv('AUTO_SETTLEMENT_AMOUNT',0))){
                    //手动处理代付
                    $this->c->redis->incr('cacheSettlementKey');
                }else {
                    (new SettlementFetchExecutor)->push(0, $platformOrderNo);
                }
                $merchantAmountLockData->refreshCache(['merchantId' => $merchantAmountLockData->merchantId]);

                (new Merchant)->incrCacheByDaySettleAmountLimit($data->merchantNo, bcmul($data->orderAmount , 100,0));

                $db->getConnection()->commit();
            } catch (\Exception $e) {
                $logger->error('Exception:' . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }

        if ($code == 'SUCCESS') {
            $biz = [
                'merchantNo' => $data->merchantNo,
                'merchantOrderNo' => $data->merchantOrderNo,
                'platformOrderNo' => $data->platformOrderNo,
                'orderStatus' => $data->orderStatus,
                'orderAmount' => $data->orderAmount,
            ];
            $sign = Tools::getSign($biz, $merchantData['signKey']);

            // (new PlatformSettlementOrder)->success($data->toArray());
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            if($code == 'E1001'){
                $return = [
                    'code' => $code,
                    'msg' => $msg,
                ];
            }else{
                $return = [
                    'code' => $code,
                    'msg' => $this->c->code['status'][$code],
                ];
            }
            $logger->error('代付请求返回，code:' . $code . '，msg:' . $this->c->code['status'][$code]);
            return $response->withStatus(200)->withJson($return);
        }
    }

    public function settlementPhp(Request $request, Response $response, $args)
    {
        $code = 'SUCCESS';
        $msg = '';
        $data = [];
        $bankCode = array_keys($this->c->code['bankCode']);
        $channels = $this->c->code['channel'];
        $logger = $this->c->logger;

        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->debug("代付请求");

        $db = $this->c->database;
        $validator = $this->c->validator->validate($request, [
            'merchantNo' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'merchantOrderNo' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'merchantReqTime' => Validator::date('YmdHis')->noWhitespace()->notBlank(),
            'orderAmount' => Validator::floatVal()->noWhitespace()->notBlank(),
            'tradeSummary' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'bankAccountNo' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'bankAccountName' => Validator::stringType()->length(1, 50)->notBlank(),
            'province' => Validator::stringType()->length(1, 50)->notBlank(),
            'city' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'bankName' => Validator::stringType()->length(1, 50)->notBlank(),
//            'bankCode' => Validator::in($bankCode)->noWhitespace()->notBlank(),
            'bankCode' => Validator::in($bankCode)->notBlank(),
            'orderReason' => Validator::stringType()->length(1, 100)->noWhitespace()->notBlank(),
            'requestIp' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
            'backNoticeUrl' => Validator::stringType()->length(1, 200)->url()->noWhitespace()->notBlank(),
            'merchantParam' => Validator::length(0, 500)->noWhitespace(),
            'sign' => Validator::stringType()->length(32, 32)->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
            $msg = json_encode($validator->getErrors());
            $logger->debug('valid', $validator->getErrors());
            // print_r($validator->getErrors());
        }

        if ($code == 'SUCCESS') {
            $st = strtotime($request->getParam('merchantReqTime'));
            if ($st > time() + 3 * 60 || $st < time() - 3 * 60) {

                $logger->error("merchantReqTime不正确");
                $code = 'E1001';
                $msg = 'merchantReqTime不正确-'.$st;
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $is_exist = $merchant->getCacheMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if($is_exist == false){//订单号已存在
                $code = 'E2100';
                $logger->error("代付请求：商户订单号重复");
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($request->getParam('merchantNo'));
            $settlementOpenSwitch = 'openSettlement';
            $aliCode = 'E2002';
            if($request->getParam('bankCode') == "ALIPAY") {
                $settlementOpenSwitch = 'openAliSettlement';
                $aliCode = 'E2007';
            }
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData[$settlementOpenSwitch]) {
                $code = $aliCode;
            } else if (!Tools::isIpWhite($merchantData)) {
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::checkSign($merchantData, $request->getParams())) {
                $logger->error("代付验签失败");
                $code = 'E1005';
            }
        }

        if ($code == 'SUCCESS') {
            if ($merchant->isExceedDaySettleAmountLimit($request->getParam('orderAmount'), $merchantData)) {
                $logger->debug("超过商户单日累计代付总金额限制");
                $code = 'E2107';
            }
        }

        if ($code == 'SUCCESS') {
            if (!Tools::isAllowIPAccess($request->getParam('requestsIp'))) {
                $logger->error("isAllowIPAccess");
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantRate = new MerchantRate();
            $merchantRateData = $merchantRate->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantRateData)) {
                $logger->error("代付请求：商户未配置费率");
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], '', $merchantData);
            $params = $request->getParams();
            $orderAmount = bcmul($request->getParam('orderAmount'), 100 ,0)/100;
            $params['orderAmount'] = $orderAmount;
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');
            if ($request->getParam('orderAmount') + $serviceCharge > $amountData['availableBalance']) {
                $logger->error("代付余额不足");
                $code = 'E2105';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannelSettlement();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($request->getParam('merchantNo'));
            if (empty($merchantChannelData)) {
                $logger->error("代付请求：未配置商户代付通道");
                $code = 'E2003';
            }
        }

        if ($code == 'SUCCESS') {
            $bankCode = $request->getParam('bankCode');
            if($bankCode != 'ALIPAY'){//判断银行是否维护
                $bankModel = new Banks();
                if($bankModel->is_open($bankCode) === false){
                    $logger->error("代付银行维护中");
                    $code = 'E2110';
                }
            }
        }

        //是否是支付宝代付到银行卡
        $alipayToBank = false;
        if ($code == 'SUCCESS') {
            $settlementType = '';
            $b = $request->getParam('bankCode');
            $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $request->getParam('bankAccountNo'), $channels, $request->getParam('bankCode'));
            if(in_array($b,array_keys(Merchant::$aliBanks)) && empty($merchantChannelConfig) && isset($merchantData['openAliSettlement']) && $merchantData['openAliSettlement']){     //若无任何可代付到银行卡的上游，读取支付宝代付银行卡配置
                $alipayToBank = true;
                $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $request->getParam('bankAccountNo'), $channels, 'ALIPAY');
            }
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannelSettelement fetchConfig失败", $merchantChannelData);
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('backNoticeUrl'))) {
            $backNoticeUrl = parse_url($request->getParam('backNoticeUrl'));
            $host = isset($backNoticeUrl['host']) ? $backNoticeUrl['host'] : '';
            $domain = trim(str_replace(['http://', 'https://'], '', $merchantData['domain']));
            if ($merchantData['openCheckDomain'] && !empty($merchantData['domain']) && $host != $domain) {
                $code = 'E2103';
                $logger->debug("回调域名校验失败", ['domain' => $domain, 'backNoticeUrl' => $request->getParam('backNoticeUrl')]);
            }
        }

        if ($code == 'SUCCESS' && !empty($request->getParam('merchantOrderNo'))) {
            $d = (new PlatformSettlementOrder)->getCacheByMerchantOrderNo($request->getParam('merchantNo'), $request->getParam('merchantOrderNo'));
            if (!empty($d)) {
                $code = 'E2100';
                $logger->error("代付请求：商户订单号重复");
            }
        }

        if ($code == 'SUCCESS') {
            $blackUserSettlement = new BlackUserSettlement();
            $isblackUserExists = $blackUserSettlement->checkBlackUser($request->getParam('bankCode'),$request->getParam('bankAccountName'),$request->getParam('bankAccountNo'));
            if($isblackUserExists){
                $code = 'E2201';
                $logger->error("代付请求：代付黑名单用户！");
            }
        }

//        if ($code == 'SUCCESS') {//风控消息
//            $warning = new \App\Logics\WarningLogic($this->c);
//            $notify_params = [
//                'merchantId' => $merchantData['merchantId'],//商户id
//                'merchantNo' => $merchantData['merchantNo'],//商户号
//                'accountName' => $request->getParam('bankAccountName'),//支付宝姓名
//                'accountNo' => $request->getParam('bankAccountNo'),//支付宝账户
//                'orderAmount' => $request->getParam('orderAmount'),//出款金额
//                'settlement_type' => 'API出款',//出款类型
//            ];
//            $warning->merchant_notify($notify_params);
//        }

        if ($code == 'SUCCESS') {
            // $channelMerchantRate = new ChannelMerchantRate;
            // $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

            try {
                $db->getConnection()->beginTransaction();
                $accountDate = Tools::getAccountDate($merchantData['settlementTime']);
                $merchantAmount = new MerchantAmount;
                $merchantAmountLockData = $merchantAmount->where('merchantId', $merchantData['merchantId'])->lockForUpdate()->first();

                $orderAmount = bcmul($request->getParam('orderAmount'),100,0) / 100;
                $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], $merchantAmountLockData->toArray(), $merchantData);

                $platformOrderNo = Tools::getPlatformOrderNo('S');
                $params = $request->getParams();
                $params['orderAmount'] = $orderAmount;
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');

                if ($serviceCharge === null) {
                    throw new \Exception("代付getServiceCharge异常");
                }

                if ($orderAmount + $serviceCharge > $amountData['availableBalance']) {
                    throw new \Exception("余额异常");
                }

                // $channelServiceCharge = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $params, 'Settlement');
                // if ($channelServiceCharge === null) {
                //     throw new \Exception("getChannelServiceCharge异常");
                // }
                $PlatformSettlementOrder = new PlatformSettlementOrder();
                if(!$merchantData['openAutoSettlement'] || (getenv('AUTO_SETTLEMENT_AMOUNT',0) && $orderAmount < getenv('AUTO_SETTLEMENT_AMOUNT',0)))  $settlementType = 'manualSettlement';
                $data = $PlatformSettlementOrder->create($request,
                    $platformOrderNo,
                    $orderAmount,
                    // $isWorkerday,
                    $settlementType,
                    $serviceCharge,
                    // $channelServiceCharge,
                    $merchantData['merchantNo'],
                    // $channelConfig['channel'],
                    // $channelConfig['channelMerchantId'],
                    // $channelConfig['channelMerchantNo'],
                    // $channelConfig['setId'],
                    // $channelConfig['settlementAccountType']
                    'API接口发起'
                );

                $merchantAmountLockData->settlementAmount = $merchantAmountLockData->settlementAmount - $orderAmount - $serviceCharge;
                // $merchantAmountLockData->settledAmount = $merchantAmountLockData->settledAmount + $orderAmount + $serviceCharge;
                // $merchantAmountLockData->todaySettlementAmount = $merchantAmountLockData->todaySettlementAmount + $orderAmount + $serviceCharge;
                // $merchantAmountLockData->todayServiceCharge = $merchantAmountLockData->todayServiceCharge + $serviceCharge;
                $merchantAmountLockData->save();

                Finance::insert([
                    [
                        'merchantId' => $data->merchantId,
                        'merchantNo' => $data->merchantNo,
                        'platformOrderNo' => $platformOrderNo,
                        'amount' => $orderAmount,
                        'balance' => $merchantAmountLockData->settlementAmount + $serviceCharge,
                        'financeType' => 'PayOut',
                        'accountDate' => $accountDate,
                        'accountType' => 'SettlementAccount',
                        'sourceId' => $data->orderId,
                        'sourceDesc' => '结算服务',
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'ports',
                        'summary' => $data->tradeSummary,
                    ],
                    [
                        'merchantId' => $data->merchantId,
                        'merchantNo' => $data->merchantNo,
                        'platformOrderNo' => $platformOrderNo,
                        'amount' => $serviceCharge,
                        'balance' => $merchantAmountLockData->settlementAmount,
                        'financeType' => 'PayOut',
                        'accountDate' => $accountDate,
                        'accountType' => 'ServiceChargeAccount',
                        'sourceId' => $data->orderId,
                        'sourceDesc' => '结算手续费',
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'ports',
                        'summary' => $data->tradeSummary,
                    ],
                ]);

                AmountPay::where('merchantId', $data->merchantId)
                    ->where('accountDate', $accountDate)
                    ->update(['balance' => $merchantAmountLockData->settlementAmount]);
                if($alipayToBank === true) {  //支付宝代付到银行卡，暂时不需要实际执行代付操作，人工打卡操作
                    $channel = current($merchantChannelConfig);
                    $PlatformSettlementOrder->start($data,'alipay',$channel['channelMerchantId'],$channel['channelMerchantNo'],$platformOrderNo,$serviceCharge);
                    $this->c->redis->incr('cacheSettlementKey');
                }elseif (!$merchantData['openAutoSettlement'] || (getenv('AUTO_SETTLEMENT_AMOUNT',0) && $orderAmount < getenv('AUTO_SETTLEMENT_AMOUNT',0))){
                    //手动处理代付
                    $this->c->redis->incr('cacheSettlementKey');
                }else {
                    (new SettlementFetchExecutor)->push(0, $platformOrderNo);
                }
                $merchantAmountLockData->refreshCache(['merchantId' => $merchantAmountLockData->merchantId]);

                (new Merchant)->incrCacheByDaySettleAmountLimit($data->merchantNo, bcmul($data->orderAmount , 100,0));

                $db->getConnection()->commit();
            } catch (\Exception $e) {
                $logger->error('Exception:' . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }

        if ($code == 'SUCCESS') {
            $biz = [
                'merchantNo' => $data->merchantNo,
                'merchantOrderNo' => $data->merchantOrderNo,
                'platformOrderNo' => $data->platformOrderNo,
                'orderStatus' => $data->orderStatus,
                'orderAmount' => $data->orderAmount,
            ];
            $sign = Tools::getSign($biz, $merchantData['signKey']);

            // (new PlatformSettlementOrder)->success($data->toArray());
            return $response->withStatus(200)->withJson([
                'code' => $code,
                'msg' => $this->c->code['status'][$code],
                'sign' => $sign,
                'biz' => $biz,
            ]);
        } else {
            if($code == 'E1001'){
                $return = [
                    'code' => $code,
                    'msg' => $msg,
                ];
            }else{
                $return = [
                    'code' => $code,
                    'msg' => $this->c->code['status'][$code],
                ];
            }
            $logger->error('代付请求返回，code:' . $code . '，msg:' . $this->c->code['status'][$code]);
            return $response->withStatus(200)->withJson($return);
        }
    }

    public function settlementRecharge(Request $request, Response $response, $args){

    }

}
