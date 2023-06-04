<?php

namespace App\Controllers\Merchant;

use App\Helpers\GoogleAuthenticator;
use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\MerchantAmount;
use App\Models\MerchantAccount;
use App\Models\MerchantChannelRecharge;
use App\Models\MerchantNotice;
use App\Models\MerchantTransformRate;
use App\Models\MerchantChannelSettlement;
use App\Models\PlatformTransformOrder;
use App\Models\Finance;
use App\Models\MerchantAccountActionLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends MerchantController
{
    public function head(Request $request, Response $response, $args)
    {
        $amountData = (new MerchantAmount)->getAmount($_SESSION['merchantId']);

        $model = new Merchant;
        $merchantId = $_SESSION['merchantId'];
        $merchantId && $model = $model->where('merchant.merchantId', $merchantId);
        /* $model = $model->where('merchant.merchantId', $_SESSION['merchantId']); */
        $date = date('Ymd');
        /* $date = '20190504'; */
        $model = $model->leftjoin('merchant_amount', 'merchant.merchantId', '=', 'merchant_amount.merchantId');
        $total = $model->count();
        $data = $model->selectRaw("
        merchant.merchantId,
        merchant.merchantNo,
        merchant.shortName,
        merchant_amount.updated_at,
        merchant.settlementType,
        (select sum(amount) from amount_pay where accountDate='{$date}' and amount_pay.merchantId = merchant.merchantId) as todayPayAmount,
        (select sum(serviceCharge) from amount_pay where accountDate='{$date}' and amount_pay.merchantId = merchant.merchantId) as todayPayServiceCharge,
        (select sum(amount) from amount_settlement where accountDate='{$date}' and amount_settlement.merchantId = merchant.merchantId) as todaySettlementAmount,
        (select sum(serviceCharge) from amount_settlement where accountDate='{$date}' and amount_settlement.merchantId = merchant.merchantId) as todaySettlementServiceCharge
        ")
            ->first();
        /* dump($model->toSql());exit; */
        $charge = 0.00;
        if (!empty($data)) {
            $charge = $data->todayPayServiceCharge + $data->todaySettlementServiceCharge;
        }

        return $this->c->view->render($response, 'merchant/head.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'settlementAmount' => !empty($amountData) ? sprintf("%.2f", $amountData['settlementAmount']) : '0.00',
            'accountBalance' => !empty($amountData) ? sprintf("%.2f", $amountData['accountBalance']) : '0.00',
            'availableBalance' => !empty($amountData) ? sprintf("%.2f", $amountData['availableBalance']) : '0.00',
            'freezeAmount' => !empty($amountData) ? sprintf("%.2f", $amountData['freezeAmount']) : '0.00',
            'merchantNo' => !empty($data) ? $data->merchantNo : '',
            'shortName' => !empty($data) ? $data->shortName : '',
            'settlementType' => !empty($data) ? $data->settlementType : '',
            'todayPayAmount' => !empty($data) ? sprintf("%.2f", $data->todayPayAmount) : '0.00',
            'todaySettlementAmount' => !empty($data) ? sprintf("%.2f", $data->todaySettlementAmount) : '0.00',
            'charge' => sprintf("%.2f", $charge),
            'menus' => $this->menus,
        ]);
    }

    public function getsignkey(Request $request, Response $response, $args)
    {
        $code = $request->getParam('code');
        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $code, 2);
        /* $checkResult = true; */

        $model = new Merchant();
        $merchantNo = $request->getParam('merchantNo');
        $data = Merchant::where('merchantNo', $merchantNo)->first();
        if ($checkResult) {

            if (empty($data)) {
                return $response->withJson([
                    'result' => '数据不存在',
                    'success' => 0,
                ]);
            }

            $_SESSION['googleAuthCheck'] = false;
            return $response->withJson([
                'result' => [
                    "signKey" => Tools::decrypt($data->signKey),
                ],
                'success' => 1,
            ]);
        } else {
            return $response->withJson([
                'success' => 0,
                'result' => '验证失败',
            ]);
        }

    }

    //商户余额转账
    public function transform(Request $request, Response $response, $args){
        $params = $request->getParams();
        //防止并发
        global $app;
        $redis = $app->getContainer()->redis;
        unset($params['_']);
        $lockRequest = md5(implode(',',$params));

        if($redis->get($lockRequest)) {
            return $response->withJson(['success' => 0, 'result' => '防并发，相同数据请1分钟后重试']);
        }
        $redis->setex($lockRequest, 60, 1);
        //
        $logger = $this->c->logger;
        $logger->debug("商户余额转账", $params);
        //验证谷歌验证码
        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $params['gcode'], 2);
//        $checkResult = true;
        if ($checkResult === false) {
            return $response->withJson(['success' => 0, 'result' => '谷歌验证码验证失败']);
        }
        $_SESSION['googleAuthCheck'] = false;

        if($_SESSION['merchantNo'] != $params['fromMerchantNo']){
            return $response->withJson(['success' => 0, 'result' => '商户信息有误']);
        }
        if($params['fromMerchantNo'] == $params['toMerchantNo']){
            return $response->withJson(['success' => 0, 'result' => '不能向同一个商户号发起余额转账']);
        }
        $model = new Merchant();
        $from_data = $model->where('merchantNo', $params['fromMerchantNo'])->first();
        $to_data = $model->where('merchantNo', $params['toMerchantNo'])->first();
        if (empty($from_data) || empty($to_data)) {
            return $response->withJson(['success' => 0, 'result' => '商户数据不存在']);
        }
        if(trim($params['shortName']) != $to_data->shortName){
            return $response->withJson(['success' => 0, 'result' => '商户名称不符合']);
        }

        //是否有同一个商户渠道
//        $cs_model = new MerchantChannelSettlement();
//        $rc_model = new MerchantChannelRecharge();
//        $from_channel = $cs_model->where('merchantNo', $params['fromMerchantNo'])->pluck('channelMerchantNo')->all();
//        $from_channel_1 = $rc_model->where('merchantNo', $params['fromMerchantNo'])->pluck('channelMerchantNo')->all();
//        $from_merge = array_unique(array_merge($from_channel, $from_channel_1));
//        $to_channel = $cs_model->where('merchantNo', $params['toMerchantNo'])->pluck('channelMerchantNo')->all();
//        $to_channel_1 = $rc_model->where('merchantNo', $params['toMerchantNo'])->pluck('channelMerchantNo')->all();
//        $to_merge = array_unique(array_merge($to_channel, $to_channel_1));
//        $inter_channel = array_intersect($from_merge, $to_merge);
//        if(empty($inter_channel)){
//            $logger->error("商户不存在相同的上游渠道号", ['from_channel'=>$from_merge, 'to_channel'=>$to_merge]);
//            return $response->withJson(['success' => 0, 'result' => '两个商户不存在相同的上游渠道号']);
//        }

        //验证密码
        $merchantAccount = new MerchantAccount;
        $merchantAccountData = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if (Tools::getHashPassword($params['paycode']) != $merchantAccountData->securePwd) {
            $logger->error('商户余额转账 支付密码错误，hash pwd:' . Tools::getHashPassword($params['paycode']) . '，secure pwd:' . $merchantAccountData->securePwd);
            return $response->withJson(['success' => 0, 'result' => "支付密码错误"]);
        }

        //获取费率
        $merchantRate = new MerchantTransformRate();
        $merchantRateData = $merchantRate->where(['merchantNo'=>$params['fromMerchantNo']])->first();
        $params['money'] = bcmul($params['money'], 100 ,0)/100;
        if($params['money'] < 0){
            return $response->withJson(['success' => 0, 'result' => "订单金额不能为负"]);
        }
        if(empty($merchantRateData)){
            $serviceCharge = 0;
        }else{
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData->toArray(), $params);
        }
        $merchantAmount = new MerchantAmount;
        $amountData = $merchantAmount->getAmount($_SESSION['merchantId']);
        if ($params['money'] + $serviceCharge > $amountData['availableBalance']) {
            $logger->error("商户余额转账 可用余额不足", ['orderAmount'=>$params['money'], 'serviceCharge'=>$serviceCharge, 'amountData'=>$amountData]);
            return $response->withJson(['success' => 0, 'result' => "商户可用余额不足"]);
        }

        try {
            $db = $this->c->database;
            $db->getConnection()->beginTransaction();
            $accountDate = Tools::getAccountDate($from_data->settlementTime);
            $merchantAmountLockData = $merchantAmount->where('merchantId', $_SESSION['merchantId'])->lockForUpdate()->first();//来源商户
            $toMerchantAmountLockData = $merchantAmount->where('merchantId', $to_data->merchantId)->lockForUpdate()->first();//目标商户
            $amountData = $merchantAmount->getAmount($_SESSION['merchantId'], $merchantAmountLockData->toArray(), $from_data->toArray());

            $platformOrderNo = Tools::getPlatformOrderNo('T');
//            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData->toArray(), $params);

            if ($params['money'] + $serviceCharge > $amountData['availableBalance']) {
                throw new \Exception("余额不足");
            }

            // 生成转换订单
            $platformTransformOrder = new PlatformTransformOrder();
            $transformData = [
                'fromMerchantId'=>$from_data->merchantId,
                'fromMerchantNo'=>$from_data->merchantNo,
                'toMerchantId'=>$to_data->merchantId,
                'toMerchantNo'=>$to_data->merchantNo,
                'platformOrderNo'=>$platformOrderNo,
                'orderAmount'=>$params['money'],
                'serviceCharge'=>$serviceCharge,
            ];
            $data = $platformTransformOrder->create($transformData);
            //来源商户
            $merchantAmountLockData->settlementAmount = $merchantAmountLockData->settlementAmount - $params['money'] - $serviceCharge;
            $merchantAmountLockData->save();
            //目标商户
            $toMerchantAmountLockData->settlementAmount = $toMerchantAmountLockData->settlementAmount + $params['money'];
            $toMerchantAmountLockData->save();

            Finance::insert([
                [
                    'merchantId' => $data->fromMerchantId,
                    'merchantNo' => $data->fromMerchantNo,
                    'platformOrderNo' => $platformOrderNo,
                    'amount' => $params['money'],
                    'balance' => $merchantAmountLockData->settlementAmount + $serviceCharge,
                    'financeType' => 'PayOut',
                    'accountDate' => $accountDate,
                    'accountType' => 'SettledAccount',
                    'sourceId' => $data->orderId,
                    'sourceDesc' => '商户余额转账（支出）',
                    'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                    'operateSource' => 'merchant',
                    'summary' => "{$params['money']} 【{$to_data->merchantNo} {$to_data->shortName}】",
                ],
                [
                    'merchantId' => $data->fromMerchantId,
                    'merchantNo' => $data->fromMerchantNo,
                    'platformOrderNo' => $platformOrderNo,
                    'amount' => $serviceCharge,
                    'balance' => $merchantAmountLockData->settlementAmount,
                    'financeType' => 'PayOut',
                    'accountDate' => $accountDate,
                    'accountType' => 'ServiceChargeAccount',
                    'sourceId' => $data->orderId,
                    'sourceDesc' => '结算手续费',
                    'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                    'operateSource' => 'merchant',
                    'summary' => "商户余额转账手续费",
                ],
                [
                    'merchantId' => $to_data->merchantId,
                    'merchantNo' => $to_data->merchantNo,
                    'platformOrderNo' => $platformOrderNo,
                    'amount' => $params['money'],
                    'balance' => $toMerchantAmountLockData->settlementAmount,
                    'financeType' => 'PayIn',
                    'accountDate' => $accountDate,
                    'accountType' => 'SettledAccount',
                    'sourceId' => $data->orderId,
                    'sourceDesc' => '商户余额转账（收入）',
                    'merchantOrderNo' => isset($data->merchantOrderNo) ? $data->merchantOrderNo : '',
                    'operateSource' => 'merchant',
                    'summary' => "{$params['money']} 【{$from_data->merchantNo} {$from_data->shortName}】",
                ]
            ]);

            $merchantAmountLockData->refreshCache(['merchantId' => $merchantAmountLockData->merchantId]);
            $toMerchantAmountLockData->refreshCache(['merchantId' => $toMerchantAmountLockData->merchantId]);
            $db->getConnection()->commit();
            return $response->withJson(['success' => 1, 'result' => "转账成功"]);
        }catch (\Exception $e){
            $logger->error('商户余额转账 Exception:' . $e->getMessage());
            $db->getConnection()->rollback();
            return $response->withJson(['success' => 0, 'result' => "转账失败"]);
        }
    }

    //消息提示
    public function tips(Request $request, Response $response, $args){
        // 获取用户最近成功修改登录密码和支付密码的时间
        $merchant_log = new MerchantAccountActionLog();
        $loginpwd_log = $merchant_log->where([['accountId', '=', $_SESSION['accountId']], ['action', '=', 'UPDATE_PASSWORD'], ['status', '=', 'Success']])->orderBy('id', 'desc')->first();
        $paypwd_log = $merchant_log->where([['accountId', '=', $_SESSION['accountId']], ['action', '=', 'UPDATE_PAY_PASSWORD'], ['status', '=', 'Success']])->orderBy('id', 'desc')->first();
        return $response->withJson([
            'success' => 0,
            'loginpwd_log' => empty($loginpwd_log) ? '无' : date('Y-m-d H:i:s',strtotime($loginpwd_log->created_at)),
            'paypwd_log' => empty($paypwd_log) ? '无' : date('Y-m-d H:i:s',strtotime($paypwd_log->created_at)),
        ]);
    }

    //获取消息公告
    public function getNotice(Request $request, Response $response, $args){
        $data=MerchantNotice::from('merchant_notice')
            ->whereRaw("FIND_IN_SET('{$_SESSION['merchantNo']}',recipient)")
            ->orWhere('type','default')
            ->where('status','published');
        $data = $data->orderBy('id', 'desc')->offset(0)->limit(10)->get(['id','title','content','published_time'])->toArray();

        return $response->withJson([
            'success'=>1,
            'result'=>$data
        ]);

    }
}
