<?php

namespace App\Controllers\GM;

// use App\Controllers\Controller;
use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\Merchant;
use App\Models\PlatformPayOrder;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use App\Models\SystemCheckLog;
use App\Queues\PayNotifyExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;

class PayOrderController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/payorder/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    private function exportHead(){
        return [
            'merchantNo' => '商户号',
            'shortName' => '商户简称',
            'platformOrderNo' => '平台订单号',
            'merchantOrderNo' => '商户订单号',
            'orderAmount' => '订单金额',
            'realOrderAmount' => '实际支付金额',
            'serviceCharge' => '平台手续费',
            'channelServiceCharge' => '上游手续费',
            'agentFee' => '代理手续费',
            'channelDesc' => '支付渠道',
            'payTypeDesc' => '支付方式',
            'orderStatusDesc' => '订单状态',
            'createDate' => '订单生成时间',
            'noticeDate' => '订单支付时间',
            'callback' => '回调',
        ];
    }

    public function search(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new PlatformPayOrder();
        $model1 = new PlatformPayOrder();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $orderStatus = $request->getParam('orderStatus');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $offset = $request->getParam('offset');
        $channel = $request->getParam('channel');
        $payType = $request->getParam('payType');
        $export = $request->getParam('export');
        $agentName = $request->getParam('agentName');
        $merchantId = 0;

        if ($channelMerchantNo) {
            $channelMerchantData = (new ChannelMerchant)->getCacheByChannelMerchantNo($channelMerchantNo);
            $channelMerchantId = !empty($channelMerchantData) ? $channelMerchantData['channelMerchantId'] : 0;
        } else {
            $channelMerchantId = 0;
        }

        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $merchantNo && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $orderStatus && $model = $model->where('orderStatus', $orderStatus);
        $channel && $model = $model->where('channel', $channel);
        $payType && $model = $model->where('payType', $payType);
        $channelMerchantNo && $model = $model->where('channelMerchantId', $channelMerchantId);
        $agentName && $model = $model->where('agentName', $agentName);

        if(!$export) {
            $model1 = clone $model;
            $total = $model->count();
            $data = $model->orderBy('orderId', 'desc')->offset($offset)->limit($limit)->get();

            $where = [];
            $where[] = '1=1';
            $value = [];
            $merchantId && $where[] = "merchantId = " . $merchantId;
            $platformOrderNo && $where[] = "platformOrderNo = '" . $platformOrderNo . "'";
            $merchantOrderNo && $where[] = "merchantOrderNo = '" . $merchantOrderNo . "'";

            $orderStatus && $where[] = "orderStatus='" . $orderStatus . "'";
            $channel && $where[] = "channel='" . $channel . "'";

            $payType && $where[] = "payType='" . $payType . "'";
            $channelMerchantId && $where[] = "channelMerchantId='" . $channelMerchantId . "'";

            $beginTime && $where[] = "created_at>='" . $beginTime . "'";
            $endTime && $where[] = "created_at<='" . $endTime . "'";

            $whereStr = implode(' and ', $where);
            $stat = $model1->selectRaw("
            count(orderId) as number,
            sum(orderAmount) as orderAmount,
            (select sum(orderAmount) from platform_pay_order where {$whereStr} and orderStatus = 'WaitPayment') as waitPaymentAmount,
            (select count(orderId) from platform_pay_order where {$whereStr} and orderStatus = 'WaitPayment') as waitPaymentNumber,
            (select sum(orderAmount) from platform_pay_order where {$whereStr} and orderStatus = 'Success') as successAmount,
            (select count(orderId) from platform_pay_order where {$whereStr} and orderStatus = 'Success') as successNumber,
            (select sum(orderAmount) from platform_pay_order where {$whereStr} and orderStatus = 'Expired') as expiredAmount,
            (select count(orderId) from platform_pay_order where {$whereStr} and orderStatus = 'Expired') as expiredNumber
            ")->first();

            if (!empty($stat)) {
                $stat = $stat->toArray();
            } else {
                $stat = array();
            }
            $stat['orderAmount'] = number_format($stat['orderAmount'] ?? 0, 2);
            $stat['waitPaymentAmount'] = number_format($stat['waitPaymentAmount'] ?? 0, 2);
            $stat['successAmount'] = number_format($stat['successAmount'] ?? 0, 2);
            $stat['expiredAmount'] = number_format($stat['expiredAmount'] ?? 0, 2);
        }else{
            $data = $model->orderBy('orderId', 'desc')->get();
        }
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
            : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [
                'channel' => $v->channel,
                "channelDesc" => $code['channel'][$v->channel]['name'],
                "channelMerchantNo" => $v->channelMerchantNo,
                "channelNoticeTime" => Tools::getJSDatetime($v->channelNoticeTime),
                "createTime" => Tools::getJSDatetime($v->created_at),
                "merchantNo" => $v->merchantNo,
                "merchantOrderNo" => $v->merchantOrderNo,
                "orderAmount" => $v->orderAmount,
                "realOrderAmount" => $v->realOrderAmount,
                "orderId" => Tools::getHashId($v->orderId),
                "orderStatus" => $v->orderStatus,
                "orderStatusDesc" => $code['payOrderStatus'][$v->orderStatus],
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType],
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => !empty($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]['shortName'] : '',
                "serviceCharge" => $v->serviceCharge,
                "channelServiceCharge" => $v->channelServiceCharge,
                "callbackLimit" => $v->callbackLimit,
                "callbackSuccess" => $v->callbackSuccess,
                "agentFee" => $v->agentFee,
                "agentName" => $v->agentName,
            ];
            $nv['callback'] = $v->callbackSuccess ? '成功' : '失败';
            $nv['callback'] = $nv['callback'] .  "({$v->callbackLimit})";
            $nv['noticeDate'] = date('Y-m-d H:i:s',strtotime($v->channelNoticeTime));
            $nv['createDate'] = date('Y-m-d H:i:s',strtotime($v->created_at));
            $rows[] = $nv;
        }
        if($export) {

            Tools::csv_export($rows, $this->exportHead(), 'payOrderList');
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
        return $this->c->view->render($response, 'gm/payorder/detail.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    public function getDetail(Request $request, Response $response, $args)
    {
        $orderId = $request->getParam('orderId');
        $orderId = Tools::getIdByHash($orderId);
        $model = new PlatformPayOrder();
        $data = $model->where('orderId', $orderId)->first();
        $code = $this->c->code;
        if (empty($data)) {
            return $response->withJson([
                'result' => "数据不存在",
                'success' => 0,
            ]);
        }

        return $response->withJson([
            'result' => [
                "accountDate" => $data->accountDate,
                "backNoticeUrl" => $data->backNoticeUrl,
                "bankCode" => $data->bankCode,
                "bankCodeDesc" => $code['bankCode'][$data->bankCode] ?? null,
                "cardHolderMobile" => Tools::decrypt($data->cardHolderMobile),
                "cardHolderName" => $data->cardHolderName,
                "cardNum" => Tools::decrypt($data->cardNum),
                "cardType" => $data->cardType,
                "cardTypeDesc" => $code['cardType'][$data->cardType] ?? null,
                "channel" => $data->channel,
                "channelDesc" => $code['channel'][$data->channel]['name'],
                "channelMerchantId" => Tools::getHashId($data->channelMerchantId),
                "channelMerchantNo" => $data->channelMerchantNo,
                "channelNoticeTime" => Tools::getJSDatetime($data->channelNoticeTime),
                "channelOrderNo" => $data->channelOrderNo,
                "channelSetId" => $data->channelSetId,
                "createTime" => Tools::getJSDatetime($data->created_at),
                "frontNoticeUrl" => $data->frontNoticeUrl,
                "idNum" => Tools::decrypt($data->idNum),
                "merchantId" => Tools::getHashId($data->merchantId),
                "merchantNo" => $data->merchantNo,
                "merchantOrderNo" => $data->merchantOrderNo,
                "merchantParam" => $data->merchantParam,
                "merchantReqTime" => Tools::getJSDatetime($data->merchantReqTime),
                "orderAmount" => $data->orderAmount,
                "realOrderAmount" => $data->realOrderAmount,
                "orderId" => Tools::getHashId($data->orderId),
                "orderStatus" => $data->orderStatus,
                "orderStatusDesc" => $code['payOrderStatus'][$data->orderStatus] ?? null,
                "orderType" => $data->orderType,
                "payModel" => $data->payModel,
                "payModelDesc" => $code['payModel'][$data->payModel] ?? null,
                "payType" => $data->payType,
                "payTypeDesc" => $code['payType'][$data->payType] ?? null,
                "platformOrderNo" => $data->platformOrderNo,
                "processType" => $data->processType,
                "processTypeDesc" => $code['processType'][$data->processType] ?? null,
                "pushChannelTime" => Tools::getJSDatetime($data->pushChannelTime),
                "serviceCharge" => $data->serviceCharge,
                "channelServiceCharge" => $data->channelServiceCharge,
                "callbackLimit" => $data->callbackLimit,
                "callbackSuccess" => $data->callbackSuccess,
                "thirdUserId" => $data->thirdUserId,
                "timeoutTime" => Tools::getJSDatetime($data->timeoutTime),
                "tradeSummary" => $data->tradeSummary,
                "transactionNo" => "",
                "userIp" => $data->userIp,
                "userTerminal" => $data->userTerminal,
            ],
            'success' => 1,
        ]);
    }

    public function perfect(Request $request, Response $response, $args)
    {
        //审核密码验证
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
        $desc = $request->getParam('desc');
        $type = $request->getParam('type');
        $id = $request->getParam('id');
        $file_base64 = $request->getParam('file_base64');
        $file_base64 = preg_replace('/data:.*;base64,/i', '', $file_base64);
        if ($type == 'in') {
            $orderId = Tools::getIdByHash($orderId);
        }
        $file = $request->getUploadedFiles();

        $model = new PlatformPayOrder();
        $data = $model->where('orderId', $orderId)->first();
        /* $code = $this->c->code; */

        if (empty($data)) {
            return $response->withJson([
                'result' => "数据不存在",
                'success' => 0,
            ]);
        }

        if ($data['processType'] == 'Success') {
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
        // $data->orderStatus = 'Success';
        // $data->accountDate = date('Ymd', strtotime($channelNoticeTime));
        // $data->save();
        // $model->setCacheByPlatformOrderNo($data->platformOrderNo, $data->toArray());
        /* $res = $model->success($data->toArray(), 0, 'ManualOperation', $channelOrderNo, $channelNoticeTime); */
        /* $type = 'payOrderSupplement'; */

        $actionData = [
            'platformOrderNo' => $data['platformOrderNo'],
            'orderAmount' => $data['orderAmount'],
            'channel' => $data['channel'],
            'channelMerchantNo' => $data['channelMerchantNo'],
            'channelOrderNo' => $channelOrderNo,
            'channelNoticeTime' => $channelNoticeTime,
            'orderStatus' => $data['orderStatus'],
            'orderId' => $data['orderId'],
            'desc' => $desc,
            'pic' => $file_base64,
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
                    'type' => '支付补单',
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

        /* if (!empty($data['backNoticeUrl'])) {
        (new PayNotifyExecutor)->push(0, $data->platformOrderNo);
        } */
        $actionAfterData = $model->getCacheByPlatformOrderNo($data->platformOrderNo);
        SystemAccountActionLog::insert([
            [
                'action' => 'MANUAL_PLATFORMPAYORDER',
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

    public function notify(Request $request, Response $response, $args)
    {
        global $app;
        $orderId = $request->getParam('orderId');
        $orderId = Tools::getIdByHash($orderId);
        // $model = new PlatformPayOrder();
        // $data = $model->getCacheById();
        $order = PlatformPayOrder::where('orderId', $orderId)->first();
        $orderData = $order->toArray();
        $biz = [
            'merchantNo' => $orderData['merchantNo'],
            'merchantOrderNo' => $orderData['merchantOrderNo'],
            'platformOrderNo' => $orderData['platformOrderNo'],
            'orderStatus' => $orderData['orderStatus'],
            'orderAmount' => $orderData['orderAmount'],
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
            $this->logger->debug("支付补发通知", ['backNoticeUrl' => $orderData['backNoticeUrl'], 'reqData' => json_encode($reqData), 'rspCode' => $req->status_code, 'rspBody' => trim($req->body)]);

            if ($req->status_code == 200 && trim($req->body) == 'SUCCESS') {

                if ($order->callbackSuccess == false) {
                    $order->callbackSuccess = true;
                    $order->callbackLimit = $order->callbackLimit + 1;
                    $order->save();
                    (new PlatformPayOrder)->setCacheByPlatformOrderNo($order->platformOrderNo, $order->toArray());
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
            return $response->withJson([
                'result' => "通知失败！notifyUrl:" . $orderData['backNoticeUrl'] . ', exception:' . $e->getMessage(),
                'success' => 0,
            ]);
        }
    }

    public function makeUpcheck(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/payorder/makeupcheck.twig', [
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
                "orderStatus" => $code['payOrderStatus'][$res['orderStatus']],
                "commiter_desc" => isset($res['desc']) ? $res['desc'] : '',
                "ip" => $data->ip,
                "created_at" => Tools::getJSDatetime($data->created_at),
                "admin_id" => isset($list[$data['admin_id']]) ? $list[$data['admin_id']] : '',
                "check_ip" => $data->check_ip,
                "check_time" => Tools::getJSDatetime($data->check_time),
                "desc" => $data->desc,
                'status' => $code['checkStatusCode'][$data->status],
                'pic' => $res['pic'],
            ],
            'success' => 1,
        ]);

    }

    public function domakeUpCheck(Request $request, Response $response)
    {
        //审核密码验证
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
        /* dump($data);exit; */
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
        $arr = [
            'desc' => $desc,
            'status' => $status,
            'admin_id' => $_SESSION['accountId'],
            'check_time' => date('Y-m-d H:i:s', time()),
            'check_ip' => Tools::getIp(),
        ];

        if ($status == 1) {
            $content['orderStatus'] = 'Success';
            $content = json_encode($content);
            $arr['content'] = $content;
        }
        $model->where('id', $id)->update($arr);

        if ($status == 1) {
            $PlatformPayOrder = new PlatformPayOrder();
            $payOrder = $PlatformPayOrder->where('orderId', $orderId)->first();
            $res = $PlatformPayOrder->success($payOrder->toArray(), 0, 'ManualOperation', $channelOrderNo, $channelNoticeTime);
            if ($res) {
                $actionAfterData = $PlatformPayOrder->getCacheByPlatformOrderNo($payOrder->platformOrderNo);

                if (!empty($payOrder['backNoticeUrl'])) {
                    (new PayNotifyExecutor)->push(0, $payOrder->platformOrderNo);
                }
                SystemAccountActionLog::insert([
                    [
                        'action' => 'MANUAL_PLATFORMPAYORDER',
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
                    'result' => '补单失败:' . $PlatformPayOrder->getErrorMessage(),
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

}
