<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\Merchant;
use App\Models\MerchantRate;
use App\Models\PlatformPayOrder;
// use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;

class PayOrderController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/payorder.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }
    private function exportPayOrderHead(){
        return [
            'platformOrderNo' => '平台订单号',
            'merchantOrderNo' => '商户订单号',
            'orderAmount' => '订单金额',
            'serviceCharge' => '手续费',
            'payTypeDesc' => '支付方式',
            'orderStatusDesc' => '订单状态',
            'createDate' => '订单生成时间',
            'noticeDate' => '处理时间',
        ];
    }

    public function search(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new PlatformPayOrder();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        // $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $orderStatus = $request->getParam('orderStatus');
        $beginTime = $request->getParam('beginTime',date('Y-m-d'));
        $endTime = $request->getParam('endTime',date('Y-m-d').' 23:59:59');
        $offset = $request->getParam('offset');
        $channel = $request->getParam('channel');
        $payType = $request->getParam('payType');
        $export = $request->getParam('export');
        $merchantId = 0;

        if ($channelMerchantNo) {
            $channelMerchantData = (new ChannelMerchant())->getCacheByChannelMerchantNo($channelMerchantNo);
            $channelMerchantId = !empty($channelMerchantData) ? $channelMerchantData['channelMerchantId'] : 0;
        } else {
            $channelMerchantId = 0;
        }
        // $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        // $merchantData && $merchantId = $merchantData['merchantId'];
        // $merchantId && $model->where('merchantId', $merchantId);
        $model = $model->where('merchantId', $_SESSION['merchantId']);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $orderStatus && $model = $model->where('orderStatus', $orderStatus);
        $channel && $model = $model->where('channel', $channel);
        $payType && $model = $model->where('payType', $payType);
        $channelMerchantNo && $model = $model->where('channelMerchantId', $channelMerchantId);
        if(!$export) {
            $model1 = clone $model;
            $total = $model->count();
            $data = $model->orderBy('orderId', 'desc')->offset($offset)->limit($limit)->get();
        }else {
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
                "orderAmount" => $v->realOrderAmount,
                "orderId" => Tools::getHashId($v->orderId),
                "orderStatus" => $v->orderStatus,
                "orderStatusDesc" => $code['payOrderStatus'][$v->orderStatus],
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType],
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => $merchantData[$v->merchantId]['shortName'],
                "serviceCharge" => $v->serviceCharge,
            ];
            $nv['noticeDate'] = date('Y-m-d H:i:s',strtotime($v->channelNoticeTime));
            $nv['createDate'] = date('Y-m-d H:i:s',strtotime($v->created_at));
            $rows[] = $nv;
        }
        if($export) {
            Tools::csv_export($rows, $this->exportPayOrderHead(), 'settlementOrderList');
            die();
        }
        $where = [];
        $where[] = '1=1';
        $merchantId = $_SESSION['merchantId'];
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
        (select sum(serviceCharge) from platform_pay_order where {$whereStr} and orderStatus = 'Success') as serviceCharge,
        (select count(orderId) from platform_pay_order where {$whereStr} and orderStatus = 'Expired') as expiredNumber
        ")->first();


        if (!empty($stat)) {
            $stat = $stat->toArray();
        } else {
            $stat = array();
        }


        $rateModeel = new MerchantRate();
        $rateArr = $rateModeel->where('merchantId',$_SESSION['merchantId'])->where('productType','Pay')->get()->toArray();

        $stat['orderAmount'] = number_format($stat['orderAmount'] ?? 0, 2);
        $stat['waitPaymentAmount'] = number_format($stat['waitPaymentAmount'] ?? 0, 2);
        $stat['successAmount'] = number_format($stat['successAmount'] ?? 0, 2);
        $stat['expiredAmount'] = number_format($stat['expiredAmount'] ?? 0, 2);
        $stat['serviceCharge'] = number_format($stat['serviceCharge'] ?? 0, 2);

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
            'stat' => $stat,
            'rateArr' => $rateArr,
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
            $req = Requests::post($orderData['backNoticeUrl'], ['Content-Type' => 'application/json'], json_encode($reqData), ['timeout' => 15]);
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

}
