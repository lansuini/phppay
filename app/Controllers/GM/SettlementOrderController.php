<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\Merchant;
use App\Models\MerchantChannel;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementRechargeOrder;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use App\Models\SystemCheckLog;
use App\Queues\SettlementFetchExecutor;
use App\Queues\SettlementActiveQueryExecutor;
use App\Queues\SettlementNotifyExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;
use Requests;

class SettlementOrderController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        $redis = $this->c->redis;
        $redis->setex('cacheSettlementKey', 7*60*60*24, 0);
        return $this->c->view->render($response, 'gm/settlementorder/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    public function rechargeOrder(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/rechargeorder/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    public function rechargeOrderSearch(Request $request, Response $response, $args)
    {

        $merchant = new Merchant();
        $model = new PlatformSettlementOrder();
        $model = new SettlementRechargeOrder();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $orderStatus = $request->getParam('orderStatus');
        $orderType= $request->getParam('orderType');
        $bankCode = $request->getParam('bankCode');
        $bankAccountNo = $request->getParam('bankAccountNo');
        $bankAccountName = $request->getParam('bankAccountName');
        $beginTime = $request->getParam('beginTime',date('Y-m-d'));
        $endTime = $request->getParam('endTime',date('Y-m-d'.' 23:59:59'));
        $channel = $request->getParam('channel');
        $merchantId = 0;

        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $merchantNo && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('settlementRechargeOrderNo', $platformOrderNo);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $orderStatus && $model = $model->where('orderStatus', $orderStatus);
        $orderType && $model = $model->where('type', $orderType);
        $bankCode && $model = $model->where('bankCode', $bankCode);
        $bankAccountNo && $model = $model->where('bankAccountNo', Tools::encrypt($bankAccountNo));
        $bankAccountName && $model = $model->where('bankAccountName', $bankAccountName);
        $channel && $model = $model->where('channel', $channel);
        $model1 = clone $model;
        $total = $model->count();
        $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();

        $where = [];
        $where[] = '1=1'; 

        $merchantId && $where[] = "merchantId = " . $merchantId;

        $platformOrderNo && $where[] = "settlementRechargeOrderNo = '" . $platformOrderNo . "'";
        $merchantOrderNo && $where[] = "merchantOrderNo = '" . $merchantOrderNo . "'";

        $orderStatus && $where[] = "orderStatus='" . $orderStatus . "'";

        $beginTime && $where[] = "created_at>='" . $beginTime . "'";
        $endTime && $where[] = "created_at<='" . $endTime . "'";

        $whereStr = implode(' and ', $where);
        $stat = $model1->selectRaw("
        count(id) as number,
        sum(orderAmount) as orderAmount,
        (select sum(orderAmount) from settlement_recharge_order where {$whereStr} and orderStatus = 'Exception') as exceptionAmount,
        (select count(id) from settlement_recharge_order where {$whereStr} and orderStatus = 'Exception') as exceptionNumber,
        (select sum(orderAmount) from settlement_recharge_order where {$whereStr} and orderStatus = 'Success') as successAmount,
        (select count(id) from settlement_recharge_order where {$whereStr} and orderStatus = 'Success') as successNumber,
        (select sum(orderAmount) from settlement_recharge_order where {$whereStr} and orderStatus = 'Fail') as failAmount,
        (select count(id) from settlement_recharge_order where {$whereStr} and orderStatus = 'Fail') as failNumber,
        (select sum(orderAmount) from settlement_recharge_order where {$whereStr} and orderStatus = 'Transfered') as transferedAmount,
        (select count(id) from settlement_recharge_order where {$whereStr} and orderStatus = 'Transfered') as transferedNumber
        ")->first();

        if (!empty($stat)) {
            $stat = $stat->toArray();
        } else {
            $stat = array();
        }

        $stat['orderAmount'] = number_format($stat['orderAmount'] ?? 0, 2);
        $stat['exceptionAmount'] = number_format($stat['exceptionAmount'] ?? 0, 2);
        $stat['successAmount'] = number_format($stat['successAmount'] ?? 0, 2);
        $stat['failAmount'] = number_format($stat['failAmount'] ?? 0, 2);
        $stat['transferedAmount'] = number_format($stat['transferedAmount'] ?? 0, 2);

        $rows = [];

        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [

                'channel' => $v->channel,
                "channelDesc" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['name'] : null,
                "channelMerchantNo" => $v->channelMerchantNo,
                "channelNoticeTime" => Tools::getJSDatetime($v->channelNoticeTime),
                "createTime" => Tools::getJSDatetime($v->created_at),
                "merchantNo" => $v->merchantNo,
//                "merchantOrderNo" => $v->merchantOrderNo,
                "orderAmount" => $v->realOrderAmount,
                "orderId" => Tools::getHashId($v->id),
                // "orderId" => $v->orderId,
//                "orderReason" => $v->orderReason,
                "orderStatus" => $v->orderStatus,
                "orderStatusDesc" => $code['recharge']['orderStatus'][$v->orderStatus] ?? null,
                "type" => $v->type,
                "typeDesc" => $code['recharge']['orderType'][$v->type] ?? null,
                "platformOrderNo" => $v->settlementRechargeOrderNo,
                "shortName" => isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]['shortName'] : null,
                "serviceCharge" => $v->serviceCharge,
                "channelServiceCharge" => $v->channelServiceCharge,
//                "callbackLimit" => $v->callbackLimit,
//                "callbackSuccess" => $v->callbackSuccess,
            ];
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
            'stat' => $stat,
        ]);

    }

    public function getStatus(Request $request, Response $response, $args){
        $orderId = $request->getParam('orderId');
        $orderId = Tools::getIdByHash($orderId);
        $model = new PlatformSettlementOrder();
        $data = $model->where('orderId', $orderId)->first();
        if((time() - strtotime($data->created_at)) < 120){
            return $response->withJson([
                "status" => "Execute",
                "orderNo" => "",
                "failReason" => "2分钟内新生成的订单不支持状态同步",
                "orderAmount" => 0
            ]);
        }
        $res = (new SettlementActiveQueryExecutor())->syncSettlementOrder($data->platformOrderNo);
        return $response->withJson($res);
    }

    private function exportSettlementHead(){
        return [
            'merchantNo' => '商户号',
            'shortName' => '商户简称',
            'platformOrderNo' => '平台订单号',
            'merchantOrderNo' => '商户订单号',
            'orderAmount' => '订单金额',
            'serviceCharge' => '平台手续费',
            'channelServiceCharge' => '上游手续费',
            'agentFee' => '代理手续费',
            'bankCodeDesc' => '收款银行',
            'bankAccountNo' => '收款卡号',
            'orderReason' => '用途',
            'orderStatusDesc' => '订单状态',
            'createDate' => '订单生成时间',
            'noticeDate' => '处理时间',
        ];
    }

    public function search(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new PlatformSettlementOrder();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $orderStatus = $request->getParam('orderStatus');
        $bankCode = $request->getParam('bankCode');
        $bankAccountNo = $request->getParam('bankAccountNo');
        $bankAccountName = $request->getParam('bankAccountName');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');

        $agentName = $request->getParam('agentName');

        $createBeginTime = $request->getParam('createBeginTime',date('Y-m-d H:i:s'));
        $createEndTime = $request->getParam('createEndTime',date('Y-m-d') . '23:59:59');

        $channel = $request->getParam('channel');
        $export = $request->getParam('export');

        $minMoney=$request->getParam('minMoney');
        $maxMoney=$request->getParam('maxMoney');

        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $merchantId = 0;

        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $merchantNo && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $beginTime && $model = $model->where('channelNoticeTime', '>=', $beginTime);
        $endTime && $model = $model->where('channelNoticeTime', '<=', $endTime);

        $minMoney && $model=$model->where('orderAmount','>=',$minMoney);
        $maxMoney && $model= $model->where('orderAmount','<=',$maxMoney);

        $createBeginTime && $model = $model->where('created_at', '>=', $createBeginTime);
        $createEndTime && $model = $model->where('created_at', '<=', $createEndTime);

        $orderStatus && $model = $model->where('orderStatus', $orderStatus);
        $bankCode && $model = $model->where('bankCode', $bankCode);
        $bankAccountNo && $model = $model->where('bankAccountNo', Tools::encrypt($bankAccountNo));
        $bankAccountName && $model = $model->where('bankAccountName', $bankAccountName);
        $channel && $model = $model->where('channel', $channel);
        $channelMerchantNo && $model = $model->where('channelMerchantNo', $channelMerchantNo);
        $agentName && $model = $model->where('agentName', $agentName);
        $model = $model->where(function($query){
            $query->where('isLock',0)
                ->orWhere('lockUser',$_SESSION['userName']);
        });
        if(!$export) {
            $model1 = clone $model;
            $total = $model->count();
            $data = $model->orderBy('orderId', 'desc')->offset($offset)->limit($limit)->get();

            $where = [];
            $where[] = '1=1';

            $merchantId && $where[] = "merchantId = " . $merchantId;

            $platformOrderNo && $where[] = "platformOrderNo = '" . $platformOrderNo . "'";
            $merchantOrderNo && $where[] = "merchantOrderNo = '" . $merchantOrderNo . "'";
            $channelMerchantNo && $where[] = "channelMerchantNo = '" . $channelMerchantNo . "'";

            $orderStatus && $where[] = "orderStatus='" . $orderStatus . "'";
            $bankCode && $where[] = "bankCode='" . $bankCode . "'";
            $channel && $where[] = "channel='" . $channel . "'";

            $bankAccountNo && $where[] = "bankAccountNo='" . Tools::encrypt($bankAccountNo) . "'";
            $bankAccountName && $where[] = "bankAccountName='" . $bankAccountName . "'";

            $beginTime && $where[] = "channelNoticeTime>='" . $beginTime . "'";
            $endTime && $where[] = "channelNoticeTime<='" . $endTime . "'";

            $createBeginTime && $where[] = "created_at>='" . $createBeginTime . "'";
            $createEndTime && $where[] = "created_at<='" . $createEndTime . "'";

            $minMoney && $where[] = "orderAmount>='" . $minMoney . "'";
            $maxMoney && $where[] = "orderAmount<='" . $maxMoney . "'";

            $whereStr = implode(' and ', $where);
            $stat = $model1->selectRaw("
        count(orderId) as number,
        sum(orderAmount) as orderAmount,
        (select sum(orderAmount) from platform_settlement_order where {$whereStr} and orderStatus = 'Exception') as exceptionAmount,
        (select count(orderId) from platform_settlement_order where {$whereStr} and orderStatus = 'Exception') as exceptionNumber,
        (select sum(orderAmount) from platform_settlement_order where {$whereStr} and orderStatus = 'Success') as successAmount,
        (select count(orderId) from platform_settlement_order where {$whereStr} and orderStatus = 'Success') as successNumber,
        (select sum(orderAmount) from platform_settlement_order where {$whereStr} and orderStatus = 'Fail') as failAmount,
        (select count(orderId) from platform_settlement_order where {$whereStr} and orderStatus = 'Fail') as failNumber,
        (select sum(orderAmount) from platform_settlement_order where {$whereStr} and orderStatus = 'Transfered') as transferedAmount,
        (select count(orderId) from platform_settlement_order where {$whereStr} and orderStatus = 'Transfered') as transferedNumber
        ")->first();

            if (!empty($stat)) {
                $stat = $stat->toArray();
            } else {
                $stat = array();
            }

            $stat['orderAmount'] = number_format($stat['orderAmount'] ?? 0, 2);
            $stat['exceptionAmount'] = number_format($stat['exceptionAmount'] ?? 0, 2);
            $stat['successAmount'] = number_format($stat['successAmount'] ?? 0, 2);
            $stat['failAmount'] = number_format($stat['failAmount'] ?? 0, 2);
            $stat['transferedAmount'] = number_format($stat['transferedAmount'] ?? 0, 2);
        }else {
            $data = $model->orderBy('orderId', 'desc')->get();
        }
        $rows = [];

        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
            : $merchant->getCacheByMerchantId($v->merchantId);
            $bankAccountNo = Tools::decrypt($v->bankAccountNo);
            $is_alipay = preg_match("/^(1[3-9]\d{9}|[\w\-\.]+@[\w\-]+\.[\w\-]+(\.[\w\-]+)?)$/", $bankAccountNo);
            $nv = [
                'bankAccountName' => $v->bankAccountName,
                'bankAccountNo' => $export ? '****' . substr($bankAccountNo,-4) : $bankAccountNo,
                'bankCode' => $v->bankCode,
                'bankCodeDesc' => $code['bankCode'][$v->bankCode] ?? '',
                'channel' => $v->channel,
                "channelDesc" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['name'] : null,
                "channelMerchantNo" => $v->channelMerchantNo,
                "channelNoticeTime" => Tools::getJSDatetime($v->channelNoticeTime),
                "createTime" => Tools::getJSDatetime($v->created_at),
                "merchantNo" => $v->merchantNo,
                "merchantOrderNo" => $v->merchantOrderNo,
                "orderAmount" => $v->realOrderAmount,
                "orderId" => Tools::getHashId($v->orderId),
                // "orderId" => $v->orderId,
                "orderReason" => $v->orderReason,
                "orderStatus" => $v->orderStatus,
                "orderStatusDesc" => $code['settlementOrderStatus'][$v->orderStatus] ?? null,
                "orderType" => $v->orderType,
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType] ?? null,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]['shortName'] : null,
                "serviceCharge" => $v->serviceCharge,
                "channelServiceCharge" => $v->channelServiceCharge,
                "callbackLimit" => $v->callbackLimit,
                "callbackSuccess" => $v->callbackSuccess,
                "agentFee" => $v->agentFee,
                "agentName" => $v->agentName,
                "isLock" => $v->isLock,
            ];
            $nv['noticeDate'] = date('Y-m-d H:i:s',strtotime($v->channelNoticeTime));
            $nv['createDate'] = date('Y-m-d H:i:s',strtotime($v->created_at));
            $rows[] = $nv;
        }
        if($export) {
            Tools::csv_export($rows, $this->exportSettlementHead(), 'settlementOrderList');
            die();
        }
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
            'stat' => $stat,
        ]);
    }

    public function detail(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/settlementorder/detail.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    public function getDetail(Request $request, Response $response, $args)
    {
        $orderId = $request->getParam('orderId');
        $orderId = Tools::getIdByHash($orderId);
        $model = new PlatformSettlementOrder();
        $cModel = new ChannelMerchant();
        $data = $model->where('orderId', $orderId)->first();
        $cData = $cModel->where('channelMerchantId', $data->channelMerchantId)->first();
        if(isset($cData['param'])) {
            $tmp = json_decode($cData['param'], true);
            if (!is_array($tmp)) {
                $tmp = json_decode(Tools::decrypt($cData['param']), true);
            }
        }
        if (empty($data)) {
            return $response->withJson([
                'result' => "数据不存在",
                'success' => 0,
            ]);
        }
        $bankAccountNo = Tools::decrypt($data->bankAccountNo);
        $is_alipay = preg_match("/^(1[3-9]\d{9}|[\w\-\.]+@[\w\-]+\.[\w\-]+(\.[\w\-]+)?)$/", $bankAccountNo);

        return $response->withJson([
            'result' => [
                "accountDate" => $data->accountDate,
                "applyIp" => $data->applyIp,
                "applyPerson" => $data->applyPerson,
                "applyTime" => Tools::getJSDatetime($data->created_at),
                "auditIp" => $data->auditIp,
                "auditPerson" => $data->auditPerson,
                "auditTime" => Tools::getJSDatetime($data->auditTime),
                "backNoticeUrl" => $data->backNoticeUrl,
                "bankAccountName" => $data->bankAccountName,
                'bankAccountNo' => $is_alipay ? $bankAccountNo :'***************'.substr($bankAccountNo,-4),
                'bankCode' => $data->bankCode,
                'bankCodeDesc' => $this->code['bankCode'][$data->bankCode] ?? null,
                'bankLineNo' => $data->bankLineNo,
                'bankName' => $data->bankName,
                "channel" => $data->channel,
                "channelDesc" => isset($this->code['channel'][$data->channel]) ? $this->code['channel'][$data->channel]['name'] : null,
                "channelMerchantId" => $data->channelMerchantId ? Tools::getHashId($data->channelMerchantId) : null,
                "channelMerchantNo" => $data->channelMerchantNo,
                "channelNoticeTime" => Tools::getJSDatetime($data->channelNoticeTime),
                "channelOrderNo" => $data->channelOrderNo,
                "channelSetId" => $data->channelSetId,
                "channelAccount" => $tmp['appAccount'] ?? '未知',
                "channelAccountStatus" => $cData['status'] ?? 'Deleted',
                'city' => $data->city,
                "createTime" => Tools::getJSDatetime($data->created_at),
                "failReason" => substr($data->failReason, 0, 255),
                // "holidayServiceCharge" => $data->holidayServiceCharge,
                // "holidaySettlementAmount" => $data->holidaySettlementAmount,
                "merchantId" => $data->merchantId,
                "merchantNo" => $data->merchantNo,
                "merchantOrderNo" => $data->merchantOrderNo,
                "merchantParam" => $data->merchantParam,
                "merchantReqTime" => Tools::getJSDatetime($data->merchantReqTime),
                "orderAmount" => $data->orderAmount,
                "realOrderAmount" => $data->realOrderAmount,
                "orderId" => Tools::getHashId($data->orderId),
                "orderReason" => $data->orderReason,
                "orderStatus" => $data->orderStatus,
                "orderStatusDesc" => $this->code['settlementOrderStatus'][$data->orderStatus] ?? null,
                "orderType" => $data->orderType,
                "platformOrderNo" => $data->platformOrderNo,
                "processType" => $data->processType,
                'province' => $data->province,
                "processTypeDesc" => $this->code['processType'][$data->processType] ?? null,
                "pushChannelTime" => Tools::getJSDatetime($data->pushChannelTime),
                // "settlementAccountType" => $data->settlementAccountType,
                // "settlementAccountTypeDesc" => $code['settlementAccountType'][$data->settlementAccountType] ?? null,
                // "t0ServiceCharge" => $data->t0ServiceCharge,
                // "t0SettlementAmount" => $data->t0SettlementAmount,
                // "t1ServiceCharge" => $data->t1ServiceCharge,
                // "t1SettlementAmount" => $data->t1SettlementAmount,
                "tradeSummary" => $data->tradeSummary,
                "transactionNo" => "",
                "userIp" => $data->userIp,
                "serviceCharge" => $data->serviceCharge,
                "channelServiceCharge" => $data->channelServiceCharge,
                "callbackLimit" => $data->callbackLimit,
                "callbackSuccess" => $data->callbackSuccess,
                "isLock" => $data->isLock,
            ],
            'success' => 1,
        ]);
    }

    public function notify(Request $request, Response $response, $args)
    {
        global $app;
        $orderId = $request->getParam('orderId');
        $orderId = Tools::getIdByHash($orderId);
        // $model = new PlatformSettlementOrder();
        // $data = $model->getCacheById();
        $order = PlatformSettlementOrder::where('orderId', $orderId)->first();
        $orderData = $order->toArray();
        $orderMsg = '';
        switch ($orderData['orderStatus']) {
            case 'WaitTransfer':
            case 'Transfered': $orderMsg = '代付中';break;
            case 'Success': $orderMsg = '代付成功';break;
            case 'Fail': $orderMsg = $orderData['failReason'];break;
        }
        $biz = [
            'merchantNo' => $orderData['merchantNo'],
            'merchantOrderNo' => $orderData['merchantOrderNo'],
            'platformOrderNo' => $orderData['platformOrderNo'],
            'orderStatus' => ($orderData['orderStatus'] == 'Exception' ? 'Transfered' : $orderData['orderStatus']),
            'orderAmount' => $orderData['orderAmount'],
            'orderMsg' => $orderMsg,
            'merchantParam' => $orderData['merchantParam'],
        ];
        $merchant = new Merchant;
        $merchantData = $merchant->getCacheByMerchantId($orderData['merchantId']);
        $sign = Tools::getSign($biz, $merchantData['signKey']);
        $reqData = [
            'code' => 'SUCCESS',
            'msg' => $app->getContainer()->code['status']['SUCCESS'],
            'sign' => $sign,
            'biz' => $biz,
        ];
        try {
            $req = Requests::post($orderData['backNoticeUrl'], ['Content-Type' => 'application/json'], json_encode($reqData), ['timeout' => 15,'verify' => false]);
            $this->logger->debug("代付补发通知", ['backNoticeUrl' => $orderData['backNoticeUrl'], 'reqData' => json_encode($reqData), 'rspCode' => $req->status_code, 'rspBody' => trim($req->body)]);

            if ($req->status_code == 200 && trim($req->body) == 'SUCCESS') {
                if ($order->callbackSuccess == false) {
                    $order->callbackSuccess = true;
                    $order->callbackLimit = $order->callbackLimit + 1;
                    $order->save();
                    (new PlatformSettlementOrder)->setCacheByPlatformOrderNo($order->platformOrderNo, $order->toArray());
                }
                return $response->withJson([
                    'result' => "通知成功",
                    'success' => 1,
                ]);
            } else {
                return $response->withJson([
                    'result' => "通知失败！notifyUrl:" . $orderData['backNoticeUrl'] . ', status_code:' . $req->status_code . ', resp_body:' . trim($req->body),
                    'success' => 0,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->debug("代付补发通知", ['backNoticeUrl' => $orderData['backNoticeUrl'], 'reqData' => json_encode($reqData)]);
            return $response->withJson([
                'result' => "通知失败！notifyUrl:" . $orderData['backNoticeUrl'] . ', exception:' . $e->getMessage(),
                'success' => 0,
            ]);
        }
    }

    public function perfect(Request $request, Response $response, $args)
{
    global $app;
    $redis = $app->getContainer()->redis;
    //审核密码验证
//        $tmp = $redis->get("checkPwd:check:count") ?? 0;
//        $checkPwd = Tools::getHashPassword($request->getParam('checkPwd'));
//        $checkPwd2 = SystemAccount::where('id',$_SESSION['accountId'])->value('check_pwd');
//        if($checkPwd2 == 'error'){
//            return $response->withJson([
//                'success' => 0,
//                'result' => "审核密码错误超过指定次数，已封审核权限，联系技术",
//            ]);
//        }
//        if( $checkPwd2 != $checkPwd){
//            $redis->setex("checkPwd:check:count", 7200, ++$tmp);
//            if($tmp > 5){
//                SystemAccount::where('id',$_SESSION['accountId'])->update(['check_pwd'=>'error']);
//            }
//            return $response->withJson([
//                'success' => 0,
//                'result' => "审核密码不正确",
//            ]);
//        }
//        $redis->setex("checkPwd:check:count", 7200, 0);

    $orderId = $request->getParam('orderId');
    $channelOrderNo = $request->getParam('channelOrderNo');
    $channelNoticeTime = $request->getParam('channelNoticeTime');
    $orderStatus = $request->getParam('orderStatus');
    $failReason = $request->getParam('failReason', '手动补单');
    $desc = $request->getParam('desc');
    $type = $request->getParam('type');
    $id = $request->getParam('id');
    $file_base64 = $request->getParam('file_base64');
    $file_base64 = preg_replace('/data:.*;base64,/i', '', $file_base64);
    if ($type == 'in') {
        $orderId = Tools::getIdByHash($orderId);
    }
    $file = $request->getUploadedFiles();
    $model = new PlatformSettlementOrder();
    $data = $model->where('orderId', $orderId)->first();
    /* var_dump($data);exit; */
    $code = $this->c->code;
    if (empty($data)) {
        return $response->withJson([
            'result' => "数据不存在",
            'success' => 0,
        ]);
    }

    if ($data->processType == 'Success') {
        return $response->withJson([
            'result' => "数据已处理",
            'success' => 0,
        ]);
    }

    if ($type == 'in') {
        //处理图片
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '图片不能为空',
                'success' => 0,
            ]);
        }
    }
    if (!empty($file['file'])) {
        if ($file['file']->getSize() > 2097152 || $file['file']->getSize() == 0) {
            return $response->withJson([
                'result' => '上传图片不能大于2M',
                'success' => 0,
            ]);
        }

        $arr = ['image/jpeg', 'image/png', 'image/jpg'];
        $img_type = $file['file']->getClientMediaType();
        if (empty($file['file']) && !in_array($img_type, $arr)) {
            return $response->withJson([
                'result' => '只能上传图片',
                'success' => 0,
            ]);
        }
    }

    $actionBeforeData = $data->toJson();
    // $data->channelOrderNo = $channelOrderNo;
    // $data->channelNoticeTime = $channelNoticeTime;
    // $data->processType = 'ManualOperation';
    // $data->orderStatus = $orderStatus;
    // $data->accountDate = date('Ymd', strtotime($channelNoticeTime));
    // $data->save();
    // $model->setCacheByPlatformOrderNo($data->platformOrderNo, $data->toArray());

    /* if ($orderStatus == 'Success') {
    $res = $model->success($data->toArray(), 0, 'ManualOperation', $channelOrderNo, $failReason, $channelNoticeTime, $_SESSION['userName']);
    } else {
    $res = $model->fail($data->toArray(), 'ManualOperation', $channelOrderNo, $failReason, $channelNoticeTime, $_SESSION['userName'],
    $channel = '', $channelMerchantId = '', $channelMerchantNo = '', $channelServiceCharge = 0, $limit = 1);
    }
    if ($res) {
    if (!empty($data->backNoticeUrl)) {
    (new SettlementNotifyExecutor)->push(0, $data->platformOrderNo);
    }
    $actionAfterData = $model->getCacheByPlatformOrderNo($data->platformOrderNo);
    SystemAccountActionLog::insert([
    [
    'action' => 'MANUAL_PLATFORMSETTLEMENTORDER',
    'actionBeforeData' => $actionBeforeData,
    'actionAfterData' => json_encode($actionAfterData),
    'status' => 'Success',
    'accountId' => $_SESSION['accountId'],
    'ip' => Tools::getIp(),
    'ipDesc' => Tools::getIpDesc(),
    ],
    ]);
    return $response->withJson([
    'result' => '补单成功',
    'success' => 1,
    ]);
    } else {
    return $response->withJson([
    'result' => '补单失败:' . $model->getErrorMessage(),
    'success' => 0,
    ]);
    } */

    $actionData = [
        'platformOrderNo' => $data['platformOrderNo'],
        'orderAmount' => $data['orderAmount'],
        'channel' => $data['channel'],
        'channelMerchantNo' => $data['channelMerchantNo'],
        'channelOrderNo' => $channelOrderNo,
        'channelNoticeTime' => $channelNoticeTime,
        'orderStatus' => $orderStatus,
        'orderId' => $data['orderId'],
        'desc' => $desc,
        'pic' => $file_base64,
        'failReason' => $failReason,
    ];

    if ($type == 'in') {
        SystemCheckLog::insert([
            [
                'admin_id' => 0,
                'commiter_id' => $_SESSION['accountId'],
                'status' => '0',
                'content' => json_encode($actionData),
                'relevance' => $data['platformOrderNo'],
                'desc' => '',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'type' => '代付补单',
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time()),
            ],
        ]);
    } else {
        $SystemCheckLog = new SystemCheckLog();
        $res = $SystemCheckLog->where('id', $id)->first();
        if ($res['status'] != 0) {
            return $response->withJson([
                'result' => "审核过的数据不能更改",
                'success' => 0,
            ]);
        }
        $content = json_decode($res['content'], true);
        if ($file_base64 == '' && isset($content['pic'])) {
            $actionData['pic'] = json_decode($res['content'], true)['pic'];
        }

        $SystemCheckLog->where('id', $id)->update([
            'content' => json_encode($actionData),
            'commiter_id' => $_SESSION['accountId'],
            'ip' => Tools::getIp(),
            'updated_at' => date('Y-m-d H:i:s', time()),
        ]);
    }

    $actionAfterData = $model->getCacheByPlatformOrderNo($data->platformOrderNo);
    SystemAccountActionLog::insert([
        [
            'action' => 'MANUAL_PLATFORMSETTLEMENTORDER',
            'actionBeforeData' => $actionBeforeData,
            'actionAfterData' => json_encode($actionAfterData),
            'status' => 'Success',
            'accountId' => $_SESSION['accountId'],
            'ip' => Tools::getIp(),
            'ipDesc' => Tools::getIpDesc(),
        ],
    ]);
    return $response->withJson([
        'result' => '请等待审核',
        'success' => 1,
    ]);

}

    public function lock(Request $request, Response $response, $args){
        global $app;
        $validator = $app->getContainer()->validator->validate($request, [
            'orderId' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
        ]);
        if (!$validator->isValid()) {
            return $response->withJson([
                'success' => 0,
                'result' => json_encode($validator->getErrors()),
            ]);
        }
        $params = $request->getParams();
        $orderId = Tools::getIdByHash($params['orderId']);
        $redis = $app->getContainer()->redis;
        $key = 'settlementLock:'.$orderId;
        if($redis->get($key)){
            return $response->withJson([
                'success' => 0,
                'result' => "操作频繁，请稍后再操作！",
            ]);
        }
        $redis->setex($key, 10, json_encode($params));
        $this->logger->debug("代付订单锁定-$orderId-{$_SESSION['userName']}");

        $model = new PlatformSettlementOrder();
        $db = $app->getContainer()->database;
        $res = false;
        try{
            $db->getConnection()->beginTransaction();
            $order = $model->where('orderId', $orderId)->lockForUpdate()->first();
            if (empty($order)) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据不存在",
                    'success' => 0,
                ]);
            }
            if ($order->orderStatus != 'Transfered') {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据已经处理,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            if ($order->isLock > 0 ) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "该订单已锁定,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            $order->isLock = 1;
            $order->lockUser = $_SESSION['userName'];
            $res = $order->save();
            $db->getConnection()->commit();
        }catch (\Exception $e){
            $db->getConnection()->rollback();
            $this->logger->debug("操作失败", ['error' => $e->getMessage()]);
            return $response->withJson([
                'success' => 0,
                'result' => "操作失败，请联系管理员！",
            ]);
        }
        if ($res != false) {
            return $response->withJson([
                'result' => '操作成功',
                'success' => 1,
            ]);
        }else{
            return $response->withJson([
                'result' => '操作失败:' . $model->getErrorMessage(),
                'success' => 0,
            ]);
        }
    }

    public function unlock(Request $request, Response $response, $args){
        global $app;
        $validator = $app->getContainer()->validator->validate($request, [
            'orderId' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
        ]);
        if (!$validator->isValid()) {
            return $response->withJson([
                'success' => 0,
                'result' => json_encode($validator->getErrors()),
            ]);
        }
        $params = $request->getParams();
        $orderId = Tools::getIdByHash($params['orderId']);
        $redis = $app->getContainer()->redis;
        $key = 'settlementLock:'.$orderId;
        if($redis->get($key)){
            return $response->withJson([
                'success' => 0,
                'result' => "操作频繁，请稍后再操作！",
            ]);
        }
        $redis->setex($key, 10, json_encode($params));
        $this->logger->debug("代付订单解除锁定-$orderId-{$_SESSION['userName']}");

        $model = new PlatformSettlementOrder();
        $db = $app->getContainer()->database;
        $res = false;
        try{
            $db->getConnection()->beginTransaction();
            $order = $model->where('orderId', $orderId)->lockForUpdate()->first();
            if (empty($order)) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据不存在",
                    'success' => 0,
                ]);
            }
            if ($order->orderStatus != 'Transfered') {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据已经处理,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            if ($order->isLock == 0  ) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "该订单已解锁,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            $order->isLock = 0;
            $order->lockUser = null;
            $res = $order->save();
            $db->getConnection()->commit();
        }catch (\Exception $e){
            $db->getConnection()->rollback();
            $this->logger->debug("操作失败", ['error' => $e->getMessage()]);
            return $response->withJson([
                'success' => 0,
                'result' => "操作失败，请联系管理员！",
            ]);
        }
        if ($res != false) {
            return $response->withJson([
                'result' => '操作成功',
                'success' => 1,
            ]);
        }else{
            return $response->withJson([
                'result' => '操作失败:' . $model->getErrorMessage(),
                'success' => 0,
            ]);
        }
    }

    public function offlineSettlement(Request $request, Response $response, $args)
    {
        global $app;
        $validator = $app->getContainer()->validator->validate($request, [
            'orderId' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'orderStatus' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
            'orderAmount' => Validator::floatVal()->noWhitespace()->notBlank(),
            'applyPerson' => Validator::stringType()->length(1, 50)->noWhitespace()->notBlank(),
        ]);
        if (!$validator->isValid()) {
            return $response->withJson([
                'success' => 0,
                'result' => json_encode($validator->getErrors()),
            ]);
        }
        $params = $request->getParams();
        $orderId = Tools::getIdByHash($params['orderId']);
        $redis = $app->getContainer()->redis;
        $key = 'offlineSettlement:'.$orderId;
        if($redis->get($key)){
            return $response->withJson([
                'success' => 0,
                'result' => "操作频繁，请稍后再操作！",
            ]);
        }
        $redis->setex($key, 30, json_encode($params));

        $channelOrderNo = $request->getParam('channelOrderNo','');
        $applyPerson = $request->getParam('applyPerson','');
        $channelServiceCharge = $request->getParam('channelServiceCharge',0);
        $model = new PlatformSettlementOrder();
        $db = $app->getContainer()->database;
        $res = false;
        try{
            $db->getConnection()->beginTransaction();
            $order = $model->where('orderId', $orderId)->lockForUpdate()->first();
            $actionBeforeData = $order->toArray();
            if (empty($order)) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据不存在",
                    'success' => 0,
                ]);
            }
            if ($order->orderStatus != 'Transfered') {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据已经处理,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }

            if ($order->isLock && $order->lockUser != $_SESSION['userName']) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据已被锁定,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            $orderStatus = $params['orderStatus'];
            $channelNoticeTime = date('Y-m-d H:i:s');
            if ($orderStatus == 'Success') {
                $res = $model->success($order->toArray(), 0, 'ManualOperation', $channelOrderNo, $applyPerson, $channelNoticeTime, $_SESSION['userName'],$channelServiceCharge);
            } else {
                $res = $model->fail($order->toArray(), 'ManualOperation', $channelOrderNo, $applyPerson, $channelNoticeTime, $_SESSION['userName'],
                    $channel = '', $channelMerchantId = '', $channelMerchantNo = '', $channelServiceCharge = 0);
            }
            $db->getConnection()->commit();
        }catch (\Exception $e){
            $db->getConnection()->rollback();
            $this->logger->debug("手动代付操作失败", ['error' => $e->getMessage()]);
            return $response->withJson([
                'success' => 0,
                'result' => "操作失败，请联系管理员！",
            ]);
        }
        if ($res) {
            (new SettlementNotifyExecutor)->push(0, $order->platformOrderNo);
            $actionAfterData = $model->getCacheByPlatformOrderNo($order->platformOrderNo);
            SystemAccountActionLog::insert([
                [
                    'action' => 'MANUAL_PLATFORMSETTLEMENTORDER',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($actionAfterData),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
            ]);
            return $response->withJson([
                'result' => '操作成功',
                'success' => 1,
            ]);
        }else{
            return $response->withJson([
                'result' => '操作失败:' . $model->getErrorMessage(),
                'success' => 0,
            ]);
        }


    }

    public function systemSettlement(Request $request, Response $response, $args){

        $orderId = $args['orderId'];
        $orderId = Tools::getIdByHash($orderId);
        $redis =  $this->c->redis;
        $key = 'offlineSettlement:'.$orderId;
        if($redis->get($key)){
            return $response->withJson([
                'success' => 0,
                'result' => "操作频繁，请稍后再操作！",
            ]);
        }
        $redis->setex($key, 30, $orderId);
        $model = new PlatformSettlementOrder();
        try{
            $db = $this->c->database;
            $order = $model->where('orderId', $orderId)->lockForUpdate()->first();
            if (empty($order)) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据不存在",
                    'success' => 0,
                ]);
            }
            if ($order->orderType != 'manualSettlement' || $order->orderStatus != 'Transfered') {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据已经处理,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            if ($order->isLock && $order->lockUser != $_SESSION['userName']) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => "数据已被锁定,请刷新页面查看最新订单状态",
                    'success' => 0,
                ]);
            }
            $order->orderType = 'SettlementOrder';
            $order->auditPerson = $_SESSION['userName'];
            $order->auditIp = Tools::getIp();
            $order->auditTime = date('YmdHis');
            $order->isLock = 0;
            $order->lockUser = null;
            $order->save();
            $db->getConnection()->commit();
            (new SettlementFetchExecutor)->push(0, $order->platformOrderNo);

        }catch (\Exception $e){
            $db->getConnection()->rollback();
            $this->logger->debug("推送系统代付失败", ['error' => $e->getMessage()]);
            return $response->withJson([
                'success' => 0,
                'result' => "操作失败，请联系管理员！",
            ]);
        }

        return $response->withJson([
            'result' => '操作成功',
            'success' => 1,
        ]);
    }

    public function makeUpcheck(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/settlementorder/makeupcheck.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    public function getMakeUp(Request $request, Response $response, $args)
    {
        $id = $request->getParam('id');
        $model = new SystemCheckLog();
        $account = new SystemAccount();
        $admin = $account::all();
        $list = [];
        foreach ($admin as $k => $v) {
            $list[$v->id] = $v->userName;
        }

        $data = $model->where('id', $id)->first();
        $code = $this->c->code;
        if (empty($data)) {
            return $response->withJson([
                'result' => "数据不存在",
                'success' => 0,
            ]);
        }
        $res = json_decode($data->content, true);
        return $response->withJson([
            'result' => [
                "id" => $data->id,
                "commiter_id" => isset($list[$data['commiter_id']]) ? $list[$data['commiter_id']] : '',
                "platformOrderNo" => $res['platformOrderNo'],
                "orderAmount" => $res['orderAmount'],
                "channel" => $res['channel'],
                "channelMerchantNo" => $res['channelMerchantNo'],
                "channelOrderNo" => $res['channelOrderNo'],
                "channelNoticeTime" => Tools::getJSDatetime($res['channelNoticeTime']),
                "orderStatus" => $code['settlementOrderStatus'][$res['orderStatus']],
                "commiter_desc" => isset($res['desc']) ? $res['desc'] : '',
                "ip" => $data->ip,
                "created_at" => Tools::getJSDatetime($data->created_at),
                "admin_id" => isset($list[$data['admin_id']]) ? $list[$data['admin_id']] : '',
                "check_ip" => $data->check_ip,
                "check_time" => Tools::getJSDatetime($data->check_time),
                "desc" => $data->desc,
                'status' => $code['checkStatusCode'][$data->status],
                'pic' => $res['pic'],
                'failReason' => $res['failReason'],
            ],
            'success' => 1,
        ]);

    }

    public function domakeUpCheck(Request $request, Response $response)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        //审核密码验证
        $tmp = $redis->get("checkPwd:check:count") ?? 0;
        $checkPwd = Tools::getHashPassword($request->getParam('checkPwd'));
        $checkPwd2 = SystemAccount::where('id',$_SESSION['accountId'])->value('check_pwd');
        if($checkPwd2 == 'error'){
            return $response->withJson([
                'success' => 0,
                'result' => "审核密码错误超过指定次数，已封审核权限，联系技术",
            ]);
        }
        if( $checkPwd2 != $checkPwd){
            $redis->setex("checkPwd:check:count", 7200, ++$tmp);
            if($tmp > 5){
                SystemAccount::where('id',$_SESSION['accountId'])->update(['check_pwd'=>'error']);
            }
            return $response->withJson([
                'success' => 0,
                'result' => "审核密码不正确",
            ]);
        }
        $redis->setex("checkPwd:check:count", 7200, 0);

        $desc = $request->getParam('desc');
        $id = $request->getParam('id');
        $status = $request->getParam('status');

        $model = new SystemCheckLog();
        $account = new SystemAccount();

        $data = $model->where('id', $id)->first();
        if (empty($data)) {
            return $response->withJson([
                'result' => "数据不存在",
                'success' => 0,
            ]);
        }

        if ($data->status != 0) {
            return $response->withJson([
                'result' => "数据已经处理",
                'success' => 0,
            ]);
        }

        $content = json_decode($data->content, true);
        $orderId = $content['orderId'];
        $channelOrderNo = $content['channelOrderNo'];
        $channelNoticeTime = $content['channelNoticeTime'];
        $failReason = $content['failReason'];
        $orderStatus = $content['orderStatus'];
        $arr = [
            'desc' => $desc,
            'status' => $status,
            'admin_id' => $_SESSION['accountId'],
            'check_time' => date('Y-m-d H:i:s', time()),
            'check_ip' => Tools::getIp(),
        ];

        if ($status == 1) {
            /* $content['orderStatus'] = 'Success'; */
            $content = json_encode($content);
            $arr['content'] = $content;
        }
        $model->where('id', $id)->update($arr);

        if ($status == 1) {
            $PlatformSettlementOrder = new PlatformSettlementOrder();
            $paySettlementOrder = $PlatformSettlementOrder->where('orderId', $orderId)->first();
            $actionBeforeData = $paySettlementOrder->toArray();
            if ($orderStatus == 'Success') {
                $res = $PlatformSettlementOrder->success($paySettlementOrder->toArray(), 0, 'ManualOperation', $channelOrderNo, $failReason, $channelNoticeTime, $_SESSION['userName']);
            } else {
                $res = $PlatformSettlementOrder->fail($paySettlementOrder->toArray(), 'ManualOperation', $channelOrderNo, $failReason, $channelNoticeTime, $_SESSION['userName'],
                    $channel = '', $channelMerchantId = '', $channelMerchantNo = '', $channelServiceCharge = 0, $limit = 1);
            }
            if ($res) {
                if (!empty($paySettlementOrder->backNoticeUrl)) {
                    (new SettlementNotifyExecutor)->push(0, $paySettlementOrder->platformOrderNo);
                }
                $actionAfterData = $PlatformSettlementOrder->getCacheByPlatformOrderNo($paySettlementOrder->platformOrderNo);
                SystemAccountActionLog::insert([
                    [
                        'action' => 'MANUAL_PLATFORMSETTLEMENTORDER',
                        'actionBeforeData' => $actionBeforeData,
                        'actionAfterData' => json_encode($actionAfterData),
                        'status' => 'Success',
                        'accountId' => $_SESSION['accountId'],
                        'ip' => Tools::getIp(),
                        'ipDesc' => Tools::getIpDesc(),
                    ],
                ]);
                return $response->withJson([
                    'result' => '补单成功',
                    'success' => 1,
                ]);
            } else {
                return $response->withJson([
                    'result' => '补单失败:' . $PlatformSettlementOrder->getErrorMessage(),
                    'success' => 0,
                ]);
            }
        } else {
            return $response->withJson([
                'result' => '审核成功',
                'success' => 1,
            ]);
        }
    }

    public function getMakeUpList(Request $request, Response $response)
    {
        $platformOrderNo = $request->getParam('platformOrderNo');
        $SystemCheckLog = new SystemCheckLog();
        $code = $this->c->code;

        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $platformOrderNo && $SystemCheckLog = $SystemCheckLog->where('relevance', $platformOrderNo);
        $total = $SystemCheckLog->count();
        $data = $SystemCheckLog->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $content = json_decode($v->content, true);
            $nv = [
                'channelOrderNo' => $content['channelOrderNo'],
                'channelNoticeTime' => Tools::getJSDatetime($content['channelNoticeTime']),
                'commiter_desc' => isset($content['desc']) ? $content['desc'] : '',
                'desc' => $v->desc,
                'created_at' => Tools::getJSDatetime($v->created_at),
                'check_time' => Tools::getJSDatetime($v->check_time),
                'status' => $code['checkStatusCode'][$v->status],
            ];
            $rows[] = $nv;
        }
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    public function timeInterval(Request $request, Response $response, $args) {
        $redis = $this->c->redis;
        $key = $request->getParam('cacheKey');
        $keys =  explode(',',$key);
        $res = [];
        foreach ($keys as $k){
            $res[$k] = intval($redis->get($k));
        }
        return $response->withJson(array_merge([
            'result' => [],
            'rows' => [],
            'success' => 1,
        ],$res));
    }

    public function postForm($url, $data, $headers = array(), $referer = NULL) {
        $headerArr = array();
        if (is_array($headers)) {
            foreach ($headers as $k => $v) {
                $headerArr[] = $k.': '.$v;
            }
        }
        $headerArr[] = 'Content-Type: application/x-www-form-urlencoded; charset=utf-8';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, "http://{$referer}/");
        }
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
