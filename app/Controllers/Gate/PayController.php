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

class PayController extends Controller
{

    public function payOrder(Request $request, Response $response, $args)
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
            'request_time' => Validator::date()->noWhitespace()->notBlank(),
            'amount' => Validator::floatVal()->noWhitespace()->notBlank(),
            'summary' => Validator::noWhitespace()->notBlank(),
            'model' => Validator::in($payModelCode)->noWhitespace()->notBlank(),
            'pay_type' => Validator::in($payTypeCode)->noWhitespace()->notBlank(),
            'card_type' => Validator::in($cardTypeCode)->noWhitespace()->notBlank(),
            'terminal' => Validator::in($userTerminalCode)->noWhitespace()->notBlank(),
            'userIp' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
            'noticeUrl' => Validator::url()->noWhitespace()->notBlank(),
            'sign' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $code = 'E1001';
            $msg = json_encode($validator->getErrors());
            $logger->info($validator->getErrors());
            // print_r($validator->getErrors());
        }

//        if ($code == 'SUCCESS') {
//            $st = strtotime($request->getParam('request_time'));
//            if ($st > time() + 3 * 60 || $st < time() - 3 * 60) {
//                $code = 'E1001';
//                $msg = 'request_time不正确-'.$st;
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
        $params = $request->getParams();
        $params['orderAmount'] = $params['amount'];
        $params['payType'] = $params['pay_type'];
        $params['tradeSummary'] = $params['summary'];
        $params['payModel'] = $params['model'];
        $params['backNoticeUrl'] = $params['noticeUrl'];
        $params['cardType'] = $params['card_type'];
        $params['userTerminal'] = $params['terminal'];
        $params['merchantReqTime'] = $params['request_time'];

        $data['orderAmount'] = $params['amount'];
        $data['payType'] = $params['pay_type'];
        $data['tradeSummary'] = $params['summary'];
        $data['payModel'] = $params['model'];
        $data['backNoticeUrl'] = $params['noticeUrl'];
        $data['cardType'] = $params['card_type'];
        $data['userTerminal'] = $params['terminal'];
        $data['merchantReqTime'] = $params['request_time'];
        $request = $request->withQueryParams($data);
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
            $merchantChannelConfig = $merchantChannel->fetchConfig($request->getParam('merchantNo'), $merchantChannelData, $request->getParam('pay_type'), $request->getParam('amount'), $request->getParam('bankCode'), $request->getParam('card_type'), $channels);
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

        if ($code == 'SUCCESS' && !empty($request->getParam('noticeUrl'))) {
            $noticeUrl = parse_url($request->getParam('noticeUrl'));
            $host = isset($noticeUrl['host']) ? $noticeUrl['host'] : '';
            $domain = trim(str_replace(['http://', 'https://'], '', $merchantData['domain']));
            if ($merchantData['openCheckDomain'] && !empty($merchantData['domain']) && $host != $domain) {
                $code = 'E2103';
                $logger->debug("回调域名校验失败", ['domain' => $domain, 'noticeUrl' => $request->getParam('noticeUrl')]);
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
                    bcmul($params['amount'] , 100 ,0)/100,
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
    
}
