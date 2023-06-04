<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\Amount;
use App\Models\AmountPay;
use App\Models\ChannelMerchantRate;
use App\Models\Finance;
use App\Models\Merchant;
use App\Models\Channel;
use App\Models\MerchantAccount;
use App\Models\ChannelMerchant;
use App\Models\MerchantAmount;
use App\Models\MerchantBankCard;
use App\Models\MerchantChannelSettlement;
use App\Models\MerchantRate;
use App\Models\Banks;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementRechargeOrder;
use App\Models\SystemAccountActionLog;
use App\Models\BlackUserSettlement;
use App\Queues\SettlementFetchExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;
use App\Channels\ChannelProxy;
use App\Helpers\GoogleAuthenticator;
use App\Queues\PlatformNotifyExecutor;


class SettlementOrderController extends MerchantController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/settlementorder.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function rechargeOrder(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/rechargeorder/index.twig', [
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
        $bankCode = $request->getParam('bankCode');
        $bankAccountNo = $request->getParam('bankAccountNo');
        $bankAccountName = $request->getParam('bankAccountName');
        $beginTime = $request->getParam('beginTime',date('Y-m-d'));
        $endTime = $request->getParam('endTime',date('Y-m-d').' 23:59:59');
        $channel = $request->getParam('channel');
        $merchantId = $_SESSION['merchantId'];

//        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
//        $merchantData && $merchantId = $merchantData['merchantId'];
//        $merchantNo && $model = $model->where('merchantId', $merchantId);
        $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('settlementRechargeOrderNo', $platformOrderNo);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $orderStatus && $model = $model->where('orderStatus', $orderStatus);
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
                "orderStatusDesc" => $code['settlementOrderStatus'][$v->orderStatus] ?? null,
//                "payType" => $v->payType,
//                "payTypeDesc" => $code['payType'][$v->payType] ?? null,
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

    private function exportSettlementOrderHead(){
        return [
            'platformOrderNo' => '平台订单号',
            'merchantOrderNo' => '商户订单号',
            'orderAmount' => '订单金额',
            'serviceCharge' => '手续费',
            'bankCodeDesc' => '收款银行',
            'bankAccountNo' => '收款卡号',
            'bankAccountName' => '收款姓名',
            'orderReason' => '用途',
            'orderStatusDesc' => '订单状态',
            'createDate' => '生成时间',
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
        // $merchantNo = $_SESSION['merchantNo'];
        $platformOrderNo = $request->getParam('platformOrderNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $orderStatus = $request->getParam('orderStatus');
        $bankCode = $request->getParam('bankCode');
        $bankAccountNo = $request->getParam('bankAccountNo');
        $bankAccountName = $request->getParam('bankAccountName');
        $minMoney = $request->getParam('minMoney');
        $maxMoney = $request->getParam('maxMoney');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $channel = $request->getParam('channel');
        $export = $request->getParam('export');

        $params = $request->getParams();
        if(count($params) <=3 ){
            empty($beginTime) && ($beginTime = date('Y-m-d'));
            empty($endTime) && ($endTime = date('Y-m-d').' 23:59:59');
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
        $bankCode && $model = $model->where('bankCode', $bankCode);
        $bankAccountNo && $model = $model->where('bankAccountNo', Tools::encrypt($bankAccountNo));
        $bankAccountName && $model = $model->where('bankAccountName', $bankAccountName);
        $channel && $model = $model->where('channel', $channel);
        $minMoney && $model=$model->where('orderAmount','>=',$minMoney);
        $maxMoney && $model= $model->where('orderAmount','<=',$maxMoney);
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
            $m_bankAccountNo = Tools::decrypt($v->bankAccountNo);
            $is_alipay = preg_match("/^(1[3-9]\d{9}|[\w\-\.]+@[\w\-]+\.[\w\-]+(\.[\w\-]+)?)$/", $m_bankAccountNo);
            $nv = [
                'bankAccountName' => $v->bankAccountName,
                'bankAccountNo' => $is_alipay ? $m_bankAccountNo : null,
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
                "failReason" => $v->orderStatus == 'Fail' ? $v->failReason : '-',
                "orderStatusDesc" => $code['settlementOrderStatus'][$v->orderStatus] ?? null,
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType] ?? null,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]['shortName'] : null,
                "serviceCharge" => $v->serviceCharge,
            ];
            $nv['noticeDate'] = date('Y-m-d H:i:s',strtotime($v->channelNoticeTime));
            $nv['createDate'] = date('Y-m-d H:i:s',strtotime($v->created_at));
            $rows[] = $nv;
        }
        if($export) {
            Tools::csv_export($rows, $this->exportSettlementOrderHead(), 'settlementOrderList');
            die();
        }
        $where = [];
        $where[] = '1=1';
        $merchantId = $_SESSION['merchantId'];
        $merchantId && $where[] = "merchantId = " . $merchantId;

        $platformOrderNo && $where[] = "platformOrderNo = '" . $platformOrderNo . "'";
        $merchantOrderNo && $where[] = "merchantOrderNo = '" . $merchantOrderNo . "'";

        $orderStatus && $where[] = "orderStatus='" . $orderStatus . "'";
        $bankCode && $where[] = "bankCode='" . $bankCode . "'";

        $bankAccountNo && $where[] = "bankAccountNo='" . Tools::encrypt($bankAccountNo) . "'";
        $bankAccountName && $where[] = "bankAccountName='" . $bankAccountName . "'";

        $beginTime && $where[] = "created_at>='" . $beginTime . "'";
        $endTime && $where[] = "created_at<='" . $endTime . "'";
        $whereStr = implode(' and ', $where);
        $stat = $model1->selectRaw("
        count(orderId) as number,
        sum(orderAmount) as orderAmount,
        (select sum(orderAmount) from platform_settlement_order where {$whereStr} and orderStatus = 'Exception') as exceptionAmount,
        (select count(orderId) from platform_settlement_order where {$whereStr} and orderStatus = 'Exception') as exceptionNumber,
        (select sum(orderAmount) from platform_settlement_order where {$whereStr} and orderStatus = 'Success') as successAmount,
        (select sum(serviceCharge) from platform_settlement_order where {$whereStr} and orderStatus = 'Success') as serviceCharge,
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

        $rateModeel = new MerchantRate();
        $rateArr = $rateModeel->where('merchantId',$_SESSION['merchantId'])->where('productType','Settlement')->get()->toArray();

        $stat['orderAmount'] = number_format($stat['orderAmount'] ?? 0, 2);
        $stat['exceptionAmount'] = number_format($stat['exceptionAmount'] ?? 0, 2);
        $stat['successAmount'] = number_format($stat['successAmount'] ?? 0, 2);
        $stat['failAmount'] = number_format($stat['failAmount'] ?? 0, 2);
        $stat['transferedAmount'] = number_format($stat['transferedAmount'] ?? 0, 2);
        $stat['serviceCharge'] = number_format($stat['serviceCharge'] ?? 0, 2);

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
            'stat' => $stat,
            'rateArr' => $rateArr
        ]);
    }

    public function getParams($param)
    {
        $params = Tools::decrypt($param);
        $paramsArr = json_decode($params, true);
        $newArr = [];

        //cId 商户号、ipWhite ip白名单、settlementMerchantNo  代付商户号、appAccount 支付宝账号
        $arr = ['cId','cid', 'ipWhite', 'settlementMerchantNo', 'appAccount','appId'];
        foreach ($paramsArr as $key => $item) {
            if (in_array($key, $arr)) {
                $newArr[$key] = $item;
            } else {
                $newArr[$key] = '';
            }
        }
        $newArr = json_encode($newArr, JSON_UNESCAPED_UNICODE);
        return $newArr;
    }

    public function settlementChannelSearch(Request $request, Response $response, $args)
    {
        $model = new MerchantChannelSettlement();
        $merchant = new Merchant;
        $merchantData = [];
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $shortName = $request->getParam('shortName');

        $model = $model->where('merchantNo', $_SESSION['merchantNo']);
        // $shortName && $model->where('shortName', $shortName);

        $total = $model->count();
        $data = $model->offset($offset)
            ->limit($limit)
            ->orderBy('setId', 'desc')
            ->get();
        $rows = [];

        $channelBalance = [];
        $channel = new Channel;
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);

            $channelBalance[$v->channelMerchantNo] = isset($channelBalance[$v->channelMerchantNo]) ? $channelBalance[$v->channelMerchantNo]
                : $channel->getBalance($v->channelMerchantNo);

            $nv = [
//                 'accountBalance' => $channelBalance[$v->channelMerchantNo],
                'accountBalance' => $v->accountBalance,
                'channel' => $v->channel,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                'channelMerchantId' => Tools::getHashId($v->channelMerchantId),
                'channelMerchantNo' => $v->channelMerchantNo,
                "merchantNo" => $v->merchantNo,
                "setId" => Tools::getHashId($v->setId),
                "settlementAccountType" => $v->settlementAccountType,
                "settlementAccountTypeDesc" => $code['settlementAccountType'][$v->settlementAccountType] ?? "",
                "shortName" => $merchantData[$v->merchantId]['shortName'],
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

    public function cardSearch(Request $request, Response $response, $args)
    {
        $merchantCard = new MerchantBankCard();

        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $bankCode = $request->getParam('bankCode');
        $merchantCard = $merchantCard->where('status', 'Normal');
        $bankCode && $merchantCard = $merchantCard->where('bankCode', $bankCode);
        $merchantCard = $merchantCard->where('merchantId', $_SESSION['merchantId']);
        $total = $merchantCard->count();
        $cardLists = $merchantCard->orderBy('id', 'desc')->offset($offset)->limit($limit)->get()->toArray();
        foreach ($cardLists as $k => $list) {
            $cardLists[$k]['accountNo'] = Tools::decrypt($list['accountNo']);
        }

        return $response->withJson([
            'result' => [],
            'rows' => $cardLists,
            'success' => 1,
            'total' => $total,
        ]);

    }

    public function chooseMerchantCard(Request $request, Response $response, $args)
    {
        $cardId = $request->getParam("cardId");
        $merchantCard = new MerchantBankCard();
        $cardId && $merchantCard = $merchantCard->where('id', $cardId);
        $merchantCard = $merchantCard->where('status', 'Normal')->where("merchantId", $_SESSION['merchantId']);

        $cardLists = $merchantCard->get()->toArray();
        $comInfos = [];
        foreach ($cardLists as $key => $list) {
            $comInfo = "收款名称：" . $list['accountName'] . " / 卡号：" . Tools::decrypt($list['accountNo']) . " / 银行：" . $list['bankName'];
            $comInfos[] = ['key' => $list['id'], "value" => $comInfo];
            $cardLists[$key]['comInfos'] = $comInfo;
            $cardLists[$key]['accountNo'] = Tools::decrypt($list['accountNo']);
        }

        return $response->withJson([
            'comInfos' => $comInfos,
            'rows' => $cardLists,
            'success' => 1,
        ]);
    }

    public function addMerchantCard(Request $request, Response $response, $args)
    {
        $data['merchantId'] = $_SESSION['merchantId'];
        $data['bankCode'] = $request->getParam("bankCode");
        $data['accountNo'] = Tools::encrypt($request->getParam("accountNo"));
        $data['accountName'] = $request->getParam("accountName");
        $data['province'] = $request->getParam("province");
        $data['city'] = $request->getParam("city");
        $data['district'] = $request->getParam("district");
        $data['bankName'] = $this->c->code['bankCode'][$data['bankCode']];

        $merchant = new Merchant();
        $merchantCard = new MerchantBankCard();
        $merchantData = $merchant->getCacheByMerchantId($data['merchantId']);

//        var_dump($merchantData,$data['merchantId']);exit;
        if (!$merchantData) {
            return $response->withJson([
                'result' => '商户号不存在',
                'success' => 0,
            ]);
        }

        $cardInfo = $merchantCard->where('merchantId', $data['merchantId'])->where('status', "Normal")->where("accountNo", $data['accountNo'])->first();
        if ($cardInfo) {
            return $response->withJson([
                'result' => '已添加此卡',
                'success' => 0,
            ]);
        }
        $merchantCard->insert($data);
        return $response->withJson([
            'result' => '上传成功',
            'success' => 1,
        ]);
    }

    public function deleteCard(Request $request, Response $response, $args)
    {
        $id = $request->getParam("cardId");

        $merchant = new Merchant();
        $merchantCard = new MerchantBankCard();
        $merchantData = $merchant->getCacheByMerchantId($_SESSION['merchantId']);
        if (!$merchantData) {
            return $response->withJson([
                'result' => '商户号不存在',
                'success' => 0,
            ]);
        }

        $cardInfo = $merchantCard->where('merchantId', $_SESSION['merchantId'])->where("status", "Normal")->where('id', $id)->first();
        if (empty($cardInfo)) {
            return $response->withJson([
                'result' => '无此银行卡信息',
                'success' => 0,
            ]);
        }

        $cardInfo->status = "Deleted";
        $cardInfo->save();
        return $response->withJson([
            'result' => '删除成功',
            'success' => 1,
        ]);

    }

    public function updateMerchantCard(Request $request, Response $response, $args)
    {
        $bankCode = $request->getParam("bankCode");
        $accountNo = Tools::encrypt($request->getParam("accountNo"));
        $accountName = $request->getParam("accountName");
        $province = $request->getParam("province");
        $city = $request->getParam("city");
        $district = $request->getParam("district");
        $logger = $this->c->logger;
        $id = $request->getParam("cardId");
        $merchantId = $_SESSION['merchantId'];

        $merchant = new Merchant();
        $merchantCard = new MerchantBankCard();
        $merchantData = $merchant->getCacheByMerchantId($merchantId);
        if (!$merchantData) {
            return $response->withJson([
                'result' => '商户号不存在',
                'success' => 0,
            ]);
        }

        $cardInfo = $merchantCard->where('merchantId', $merchantId)->where('id', $id)->first();
        if (empty($cardInfo)) {
            return $response->withJson([
                'result' => '无此银行卡信息',
                'success' => 0,
            ]);
        }

        if ($accountNo != $cardInfo->accountNo) {
            $existBank = $merchantCard->where('merchantId', $merchantId)->where("status", "Normal")->where('accountNo', $accountNo)->first();
            if ($existBank) {
                return $response->withJson([
                    'result' => '已添加此卡',
                    'success' => 0,
                ]);
            }
        }

        try {
            $db = $this->c->database;
            $db->getConnection()->beginTransaction();
            $cardInfo->bankName = $this->c->code['bankCode'][$bankCode];
            $cardInfo->bankCode = $bankCode;
            $cardInfo->accountNo = $accountNo;
            $cardInfo->accountName = $accountName;
            $cardInfo->province = $province;
            $cardInfo->city = $city;
            $cardInfo->district = $district;
            $cardInfo->save();
            $db->getConnection()->commit();

        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            $db->getConnection()->rollback();
        }

        return $response->withJson([
            'result' => '更新成功',
            'success' => 1,
        ]);
    }

    public function create(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/settlementorder_create.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function aliCreate(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/settlementorder_aliCreate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function cardList(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/cardlist.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function settlementChannel(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/settlementchannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/merchantSettlementTmpl.csv',
        ]);
    }

    public function aliSettlement(Request $request, Response $response, $args)
    {


        $params = $request->getParams();
        $aliAccountNo = $request->getParam('aliAccountNo');
        $aliAccountName = $request->getParam('aliAccountName');
        $orderReason = $request->getParam('orderReason');
        $applyPerson = $request->getParam('applyPerson');
        $orderAmount = $request->getParam('orderAmount');
        $channels = $this->c->code['channel'];

        $code = 'SUCCESS';
        $data = [];
//        $bankCode = array_keys($this->c->code['bankCode']);
//        $channels = $this->c->code['channel'];
        $logger = $this->c->logger;
        $db = $this->c->database;

        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->debug("商户后台支付宝代付请求");

        $validator = $this->c->validator->validate($request, [
            'aliAccountNo' => Validator::noWhitespace()->notBlank(),
            'aliAccountName' => Validator::noWhitespace()->notBlank(),
            'orderReason' => Validator::noWhitespace()->notBlank(),
            'applyPerson' => Validator::noWhitespace()->notBlank(),
            'orderAmount' => Validator::noWhitespace()->notBlank(),
            'googleAuth' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('valid', $validator->getErrors());
            return $response->withJson([
                'result' => "验证不通过",
                'success' => 0,
            ]);
        }

        if(!$_SESSION['googleAuthSecretKey']){
            $logger->error("请绑定谷歌验证码后再操作！！");
            return $response->withStatus(200)->withJson([
                'result' => '请绑定谷歌验证码后再操作！',
                'success' => 2,
            ]);
        }
        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $params['googleAuth'], 2);

        if(!$checkResult) {
            $logger->error("谷歌验证码错误，请重新输入 ！");
            return $response->withStatus(200)->withJson([
                'result' => '谷歌验证码错误，请重新输入！',
                'success' => 2,
            ]);
        }

        // $merchant = new Merchant;
        // $merchantData =  $merchant->getCacheByMerchantId($_SESSION['merchantId']);
        $merchantAccount = new MerchantAccount;
        $merchantAccountData = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if (Tools::getHashPassword($applyPerson) != $merchantAccountData->securePwd) {
            $logger->error('支付密码错误，hash pwd:' . Tools::getHashPassword($applyPerson) . '，secure pwd:' . $merchantAccountData->securePwd);
            return $response->withJson([
                'result' => "验证不通过",
                'success' => 0,
            ]);
        }
        // $model = new PlatformSettlementOrder();
        // $data = (new PlatformSettlementOrder())->create($request,
        //     Tools::getPlatformOrderNo('S'),
        //     intval($orderAmount * 100) / 100,
        //     $_SESSION['merchantNo']
        // );

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openAliSettlement']) {
                $code = 'E2007';
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (isset($merchantData['openManualSettlement']) && !$merchantData['openManualSettlement']) {
                $code = 'E2202';
            }
        }

        // if ($code == 'SUCCESS') {
        //     if (!Tools::checkSign($merchantData, $request->getParams())) {
        //         $code = 'E1005';
        //     }
        // }
        if ($code == 'SUCCESS') {
            if ($merchant->isExceedDaySettleAmountLimit($request->getParam('orderAmount'), $merchantData)) {
                $logger->error("超过商户单日累计代付总金额限制");
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
            $merchantRateData = $merchantRate->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantRateData)) {
                $logger->error("代付请求：商户未配置费率");
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $amountData = (new MerchantAmount())->getAmount($merchantData['merchantId'], '', $merchantData);
            $orderAmount = intval($request->getParam('orderAmount') * 100) / 100;
            $params['orderAmount'] = $orderAmount;
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');
            if ($request->getParam('orderAmount') + $serviceCharge > $amountData['availableBalance']) {
                $logger->error("代付余额不足");
                $code = 'E2105';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannelSettlement();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantChannelData)) {
                $logger->error("代付请求：未配置商户代付通道");
                $code = 'E2003';
            }
        }

        if ($code == 'SUCCESS') {
            $settlementType = '';
            $merchantChannelConfig = $merchantChannel->fetchConfig($_SESSION['merchantNo'], $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $aliAccountNo, $channels, "ALIPAY");
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannelSettelement fetchConfig失败", $merchantChannelData);
            }
        }

        if ($code == 'SUCCESS') {
            $blackUserSettlement = new BlackUserSettlement();
            $isblackUserExists = $blackUserSettlement->checkBlackUser($request->getParam('bankCode'),$aliAccountName,$aliAccountNo);
            if($isblackUserExists){
                $code = 'E2201';
                $logger->error("代付请求：代付黑名单用户！");
            }
        }

//        if ($code == 'SUCCESS' && getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT')) {
//
//            //风控限制
//            $settlementRecordKeys = $this->c->redis->redis_keys('settlement:'.$aliAccountName.':' . "*");
//            if($settlementRecordKeys){
//                if(count($settlementRecordKeys) >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_COUNT')){
//                    $code = 'E2108';
//                    $logger->error("代付请求：1小时内同一出款人笔数超限！");
//                }
//                $temp = 0;
//                foreach ($settlementRecordKeys as $settlementRecordKey){
//                    $temp += $this->c->redis->get($settlementRecordKey);
//                }
//                if($temp + $orderAmount >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_AMOUNT')){
//                    $code = 'E2109';
//                    $logger->error("代付请求：1小时内同一出款人金额超限！");
//                }
//            }
//
//            if($code != 'SUCCESS'){
//
//                if(!$this->c->redis->get($aliAccountName.'-'.$_SESSION['merchantNo'])){
//                    (new PlatformNotifyExecutor)->push('gm','risk', $aliAccountName.'-'.$_SESSION['merchantNo']);
//                    $this->c->redis->setex($aliAccountName.'-'.$_SESSION['merchantNo'],40,1);
//                }
//                if(!$_SESSION['googleAuthSecretKey']){
//                    $logger->error("此次操作存在风险，请绑定谷歌验证码后再操作！！");
//                    return $response->withStatus(200)->withJson([
//                        'result' => '此次操作存在风险，请绑定谷歌验证码后再操作！',
//                        'success' => 2,
//                    ]);
//                }
//
//                if(!isset($params['googleAuth']) || !$params['googleAuth']){
//                    $logger->error("此次操作存在风险，请输入谷歌验证码 ！");
//                    return $response->withStatus(200)->withJson([
//                        'result' => '此次操作存在风险，请输入谷歌验证码！',
//                        'success' => 2,
//                    ]);
//                }
//                $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
//                $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $params['googleAuth'], 2);
//
//                if(!$checkResult) {
//                    $logger->error("谷歌验证码错误，请重新输入 ！");
//                    return $response->withStatus(200)->withJson([
//                        'result' => '谷歌验证码错误，请重新输入！',
//                        'success' => 2,
//                    ]);
//                }
//
//                $code = 'SUCCESS';
//
//            }
//        }

//        if ($code == 'SUCCESS' && $request->getParam('notify') != 1) {//风控消息
//            $warning = new \App\Logics\WarningLogic($this->c);
//            $notify_params = [
//                'merchantId' => $merchantData['merchantId'],//商户id
//                'merchantNo' => $merchantData['merchantNo'],//商户号
//                'accountName' => $aliAccountName,//支付宝姓名
//                'accountNo' => $aliAccountNo,//支付宝账户
//                'orderAmount' => $request->getParam('orderAmount'),//出款金额
//                'settlement_type' => '单笔手动出款',//出款类型
//            ];
//            $notify_return = $warning->merchant_notify($notify_params);
//            if($notify_return['code'] != 'SUCCESS'){
//                return $response->withStatus(200)->withJson([
//                    'result' => $notify_return['msg'] . ',请谨慎操作！',
//                    'success' => 3,
//                ]);
//            }
//        }

        $key = 'aliSettlement' . ':' . $_SESSION['merchantNo'] . ':' . $aliAccountNo;
        if($this->c->redis->get($key)){
            $logger->error('后台支付宝单笔手动代付操作频繁');
            return $response->withJson([
                'result' => "后台支付宝单笔手动代付操作频繁",
                'success' => 0,
            ]);
        }
        $this->c->redis->setex($key, 60, 1);

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
                $params['merchantNo'] = $_SESSION['merchantNo'];
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');
                if ($serviceCharge === null) {
                    throw new \Exception("getServiceCharge异常");
                }

                if ($orderAmount + $serviceCharge > $amountData['availableBalance']) {
                    throw new \Exception("余额异常");
                }

                // $channelServiceCharge = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $params, 'Settlement');
                // if ($channelServiceCharge === null) {
                //     throw new \Exception("getChannelServiceCharge异常");
                // }
//                var_dump($request->getParam('aliAccountName'));exit;
                $settlementType = "aliSettlement";
                $data = (new PlatformSettlementOrder())->create($request,
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
                    // $channelConfig['settlementAccountType'],
                    '商户后台发起'
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
                        'summary' => $data->tradeSummary,
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'merchant',
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
                        'summary' => $data->tradeSummary,
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'merchant',
                    ],
                ]);

                AmountPay::where('merchantId', $data->merchantId)
                    ->where('accountDate', $accountDate)
                    ->update(['balance' => $merchantAmountLockData->settlementAmount]);
                //丢入队列 实际代付
                (new SettlementFetchExecutor)->push(0, $platformOrderNo);
                $merchantAmountLockData->refreshCache(['merchantId' => $merchantAmountLockData->merchantId]);

                (new Merchant)->incrCacheByDaySettleAmountLimit($data->merchantNo, intval($data->orderAmount * 100));

                $db->getConnection()->commit();
                //写入缓存一小时
                $this->c->redis->setex('settlement:'.$aliAccountName.':'.$platformOrderNo,60*60,$orderAmount);

            } catch (\Exception $e) {
                $logger->error('Exception:' . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }

        if ($code == 'SUCCESS') {
            return $response->withStatus(200)->withJson([
                'result' => $this->c->code['status'][$code],
                'success' => 1,
            ]);
        } else {
            $logger->error('代付请求返回，code:' . $code . '，msg:' . $this->c->code['status'][$code]);
            return $response->withJson([
                'result' => $this->c->code['status'][$code],
                'success' => 0,
            ]);
        }


    }

    public function doCreate(Request $request, Response $response, $args)
    {
        $params = $request->getParams();
        $bankCode = $request->getParam('bankCode');
        $bankAccountNo = $request->getParam('bankAccountNo');
        $bankAccountName = $request->getParam('bankAccountName');
        $province = $request->getParam('province');
        $city = $request->getParam('city');
        $bankName = $request->getParam('bankName');
        $orderReason = $request->getParam('orderReason');
        $applyPerson = $request->getParam('applyPerson');
        $orderAmount = $request->getParam('orderAmount');
        //是否是支付宝代付到银行卡
        $alipayToBank = false;

        $code = 'SUCCESS';
        $data = [];
        $bankCode = array_keys($this->c->code['bankCode']);
        $channels = $this->c->code['channel'];
        $logger = $this->c->logger;
        $db = $this->c->database;

        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });

        $logger->debug("商户后台代付请求");

        $validator = $this->c->validator->validate($request, [
            'bankCode' => Validator::in($bankCode)->notBlank(),
            'bankAccountNo' => Validator::noWhitespace()->notBlank(),
            'bankAccountName' => Validator::notBlank(),
            'province' => Validator::notBlank(),
            'city' => Validator::notBlank(),
//            'bankName' => Validator::noWhitespace()->notBlank(),
            'orderReason' => Validator::notBlank(),
            'applyPerson' => Validator::notBlank(),
            'orderAmount' => Validator::noWhitespace()->notBlank(),
            'googleAuth' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('valid', $validator->getErrors());
            return $response->withJson([
                'result' => "验证不通过，必要数据为空",
                'success' => 0,
            ]);
        }

        if(!$_SESSION['googleAuthSecretKey']){
            $logger->error("此次操作存在风险，请绑定谷歌验证码后再操作！！");
            return $response->withStatus(200)->withJson([
                'result' => '此次操作存在风险，请绑定谷歌验证码后再操作！',
                'success' => 2,
            ]);
        }

        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $params['googleAuth'], 2);

        if(!$checkResult) {
            $logger->error("谷歌验证码错误，请重新输入 ！");
            return $response->withStatus(200)->withJson([
                'result' => '谷歌验证码错误，请重新输入！',
                'success' => 2,
            ]);
        }
        // $merchant = new Merchant;
        // $merchantData =  $merchant->getCacheByMerchantId($_SESSION['merchantId']);
        $merchantAccount = new MerchantAccount;
        $merchantAccountData = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if (Tools::getHashPassword($applyPerson) != $merchantAccountData->securePwd) {
            $logger->error('支付密码错误，hash pwd:' . Tools::getHashPassword($applyPerson) . '，secure pwd:' . $merchantAccountData->securePwd);
            return $response->withJson([
                'result' => "验证不通过：支付密码错误",
                'success' => 0,
            ]);
        }
        // $model = new PlatformSettlementOrder();
        // $data = (new PlatformSettlementOrder())->create($request,
        //     Tools::getPlatformOrderNo('S'),
        //     intval($orderAmount * 100) / 100,
        //     $_SESSION['merchantNo']
        // );

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (!$merchantData['openSettlement']) {
                $code = 'E2002';
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $code = 'E2001';
            } else if (isset($merchantData['openManualSettlement']) && !$merchantData['openManualSettlement']) {
                $code = 'E2202';
            }
        }

        // if ($code == 'SUCCESS') {
        //     if (!Tools::checkSign($merchantData, $request->getParams())) {
        //         $code = 'E1005';
        //     }
        // }

        if ($code == 'SUCCESS') {
            if ($merchant->isExceedDaySettleAmountLimit($request->getParam('orderAmount'), $merchantData)) {
                $logger->error("超过商户单日累计代付总金额限制");
                $code = 'E2107';
            }
        }

        // if ($code == 'SUCCESS') {
        //     $w = intval(date('w'));
        //     $isWorkerday = in_array($w, [1, 2, 3, 4, 5]) ? true : false;
        //     if ($isWorkerday && $merchantData['openWorkdaySettlement'] == false) {
        //         $logger->debug("openWorkdaySettlement关闭");
        //         $code = 'E2201';
        //     }

        //     if ($isWorkerday == false && $merchantData['openHolidaySettlement'] == false) {
        //         $logger->debug("openHolidaySettlement关闭");
        //         $code = 'E2201';
        //     }
        // }

        // if ($code == 'SUCCESS') {
        //     $settlementRate = 1;
        //     $settlementType = null;
        //     $settlementMaxAmount = 0;
        //     if ($isWorkerday) {
        //         $settlementRate = $merchantData['openWorkdaySettlement'];
        //         $settlementType = $merchantData['workdaySettlementType'];
        //         $settlementMaxAmount = $merchantData['workdaySettlementMaxAmount'];
        //     } else {
        //         $settlementRate = $merchantData['holidaySettlementRate'];
        //         $settlementType = $merchantData['holidaySettlementType'];
        //         $settlementMaxAmount = $merchantData['holidaySettlementMaxAmount'];
        //     }

        //     $merchantAmount = new MerchantAmount;
        //     $merchantAmountData = $merchantAmount->getCacheByMerchantId($merchantData['merchantId']);
        //     if ($request->getParam('orderAmount') > $merchantAmountData['settlementAmount'] * $settlementRate) {
        //         $logger->debug("超过最大垫资比例");
        //         $code = 'E2106';
        //     }
        // }

        if ($code == 'SUCCESS') {
            if (!Tools::isAllowIPAccess($request->getParam('requestsIp'))) {
                $logger->error("isAllowIPAccess");
                $code = 'E2104';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantRate = new MerchantRate();
            $merchantRateData = $merchantRate->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantRateData)) {
                $logger->error("代付请求：商户未配置费率");
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], '', $merchantData);
            $params = $request->getParams();
            $orderAmount = intval($request->getParam('orderAmount') * 100) / 100;
            $params['orderAmount'] = $orderAmount;
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');
            if ($request->getParam('orderAmount') + $serviceCharge > $amountData['availableBalance']) {
                $logger->error("代付余额不足");
                $code = 'E2105';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannelSettlement();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($_SESSION['merchantNo']);
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

        if ($code == 'SUCCESS') {
            $settlementType = '';
            $merchantChannelConfig = $merchantChannel->fetchConfig($_SESSION['merchantNo'], $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $bankAccountNo, $channels, $request->getParam('bankCode'));
            $b = $request->getParam('bankCode');
            if(in_array($b,array_keys(Merchant::$aliBanks)) && empty($merchantChannelConfig) && isset($merchantData['openAliSettlement']) && $merchantData['openAliSettlement']){     //若无任何可代付到银行卡的上游，读取支付宝代付银行卡配置
                $alipayToBank = true;
                $merchantChannelConfig = $merchantChannel->fetchConfig($_SESSION['merchantNo'], $merchantChannelData, $settlementType, $request->getParam('orderAmount'), $bankAccountNo, $channels, 'ALIPAY');
            }
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannelSettelement fetchConfig失败", $merchantChannelData);
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

        // if ($code == 'SUCCESS') {
        //     $channelConfig = $merchantChannel->getRandConfig($merchantChannelConfig);
        //     if (!isset($channels[$channelConfig['channel']])) {
        //         $code = 'E2003';
        //         $logger->debug("merchantChannelSettlement channel查找失败");
        //     } else {
        //         $channel = $channels[$channelConfig['channel']];
        //     }
        // }

        // if ($code == 'SUCCESS') {
        //     try {
        //         $channel['dbParam'] = $channelConfig['dbParam'];
        //         $channelPay = new $channel['path']($channel);
        //         $params = $request->getParams();
        //         $params['platformOrderNo'] = Tools::getPlatformOrderNo('P');
        //         $params['CB'] = getenv('CB_DOMAIN') . '/callback/' . $params['platformOrderNo'];
        //         // $params['orderAmount'] = $channelPay->changeOrderAmount($orderAmount);
        //         $channelOrder = $channelPay->getChannelOrder($params);
        //     } catch (\Exception $e) {
        //         $logger->debug("getChannelOrder失败" . $e->getMessage());
        //         $code = 'E1004';
        //     }
        // }

//        if ($code == 'SUCCESS' && getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT')) {
//            //风控限制
//
//            $settlementRecordKeys = $this->c->redis->redis_keys('settlement:'.$bankAccountName.':' . "*");
//            if($settlementRecordKeys){
//                if(count($settlementRecordKeys) >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_COUNT')){
//                    $code = 'E2108';
//                    $logger->error("代付请求：1小时内同一出款人笔数超限！");
//                }
//                $temp = 0;
//                foreach ($settlementRecordKeys as $settlementRecordKey){
//                    $temp += $this->c->redis->get($settlementRecordKey);
//                }
//                if($temp + $orderAmount >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_AMOUNT')){
//                    $code = 'E2109';
//                    $logger->error("代付请求：1小时内同一出款人金额超限！");
//                }
//            }
//            if($code != 'SUCCESS'){
//                if(!$this->c->redis->get($bankAccountName.'-'.$_SESSION['merchantNo'])){
//                    (new PlatformNotifyExecutor)->push('gm','risk', $bankAccountName.'-'.$_SESSION['merchantNo']);
//                    $this->c->redis->setex($bankAccountName.'-'.$_SESSION['merchantNo'],40,1);
//                }
//
//                if(!$_SESSION['googleAuthSecretKey']){
//                    $logger->error("此次操作存在风险，请绑定谷歌验证码后再操作！！");
//                    return $response->withStatus(200)->withJson([
//                        'result' => '此次操作存在风险，请绑定谷歌验证码后再操作！',
//                        'success' => 2,
//                    ]);
//                }
//
//                if(!isset($params['googleAuth']) || !$params['googleAuth']){
//                    $logger->error("此次操作存在风险，请输入谷歌验证码 ！");
//                    return $response->withStatus(200)->withJson([
//                        'result' => '此次操作存在风险，请输入谷歌验证码！',
//                        'success' => 2,
//                    ]);
//                }
//                $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
//                $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $params['googleAuth'], 2);
//
//                if(!$checkResult) {
//                    $logger->error("谷歌验证码错误，请重新输入 ！");
//                    return $response->withStatus(200)->withJson([
//                        'result' => '谷歌验证码错误，请重新输入！',
//                        'success' => 2,
//                    ]);
//                }
//
//                $code = 'SUCCESS';
//
//            }
//        }
        if ($code == 'SUCCESS') {
            // $channelMerchantRate = new ChannelMerchantRate;
            // $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

            try {
                $db->getConnection()->beginTransaction();
                $accountDate = Tools::getAccountDate($merchantData['settlementTime']);
                $merchantAmount = new MerchantAmount;
                $merchantAmountLockData = $merchantAmount->where('merchantId', $merchantData['merchantId'])->lockForUpdate()->first();
//                $orderAmount = intval($request->getParam('orderAmount') * 100) / 100;
                $orderAmount = bcmul($request->getParam('orderAmount') , 100,0) / 100;
                $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], $merchantAmountLockData->toArray(), $merchantData);

                $platformOrderNo = Tools::getPlatformOrderNo('S');
                $params = $request->getParams();
                $params['orderAmount'] = $orderAmount;
                $params['merchantNo'] = $_SESSION['merchantNo'];
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $params, 'Settlement');
                if ($serviceCharge === null) {
                    throw new \Exception("getServiceCharge异常");
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
                    // $channelConfig['settlementAccountType'],
                    '商户后台发起'
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
                        'summary' => $data->tradeSummary,
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'merchant',
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
                        'summary' => $data->tradeSummary,
                        'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                        'operateSource' => 'merchant',
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
                (new Merchant)->incrCacheByDaySettleAmountLimit($data->merchantNo, intval($data->orderAmount * 100));
                $db->getConnection()->commit();

                //写入缓存一小时
                $this->c->redis->setex('settlement:'.$bankAccountName.':'.$platformOrderNo,60*60,$orderAmount);

            } catch (\Exception $e) {
                $logger->error('Exception:' . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }

        if ($code == 'SUCCESS') {
            return $response->withStatus(200)->withJson([
                'result' => $this->c->code['status'][$code],
                'success' => 1,
            ]);
        } else {
            $logger->error('代付请求返回，code:' . $code . '，msg:' . $this->c->code['status'][$code]);
            return $response->withJson([
                'result' => $this->c->code['status'][$code],
                'success' => 0,
            ]);
        }
    }

    public function settlementChannelRecharge(Request $request, Response $response, $args)
    {

        $return = [
            'msg' => 'fail',
            'result' => [],
            'success' => 0,
        ];

        $setId = $request->getParam('setId', '');
        $amount = $request->getParam('amount', '');
        $setId = Tools::getIdByHash($setId);
        $settlementChannel = MerchantChannelSettlement::where('setId', $setId)->first();
        if (!$settlementChannel) {
            return $response->withJson([
                'msg' => '渠道不存在',
                'result' => [],
            ]);
        }
        $settlementChannel = $settlementChannel->toArray();

        $channelMerchantData = (new ChannelMerchant)->getCacheByChannelMerchantId($settlementChannel['channelMerchantId']);
        $channelConfig = isset($channelMerchantData['config']) ? json_decode($channelMerchantData['config'], true) : [];
        if (!$channelConfig) {
            echo "<script>alert('渠道充值配置为空!');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
            exit;
        }
        if (!isset($channelConfig['outsideRecharge']) || !$channelConfig['outsideRecharge']['open']) {
            echo "<script>alert('该渠道未配置充值!');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
            exit;
        }
        $rateConfig = $channelConfig['outsideRecharge'];

        $rechargeMin = $rateConfig['rechargeMin'];
        $rechargeMax = $rateConfig['rechargeMax'];
        if ($amount < $rechargeMin || $amount > $rechargeMax) {
            echo "<script>alert('充值金额在$rechargeMin - $rechargeMax!');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
            exit;
            return $response->withJson([
                'msg' => '充值金额在10000-200000',
                'result' => [],
            ]);
        }

        $class = 'App\Channels\Lib' . "\\" . ucwords($settlementChannel['channel']);
        if (!class_exists($class) && !method_exists($class, 'settlementRecharge')) {
            return $response->withJson([
                'msg' => '渠道不支持充值',
                'result' => [],
            ]);
        }
        $settlementRechargeOrder = new SettlementRechargeOrder();
        $settlementRechargeOrderN0 = 'R' . date('YmdHis') . rand(10000, 999999);

        $settlementRechargeOrder->settlementRechargeOrderNo = $settlementRechargeOrderN0;
        $settlementRechargeOrder->merchantNo = $settlementChannel['merchantNo'];
        $settlementRechargeOrder->merchantId = $settlementChannel['merchantId'];
        $settlementRechargeOrder->channelMerchantId = $settlementChannel['channelMerchantId'];
        $settlementRechargeOrder->channelMerchantNo = $settlementChannel['channelMerchantNo'];
        $settlementRechargeOrder->orderAmount = $amount;
        $settlementRechargeOrder->realOrderAmount = $amount;
        $settlementRechargeOrder->serviceCharge = $rateConfig['xiayouFixed'] + ($amount * $rateConfig['xiayouPercent']);
        $settlementRechargeOrder->channelServiceCharge = $rateConfig['shangyouFixed'] + ($amount * $rateConfig['shangyouPercent']);
        $settlementRechargeOrder->channel = $settlementChannel['channel'];
        $settlementRechargeOrder->channelSetId = $settlementChannel['setId'];
        $settlementRechargeOrder->orderStatus = 'Transfered';
        $settlementRechargeOrder->type = 'outsideRecharge';

        $res = $settlementRechargeOrder->save();
        if (!$res) return $response->withJson($return);
        $settlementRechargeOrder->setCacheByPlatformOrderNo($settlementRechargeOrderN0, $settlementRechargeOrder->toArray());

//        print_r($settlementRechargeOrder);exit;
        $res = (new ChannelProxy)->settlementRecharge($settlementChannel['channelMerchantId'], $settlementRechargeOrder);


    }

    public function settlementChannelRechargeCheck(Request $request, Response $response, $args)
    {

        $return = [
            'msg' => 'fail',
            'result' => [],
            'success' => 0,
        ];

        $setId = $request->getParam('setId', '');
        $amount = $request->getParam('amount', '');
        if ($amount < 10000 || $amount > 200000) {
            echo "<script>alert('充值金额在10000-200000!');location.href='" . $_SERVER["HTTP_REFERER"] . "';</script>";
            exit;
            return $response->withJson([
                'msg' => '充值金额在10000-200000',
                'result' => [],
            ]);
        }
        $result = [
            'channel' => 'yilian',
            'merchantNo' => 90000010,
            'amount' => 100.00,
        ];
        return $response->withJson([
            'msg' => 'test',
            'result' => $result,
            'success' => 1,
        ]);

    }


    public function account(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/alipayAccount.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    /**
     * 查询当前商户的绑定的支付宝账号
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function getAccount(Request $request, Response $response, $args)
    {
        $model = new ChannelMerchant();
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $status = $request->getParam('status');
        $merchantNo = $request->getParam('merchantNo');

        $settlement = new MerchantChannelSettlement();
        $settlement = $settlement->where('merchantNo', $_SESSION['merchantNo'])->where('channel', 'alipay')->get();

        $status && $model = $model->where('status', $status)->where('channel', 'alipay');

        $merchantNo && $settlement = $settlement->where('merchantNo', $merchantNo);

        $data = $model->get();
        $rows = [];

        foreach ($settlement as $key => $item) {
            foreach ($data ?? [] as $k => $v) {
                if ($v->channelMerchantNo == $item->channelMerchantNo) {
                    if (isset($v->param)) {
                        $tmp = json_decode($v->param, true);
                        if (!is_array($tmp)) {
                            $tmp = json_decode(Tools::decrypt($v->param), true);
                        }
                    }

                    $nv = [
                        'channelMerchantId'=>$v->channelMerchantId,
                        'channelMerchantNo' => $v->channelMerchantNo,
                        'merchantNo' => $item->merchantNo,
                        'status' => $v->status,
                        "channelAccount" => $tmp['appAccount'] != "" ? $tmp['appAccount'] : "未填写",
                        'statusDesc' => $code['commonStatus'][$v->status] ?? '',
                        "param" => self::getParams($v->param),
                        'updated_at' => Tools::getJSDatetime($v->updated_at)
                    ];
                    $rows[] = $nv;
                }
            }
        }
        $start = ($offset - 1) * $limit;//偏移量，当前页-1乘以每页显示条数

        $article = array_slice($rows, $start, $limit);

        return $response->withJson([
            'result' => [],
            'rows' => $article,
            'success' => 1,
            'total' => count($rows),
        ]);
    }

    public function getChannelParameter(Request $request, Response $response, $args)
    {
        $code = $this->c->code;
        $param =  $code['channel']['alipay'];
        $res = isset($param['param']) ? $param['param'] : '';
        $desc = isset($param['paramDesc']) ? $param['paramDesc'] : '';
        return $response->withJson([
            'result' => $res,
            'success' => 1,
            'desc' => $desc,
        ]);
    }

    //支付宝账号相关配置信息详情
    public function accountDetail(Request $request, Response $response, $args)
    {
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $model = new ChannelMerchant();
        $data = $model->where('channelMerchantNo', $channelMerchantNo)->first();
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            $code = $this->c->code;
            $data = $data->toArray();
            $data['channelMerchantId'] = Tools::getHashId($data['channelMerchantId']);
            unset($data['created_at'], $data['updated_at'], $data['channelMerchantNo']);
            $data['param'] = self::getParams($data['param']);
            $desc = isset($code['channel'][$data['channel']]['paramDesc']) ? $code['channel'][$data['channel']]['paramDesc'] : '';
            return $response->withJson([
                'result' => $data,
                'success' => 1,
                'desc' => $desc,
            ]);
        }
    }

    /**
     * 修改支付宝账号信息
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     */
    public function update(Request $request, Response $response, $args)
    {
        $code = $this->c->code;
        $channel = $request->getParam('channel');
        $merchantNo = $request->getParam('channelMerchantNo');
        $merchantId = $request->getParam('channelMerchantId');
        $delegateDomain = $request->getParam('delegateDomain', '');
        $status = $request->getParam('status', '');
        $param = $request->getParam('param', '');
        $channel_param =  $code['channel'][$channel]['param'];
        $diff = $this->array_diff($channel_param, $param);
        $model = new ChannelMerchant();
        $data = $model->where('channelMerchantId', Tools::getIdByHash($merchantId))->first();
        $arr=json_decode(Tools::decrypt($data->param), true);
        $newArr=array_merge($arr,$diff);
        $param = Tools::encrypt(json_encode($newArr));
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            // $model->channelMerchantNo = $merchantNo;
            $actionBeforeData = $data->toJson();
            $data->param = $param;
            $data->delegateDomain = $delegateDomain;
            $data->platformNo = $merchantNo;
            $data->channel = 'alipay';
            $data->status=$status;
            $data->save();
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_CHANNEL_MERCHANT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $data->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            $model->refreshCache(['channelMerchantNo' => $merchantNo]);
            return $response->withJson([
                'result' => '修改成功',
                'success' => 1,
            ]);
        }
    }

    /**
     * 新增支付宝账号信息
     * @param Request $request
     * @param Response $response
     * @param $args
     * @return mixed
     * 1、动态生成一个上游商户号
     * 2、获取当前下游商户的对应的上游商户号的支付宝的费率配置（channel_merchant_rate表）
     * 3、新增一条费率信息
     * 4、把当前商户号和新增的下游商户号绑定起来
     * 4、获取当前下游商户支付宝的费率配置
     * 5、配置下游费率
     *
     */
    public function insertAlipayAccount(Request $request, Response $response, $args)
    {
        $code = $this->c->code;
        $channel = $request->getParam('channel');
        $delegateDomain = $request->getParam('delegateDomain', '');
        $param = $request->getParam('param', '');
        $channel_param =  $code['channel'][$channel]['param'];


        //生成上游商户号
        $merchantData = ChannelMerchant::orderBy('channelMerchantId', 'desc')->first();
        $newmerchantNo = $merchantData ? intval($merchantData->channelMerchantNo) + 1 : 10000000;

        $diff = $this->array_diff($channel_param, $param);
        $params=[
            'appAccount'=>'支付宝账号','appId'=>'商户号','appPrivateKey'=>'接口私钥','appPublicKey'=>'接口公钥','aliPublicKey'=>'阿里公钥'
        ];
        foreach ($params as $key=>$val) {
            if(!in_array('ipWhite',$diff)){
                $diff['ipWhite']=[];
            }
            if($diff[$key]==''){
                return $response->withJson([
                    'result' => $val.'不能为空',
                    'success' => 0,
                ]);
            }
        }
        $param = Tools::encrypt(json_encode($diff));

        //获取当前下游商户号的上游商户号
        $settlement = new MerchantChannelSettlement();
        $settlementData = $settlement->where('merchantNo', $_SESSION['merchantNo'])->where('channel', 'alipay')->first();

        if (empty($newmerchantNo)) {
            return $response->withJson([
                'result' => '商户号生成失败！',
                'success' => 0,
            ]);
        }

        if (empty($settlementData)) {
            return $response->withJson([
                'result' => '首次配置支付宝账号，请联系系统管理员！',
                'success' => 0,
            ]);
        }
        if (!empty($data)) {
            return $response->withJson([
                'result' => '数据已存在',
                'success' => 0,
            ]);
        } else {
            //先新增一个上游商户
            $model=new ChannelMerchant();
            $merArr=[
                'channelMerchantNo'=>$newmerchantNo,
                'param'=>$param,
                'delegateDomain'=>$delegateDomain,
                'platformNo'=>$newmerchantNo,
                'channel'=>'alipay'
            ];
            $newChannelMerchantId=$model->insertGetId($merArr);

            $model->refreshCache(['channelMerchantNo' => $newmerchantNo]);
            SystemAccountActionLog::insert([
                'action' => 'CREATE_CHANNEL_MERCHANT',
                'actionBeforeData' => '',
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);

//            $newChannelMerchantId='48';

            $dataCacheByChannel=$model->getCacheByChannelMerchantId($newChannelMerchantId);
            //新增相同商户的上游费率配置
            $result=$this->addRate($dataCacheByChannel['channelMerchantNo'],$settlementData->channelMerchantId,$newChannelMerchantId);

//            $result=true;
            if($result){
                //新增相同商户的下游费率配置

                $result2=$this->addRateMerchant($dataCacheByChannel['channelMerchantNo'],$settlementData->channelMerchantId,$newChannelMerchantId);

                if($result2){
                    return $response->withJson([
                        'result' => '添加成功！',
                        'success' => 1,
                    ]);
                }else{
                    return $response->withJson([
                        'result' => '添加下游配置失败！',
                        'success' => 0,
                    ]);
                }

            }
            return $response->withJson([
                'result' => '添加上游商户和上游配置失败！',
                'success' => 0,
            ]);

        }
    }

    public function addRate($newmerchantNo,$oldchannelMerchantId,$newChannelMerchantId){
        $merchantRateModel = new ChannelMerchantRate();

        $rateData=$merchantRateModel->where('channelMerchantId', $oldchannelMerchantId)->get()->first();

        $arr=[
            'channel'=>'alipay',
            'beginTime'=>$rateData->beginTime,
            'maxServiceCharge'=>$rateData->maxServiceCharge,
            'minServiceCharge'=>$rateData->minServiceCharge,
            'channelMerchantId'=>$newChannelMerchantId,
            'channelMerchantNo'=>$newmerchantNo,
            'payType'=>'D0Settlement',
            'productType'=>'Settlement',
            'rate'=>$rateData->rate,
            'fixed'=>$rateData->fixed,
            'rateType'=>$rateData->rateType,
        ];

        if (!empty($rateData)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
//                $merchantRateModel->where('channelMerchantId', $newChannelMerchantId)->delete();
                $merchantRateModel->insert($arr);
                $merchantRateModel->refreshCache(['channelMerchantId' => $newChannelMerchantId]);
                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_MERCHANT_RATE',
                    'actionBeforeData' => '',
                    'actionAfterData' => json_encode($merchantRateModel->getCacheByChannelMerchantId($newChannelMerchantId), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);

                $db->getConnection()->commit();
                return true;
            } catch (\Exception $e) {
                // $logger->debug("create失败" . $e->getMessage());
                $db->getConnection()->rollback();
               return false;
            }
        } else {
            return false;
        }

    }

    //把当前商户和新增的上游商户绑定起来并且新增相同商户的下游费率配置
    public function addRateMerchant($newmerchantNo,$oldchannelMerchantId,$newChannelMerchantId){
        //=======把当前商户和新增的上游商户绑定起来======start=====================================
        $merchantChannelModel = new MerchantChannelSettlement();
        $rateModel = new ChannelMerchantRate;

        $rateChannelData=$merchantChannelModel->where('merchantNo', $_SESSION['merchantNo'])->get()->first();
        $arr=[
            'merchantId'=>$rateChannelData->merchantId,
            'merchantNo'=>$rateChannelData->merchantNo,
            'channel'=>'alipay',
            'channelMerchantId'=>$newChannelMerchantId,
            'channelMerchantNo'=>$newmerchantNo,
            'settlementAccountType'=>$rateChannelData->settlementAccountType,
            'beginTime'=>$rateChannelData->beginTime,
            'endTime'=>$rateChannelData->endTime,
            'openOneAmountLimit'=>$rateChannelData->openOneAmountLimit,
            'oneMaxAmount'=>$rateChannelData->oneMaxAmount,
            'oneMinAmount'=>$rateChannelData->oneMinAmount,
            'openDayAmountLimit'=>$rateChannelData->openDayAmountLimit,
            'dayAmountLimit'=>$rateChannelData->dayAmountLimit,
            'openDayNumLimit'=>$rateChannelData->openDayNumLimit,
            'dayNumLimit'=>$rateChannelData->dayNumLimit,
            'priority'=>$rateChannelData->priority
        ];



        if (!empty($rateChannelData)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
//                $merchantChannelRateModel->where('merchantId', $rateChannelData->merchantId)->delete();
                $merchantChannelModel->insert($arr);
                $merchantChannelModel->refreshCache(['merchantId' =>$rateChannelData->merchantId]);

                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_MERCHANT_CHANNEL_SETTLEMENT',
                    'actionBeforeData' => '',
                    'actionAfterData' => json_encode($merchantChannelModel->getCacheByMerchantNo($_SESSION['merchantNo']), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);
                (new Amount)->init($rateChannelData->merchantId, $rateChannelData->merchantNo);
                $db->getConnection()->commit();

                return true;
            } catch (\Exception $e) {
                // $logger->debug("create失败" . $e->getMessage());
                $db->getConnection()->rollback();
                return false;
            }
        } else {
            return true;
        }

    }

    public function array_diff($arr, $arr2){
        if (!is_array($arr2)) {
            $arr2 = array();
        }
        foreach ($arr as $key => $record ) {
            if (!isset($arr2[$key]) && $key != 'gateway') {
                $arr2[$key] = '';
            }
        }
        return $arr2;
    }
}
