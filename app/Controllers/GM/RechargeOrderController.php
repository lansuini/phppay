<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/8/8
 * Time: 23:26
 */

namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\Merchant;
use App\Models\MerchantChannel;
use App\Models\PlatformSettlementOrder;
use App\Models\PlatformRechargeOrder;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use App\Models\SystemCheckLog;
use App\Queues\SettlementActiveQueryExecutor;
use App\Queues\SettlementNotifyExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Requests;
class RechargeOrderController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/rechargeorder/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'],
            'menus' => $this->menus,
        ]);
    }

    private function exportRechargeHead(){
        return [
            'merchantNo' => '商户号',
            'shortName' => '商户简称',
            'platformOrderNo' => '平台订单号',
            'orderAmount' => '订单金额',
            'serviceCharge' => '平台手续费',
            'channelServiceCharge' => '上游手续费',
            'agentFee' => '代理手续费',
            'orderReason' => '用途',
            'orderStatusDesc' => '订单状态',
            'payTypeDesc' => '充值方式',
            'bankAccountNo' => '收款卡号',
            'createDate' => '订单生成时间',
            'noticeDate' => '处理时间',
        ];
    }
    public function rechargeOrderSearch(Request $request, Response $response, $args)
    {

        $merchant = new Merchant();
        $model = new PlatformRechargeOrder();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $orderStatus = $request->getParam('orderStatus');
        $orderPayType = $request->getParam('orderPayType');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');

        $createBeginTime = $request->getParam('createBeginTime');
        $createEndTime = $request->getParam('createEndTime');
        $agentName = $request->getParam('agentName');

        $channel = $request->getParam('channel');
        $export = $request->getParam('export');
        $channelMerchantNo= $request->getParam('channelMerchantNo');
        $merchantId = 0;

        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $merchantNo && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $beginTime && $model = $model->where('channelNoticeTime', '>=', $beginTime);
        $endTime && $model = $model->where('channelNoticeTime', '<=', $endTime);

        $createBeginTime && $model = $model->where('created_at', '>=', $createBeginTime);
        $createEndTime && $model = $model->where('created_at', '<=', $createEndTime);

        $orderStatus && $model = $model->where('orderStatus', $orderStatus);
        $orderPayType && $model = $model->where('payType', $orderPayType);
        $channel && $model = $model->where('channel', $channel);
        $channelMerchantNo && $model = $model->where('channelMerchantNo', $channelMerchantNo);
        $agentName && $model = $model->where('agentName', $agentName);
        if(!$export) {
            $model1 = clone $model;
            $total = $model->count();
            $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();

            $where = [];
            $where[] = '1=1';

            $merchantId && $where[] = "merchantId = " . $merchantId;

            $platformOrderNo && $where[] = "platformOrderNo = '" . $platformOrderNo . "'";

            $orderStatus && $where[] = "orderStatus='" . $orderStatus . "'";

            $orderPayType && $where[] = "payType='" . $orderPayType . "'";
            $channel && $where[] = "channel='" . $channel . "'";
            $channelMerchantNo && $where[] = "channelMerchantNo='" . $channelMerchantNo . "'";

            $beginTime && $where[] = "channelNoticeTime>='" . $beginTime . "'";
            $endTime && $where[] = "channelNoticeTime<='" . $endTime . "'";

            $createBeginTime && $where[] = "created_at>='" . $createBeginTime . "'";
            $createEndTime && $where[] = "created_at<='" . $createEndTime . "'";

            $agentName && $where[] = "agentName='" . $agentName . "'";

            $whereStr = implode(' and ', $where);
            $stat = $model1->selectRaw("
        count(id) as number,
        sum(orderAmount) as orderAmount,
        sum(serviceCharge) as serviceCharge,
        sum(channelServiceCharge) as channelServiceCharge,
        (select sum(orderAmount) from platform_recharge_order where {$whereStr} and orderStatus = 'Exception') as exceptionAmount,
        (select count(id) from platform_recharge_order where {$whereStr} and orderStatus = 'Exception') as exceptionNumber,
        (select sum(orderAmount) from platform_recharge_order where {$whereStr} and orderStatus = 'Success') as successAmount,
        (select count(id) from platform_recharge_order where {$whereStr} and orderStatus = 'Success') as successNumber,
        (select sum(orderAmount) from platform_recharge_order where {$whereStr} and orderStatus = 'Fail') as failAmount,
        (select count(id) from platform_recharge_order where {$whereStr} and orderStatus = 'Fail') as failNumber,
        (select sum(orderAmount) from platform_recharge_order where {$whereStr} and orderStatus = 'Transfered') as transferedAmount,
        (select count(id) from platform_recharge_order where {$whereStr} and orderStatus = 'Transfered') as transferedNumber
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
            $data = $model->orderBy('id', 'desc')->get();
        }
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
                "orderAmount" => $v->realOrderAmount,
                "orderId" => Tools::getHashId($v->id),
                // "orderId" => $v->orderId,
                "orderReason" => $v->orderReason,
                "orderStatus" => $v->orderStatus,
                "orderStatusDesc" => $code['recharge']['orderStatus'][$v->orderStatus] ?? null,
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType] ?? null,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]['shortName'] : null,
                "serviceCharge" => $v->serviceCharge,
                "channelServiceCharge" => $v->channelServiceCharge,
                "agentFee" => $v->agentFee,
                "agentName" => $v->agentName,
            ];
            $nv['noticeDate'] = date('Y-m-d H:i:s',strtotime($v->channelNoticeTime));
            $nv['createDate'] = date('Y-m-d H:i:s',strtotime($v->created_at));
            $rows[] = $nv;
        }
        if($export) {
            Tools::csv_export($rows, $this->exportRechargeHead(), 'rechargeOrderList');
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

}