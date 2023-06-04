<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2019/8/5
 * Time: 4:09
 */

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Models\Agent;
use App\Models\AgentIncomeLog;
use App\Models\AgentMerchantRelation;
use App\Models\AmountPay;
use App\Models\Finance;
use App\Models\Merchant;
use App\Models\Channel;
use App\Models\MerchantAccount;
use App\Models\MerchantAmount;
use App\Models\ChannelMerchant;
use App\Models\MerchantChannel;
use App\Models\MerchantChannelRecharge;
use App\Models\ChannelMerchantRate;
use App\Models\MerchantRate;
use App\Models\PlatformRechargeOrder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;
use App\Channels\ChannelProxy;
class RechargeOrderController extends MerchantController
{

    public function paychannel(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/recharge/paychannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function paychannelSearch(Request $request, Response $response, $args)
    {
        $merchant = new Merchant;
        $merchantData = [];
        $code = $this->c->code;
        $limit = (int) $request->getParam('limit', 20);
        $offset = (int) $request->getParam('offset', 0);
        $merchantNo = $_SESSION['merchantNo'];
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $channel = $request->getParam('channel');
        $shortName = $request->getParam('shortName');

        /*  $merchantNo && $model = $model->where('merchant_channel.merchantNo', $merchantNo);
         // $shortName && $model->where('shortName', $shortName);
         if (!empty($shortName)) {
             $data = $merchant->where('shortName', $shortName)->first();
             $data && $model = $model->where('merchant_channel.merchantId', $data->merchantId);
         }
         $channel && $model = $model->where('merchant_channel.channel', $channel);
         $channelMerchantNo && $model = $model->where('merchant_channel.channelMerchantNo', $channelMerchantNo);

         $total = $model::all()->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])->count();
         $data = MerchantChannel::selectRaw('merchant_channel.channel,merchant_channel.channelMerchantId,merchant_channel.channelMerchantNo,
         merchant_channel.merchantId,merchant_channel.merchantNo,GROUP_CONCAT(merchant_channel.payType) as payTypes,amount_pay.amount')
             ->leftjoin('amount_pay', function ($join) {
                 $join->on('amount_pay.channelMerchantId', '=', 'merchant_channel.channelMerchantId')
                     ->on('amount_pay.merchantId', '=', 'merchant_channel.merchantId');
                 // ->on('amount_pay.accountDate', '=', date('Ymd'));
             })
             ->where('amount_pay.accountDate', date('Ymd'))
             ->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])
             ->offset($offset)
             ->limit($limit)
             ->get();

         $data = $model->selectRaw('*,GROUP_CONCAT(merchant_channel.payType) as payTypes,(select amount from amount_pay
         where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
         and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = "' . date('Ymd') . '"
         ) as amount')
             ->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])
             ->offset($offset)
             ->limit($limit)
             ->get(); */
        $wheres = [];
        $value = [];
        $where[] = '1=1';
        $merchantNo && $where[] = "merchant_channel.merchantNo=?";
        $merchantNo && $value[] = $merchantNo;

        $shortName && $where[] = "merchant_channel.$shortName=?";
        $shortName && $value[] = $shortName;

        $channelMerchantNo && $where[] = "merchant_channel.channelMerchantNo=?";
        $channelMerchantNo && $value[] = $channelMerchantNo;

        $channel && $where[] = "merchant_channel.channel=?";
        $channel && $value[] = $channel;

        if (!empty($shortName)) {
            $data = $merchant->where('shortName', $shortName)->first();
            $shortName && $where[] = "merchant_channel.merchantId=?";
            $shortName && $value[] = $data->merchantId;
        }

        $whereStr = implode(' and ', $where);
        $total = \Illuminate\Database\Capsule\Manager::select("select count(*) from (select *,
        (select SUM(amount) from amount_pay
        where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
        and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = '" . date('Ymd') . "'
        ) as amount
        from merchant_channel
        WHERE {$whereStr}
        GROUP BY merchant_channel.merchantId, merchant_channel.channelMerchantId) a", $value);

        $value[] = $limit;
        $value[] = $offset;
        $data = \Illuminate\Database\Capsule\Manager::select("select * from (select *,
        (select SUM(amount) from amount_pay
        where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
        and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = '" . date('Ymd') . "'
        ) as amount,GROUP_CONCAT(merchant_channel.payType) as payTypes
        from merchant_channel
        WHERE {$whereStr}
        GROUP BY merchant_channel.merchantId, merchant_channel.channelMerchantId) a limit ? offset ?", $value);

        $rows = [];
        $total = current(current($total));
        // exit;
        foreach ($data ?? [] as $k => $v) {
            // $total = $v->count;
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);

            $payTypes = explode(',', $v->payTypes);
            $payTypeDescs = [];
            foreach ($payTypes ?? [] as $payType) {
                $payTypeDescs[] = $code['payType'][$payType];
            }
            $nv = [
                'channel' => $v->channel,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                'channelMerchantId' => Tools::getHashId($v->channelMerchantId),
                'channelMerchantNo' => $v->channelMerchantNo,
                'dayAmountCount' => $v->amount,
                "merchantNo" => $v->merchantNo,
                "setId" => Tools::getHashId($v->setId),
                "payTypeDescs" => $payTypeDescs,
                "payTypes" => $payTypes,
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

    public function insideRecharge(Request $request, Response $response, $args){

        $return = ['msg' => 'fail', 'result' => [], 'success' => 0,];

        $setId = $request->getParam('setId','');
        $amount = $request->getParam('amount','');
        $setId = Tools::getIdByHash($setId);
        $settlementChannel = MerchantChannel::where('setId', $setId)->first();
        if(!$settlementChannel){
            return $response->withJson([
                'msg' => '渠道不存在',
                'result' => [],
            ]);
        }
        $settlementChannel = $settlementChannel->toArray();

        $channelMerchantData = (new ChannelMerchant)->getCacheByChannelMerchantId($settlementChannel['channelMerchantId']);
        $channelConfig = isset($channelMerchantData['config']) ? json_decode($channelMerchantData['config'],true) : [];
        if(!$channelConfig){
            return $response->withJson([
                'msg' => "渠道充值配置为空",
                'result' => [],
            ]);
        }
        if(!isset($channelConfig['insideRecharge']) || !$channelConfig['insideRecharge']['open']){
            return $response->withJson([
                'msg' => "该渠道未配置充值",
                'result' => [],
            ]);
        }

        $rateConfig = $channelConfig['insideRecharge'];

        $rechargeMin = $rateConfig['rechargeMin'];
        $rechargeMax = $rateConfig['rechargeMax'];
        if($amount < $rechargeMin || $amount > $rechargeMax){
            return $response->withJson([
                'msg' => "充值金额在$rechargeMin - $rechargeMax",
                'result' => [],
            ]);
        }

        $class = 'App\Channels\Lib'."\\" . ucwords($settlementChannel['channel']);
        if(!class_exists( $class) && !method_exists($class,'getInsideRechargeOrder')){
            return $response->withJson([
                'msg' => '渠道不支持充值',
                'result' => [],
            ]);
        }
        $settlementRechargeOrder = new SettlementRechargeOrder();
        $settlementRechargeOrderN0 = 'R'.date('YmdHis') . rand(10000,999999);

        $settlementRechargeOrder->settlementRechargeOrderNo = $settlementRechargeOrderN0;
        $settlementRechargeOrder->merchantNo = $settlementChannel['merchantNo'];
        $settlementRechargeOrder->merchantId = $settlementChannel['merchantId'];
        $settlementRechargeOrder->channelMerchantId = $settlementChannel['channelMerchantId'];
        $settlementRechargeOrder->channelMerchantNo = $settlementChannel['channelMerchantNo'];
        $settlementRechargeOrder->orderAmount = $amount;
        $settlementRechargeOrder->realOrderAmount = $amount;
        $settlementRechargeOrder->serviceCharge = $rateConfig['xiayouFixed'] + ($amount * $rateConfig['xiayouPercent']);
        $settlementRechargeOrder->channelServiceCharge = $rateConfig['shangyouFixed'] + ($amount * $rateConfig['shangyouPercent']) ;
        $settlementRechargeOrder->channel = $settlementChannel['channel'];
        $settlementRechargeOrder->channelSetId = $settlementChannel['setId'];
        $settlementRechargeOrder->orderStatus = 'Transfered';
        $settlementRechargeOrder->type = 'insideRecharge';

        $res = $settlementRechargeOrder->save();
        if(!$res) return $response->withJson($return);
        $settlementRechargeOrder->setCacheByPlatformOrderNo($settlementRechargeOrderN0,$settlementRechargeOrder->toArray());

//        print_r($settlementRechargeOrder);exit;
        $res = (new ChannelProxy)->insideRecharge($settlementChannel['channelMerchantId'], $settlementRechargeOrder);
        if($res && $res['status'] == 'Success'){
            return $response->withJson([
                'msg' => '提交充值成功！',
                'success' => 1,
                'payUrl' => $res['payUrl'],
            ]);
        }else{
            return $response->withJson([
                'msg' => '提交充值失败！',
                'success' => 0,
                'payUrl' => '',
            ]);
        }


    }

    public function create(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/rechargeorder/create.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
//            'rechargeType' => $args['type'],
        ]);
    }

    public function doCreate(Request $request, Response $response, $args){

        $logger = $this->c->logger;
        $db = $this->c->database;
        $params = $request->getParams();
        $record['extra']['a'] = 'recharge';
        $record['extra']['i'] = Tools::getIp();
        $record['extra']['d'] = Tools::getIpDesc();
        $record['extra']['p'] = $params;
        $logger->info('发起充值：',$record);
        $validator = $this->c->validator->validate($request, [
//            'type' => Validator::in(['insideRecharge', 'outsideRecharge']),
            'setId' => Validator::noWhitespace()->notBlank()->notEmpty(),
            'orderReason' => Validator::noWhitespace()->notBlank()->notEmpty(),
            'orderAmount' => Validator::noWhitespace()->numeric()->notEmpty(),
            'applyPerson' => Validator::noWhitespace()->notEmpty(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('发起充值失败：',$validator->getErrors());
            return $response->withJson([
                'result' => "验证不通过:".json_encode($validator->getErrors()),
                'success' => 0,
            ]);
        }

        $merchantAccount = new MerchantAccount;
        $merchantAccountData = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if (Tools::getHashPassword($params['applyPerson']) != $merchantAccountData->securePwd) {
            $logger->error('支付密码错误，hash pwd:' . Tools::getHashPassword($params['applyPerson']) . '，secure pwd:' . $merchantAccountData->securePwd);
            return $response->withJson([
                'result' => "支付密码错误",
                'success' => 0,
            ]);
        }

        $model = new MerchantChannelRecharge();
        $setId = Tools::getIdByHash($params['setId']);
        $merchantChannelRecharge = $model->where('setId',$setId)->first();
        if(!$merchantChannelRecharge){
            $logger->error("充值渠道不存在:".$setId);
            return $response->withJson([
                'result' => "充值渠道不存在:".$setId,
                'success' => 0,
            ]);
        }

        $merchantRateModel = new MerchantRate();
        $merchantRateConfig = $merchantRateModel->where('merchantNo',$merchantChannelRecharge->merchantNo)
                                                ->where('status','Normal')
                                                ->where('productType','Recharge')
                                                ->where('payType',$merchantChannelRecharge->payType)
                                                ->first();
        if(!$merchantRateConfig){
            $logger->error("充值商户费率不存在:".$setId);
            return $response->withJson([
                'result' => "充值商户费率不存在:".$setId,
                'success' => 0,
            ]);
        }


        $channelMerchantRateModel = new ChannelMerchantRate();
        $channelRateConfig = $channelMerchantRateModel->where('channelMerchantNo',$merchantChannelRecharge->channelMerchantNo)
                                                        ->where('status','Normal')
                                                        ->where('productType','Recharge')
                                                        ->where('payType',$merchantChannelRecharge->payType)
                                                        ->first();


        if($merchantChannelRecharge->payChannelStatus != 'Normal'){
            $logger->error("充值渠道已关闭:".$setId);
            return $response->withJson([
                'result' => "充值渠道已关闭:".$setId,
                'success' => 0,
            ]);
        }
        if($merchantChannelRecharge->openTimeLimit){
            $now = date('H') . date('i');
            if($now < $merchantChannelRecharge->beginTime || $now > $merchantChannelRecharge->endTime){
                $logger->error("充值时间不在限定内，充值时间: ".$merchantChannelRecharge->beginTime . '-' . $merchantChannelRecharge->endTime);
                return $response->withJson([
                    'result' => "充值时间不在限定内，充值时间: ".$merchantChannelRecharge->beginTime . '-' . $merchantChannelRecharge->endTime,
                    'success' => 0,
                ]);
            }
        }


        if($merchantChannelRecharge->openOneAmountLimit){
            if($params['orderAmount'] < $merchantChannelRecharge->oneMinAmount || $params['orderAmount'] > $merchantChannelRecharge->oneMaxAmount){
                $logger->error("充值金额不在限定内，金额范围: ".$merchantChannelRecharge->oneMinAmount . '-' . $merchantChannelRecharge->oneMaxAmount);
                return $response->withJson([
                    'result' => "充值金额不在限定内，金额范围: ".$merchantChannelRecharge->oneMinAmount . '-' . $merchantChannelRecharge->oneMaxAmount,
                    'success' => 0,
                ]);
            }
        }

        if($merchantChannelRecharge->openDayAmountLimit){
            $dayAmount = PlatformRechargeOrder::where('created_at','>=',date('Y-m-d'))
                                                    ->where('created_at','<=',date('Y-m-d').' 23:59:59')
                                                    ->where('orderStatus','Success')
                                                    ->where('merchantId',$merchantChannelRecharge->merchantId)
                                                    ->where('channelMerchantId',$merchantChannelRecharge->channelMerchantId)
                                                    ->where('payType',$merchantChannelRecharge->payType)
                                                    ->value($db::raw("sum(realOrderAmount)"));
            if(($dayAmount + $params['orderAmount']) > $merchantChannelRecharge->dayAmountLimit){
                $logger->error("单日累计充值金额超出限制: 单日充值累计金额".$dayAmount . ',单日充值限额：' . $merchantChannelRecharge->dayAmountLimit);
                return $response->withJson([
                    'result' => "单日累计充值金额超出限制: 单日充值累计金额".$dayAmount . ',单日充值限额：' . $merchantChannelRecharge->dayAmountLimit,
                    'success' => 0,
                ]);
            }
        }

        if($merchantChannelRecharge->openDayNumLimit){
            $dayNum = PlatformRechargeOrder::where('created_at','>=',date('Y-m-d'))
                ->where('created_at','<=',date('Y-m-d').' 23:59:59')
                ->where('orderStatus','Success')
                ->where('merchantId',$merchantChannelRecharge->merchantId)
                ->where('channelMerchantId',$merchantChannelRecharge->channelMerchantId)
                ->where('payType',$merchantChannelRecharge->payType)
                ->count();
            if(($dayNum + 1) > $merchantChannelRecharge->dayNumLimit){
                $logger->error("单日累计充值笔数超出限制: 单日充值累计".$dayNum . ',单日充值限制：' . $merchantChannelRecharge->dayAmountLimit);
                return $response->withJson([
                    'result' => "单日累计充值笔数超出限制: 单日充值累计".$dayNum . ',单日充值限制：' . $merchantChannelRecharge->dayAmountLimit,
                    'success' => 0,
                ]);
            }
        }
        $class = 'App\Channels\Lib'."\\" . ucwords($merchantChannelRecharge['channel']);

        if(!class_exists( $class) || !method_exists($class,'getRechargeOrder')){
            $logger->error("渠道不支持充值方法");
            return $response->withJson([
                'result' => '渠道不支持充值方法',
                'success' => 0,
            ]);
        }

        $rechargeOrder = new PlatformRechargeOrder();
        $rechargeOrderN0 = 'R'.date('YmdHis') . rand(10000,999999);

        //代理手续费
        $agentId = AgentMerchantRelation::where('merchantId',$merchantChannelRecharge['merchantId'])->value('agentId');
        if($agentId) {
            $agentLog = new AgentIncomeLog();
            //代付订单类型只有一种
            $agentFee = $agentLog->getFee($agentId,$merchantChannelRecharge['merchantId'],$rechargeOrderN0,$params['orderAmount'],'recharge',$merchantChannelRecharge->payType);

            $agentName=Agent::where('id',$agentId)->value('loginName');
        }else {
            $agentFee = 0;
            $agentName='';
        }

        $rechargeOrder->platformOrderNo = $rechargeOrderN0;
        $rechargeOrder->merchantNo = $merchantChannelRecharge['merchantNo'];
        $rechargeOrder->merchantId = $merchantChannelRecharge['merchantId'];
        $rechargeOrder->channelMerchantId = $merchantChannelRecharge['channelMerchantId'];
        $rechargeOrder->channelMerchantNo = $merchantChannelRecharge['channelMerchantNo'];
        $rechargeOrder->orderAmount = $params['orderAmount'];
        $rechargeOrder->realOrderAmount = $params['orderAmount'];
        $rechargeOrder->serviceCharge = $merchantRateConfig->fixed + ($params['orderAmount'] * $merchantRateConfig->rate);
        $rechargeOrder->channelServiceCharge = $channelRateConfig->fixed + ($params['orderAmount'] * $channelRateConfig->rate);
        $rechargeOrder->channel = $merchantChannelRecharge['channel'];
        $rechargeOrder->channelSetId = $merchantChannelRecharge['setId'];
        $rechargeOrder->orderStatus = 'Transfered';
        $rechargeOrder->payType = $merchantChannelRecharge->payType;
        $rechargeOrder->orderReason = $params['orderReason'];
        $rechargeOrder->agentFee = $agentFee;
        $rechargeOrder->agentName = $agentName;

        $merchantRateConfigTemp['rateType'] = $merchantRateConfig->rateType;
        $merchantRateConfigTemp['rate'] = (string)$merchantRateConfig->rate;
        $merchantRateConfigTemp['fixed'] = (string)$merchantRateConfig->fixed;

        $channelRateConfigTemp['rateType'] = $channelRateConfig->rateType;
        $channelRateConfigTemp['rate'] = (string)$channelRateConfig->rate;
        $channelRateConfigTemp['fixed'] = (string)$channelRateConfig->fixed;
        $rechargeOrder->rateTemp = json_encode(['merchant'=>$merchantRateConfigTemp,'channel'=>$channelRateConfigTemp]);

        $res = $rechargeOrder->save();
        if(!$res){
            $logger->error("订单提交失败: ".$rechargeOrderN0);
            return $response->withJson([
                'result' => '订单提交失败！',
                'success' => 0,
            ]);
        }
        $rechargeOrder->setCacheByPlatformOrderNo($rechargeOrderN0,$rechargeOrder->toArray());

        $result=$rechargeOrder->toArray();
        $result['bankCode']=$params['bankCode'] ?? '';
        $res = (new ChannelProxy)->getRechargeOrder($result);
        $logger->info("充值渠道返回：",$res);
        if($res['status'] != 'Success' ){
            $logger->error("订单提交失败：",$res);
            return $response->withJson([
                'result' => $res['FailReason'] ? $res['FailReason'] : '订单提交失败！',
                'success' => 0,
            ]);
        }

        return $response->withJson([
            'result' => '订单提交成功！',
            'payUrl' => $res['payUrl'],
            'success' => 1,
        ]);

    }

    public function chooseChannel(Request $request, Response $response, $args){
//        $db = $this->c->database;
        $code = $this->c->code;
//        $db::enableQueryLog();
        $merchantNo = $_SESSION['merchantNo'];
        $model = new MerchantChannelRecharge;
        $channels = $model->where('merchantNo',$merchantNo)->where('paychannelStatus','Normal')
            ->selectRaw("setId,payType,channel")
            ->get()->toArray();
        if(!$channels) return [];
        $rechargeChannels = [];
        $payTypeCode = $code['payType'];
        foreach ($channels as $channel){
            $key = Tools::getHashId($channel['setId']);
            $value = $channel['channel'].'-'.$payTypeCode[$channel['payType']];
            array_push($rechargeChannels,['key'=>$key,'value'=>$value]);
        }
//        print_r($db::getQueryLog());

        return $response->withJson([
            'result' => ['channel'=>$rechargeChannels],
            'success' => 1,
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
        $model = new PlatformRechargeOrder();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        $orderStatus = $request->getParam('orderStatus');
        $orderPayType= $request->getParam('orderPayType');
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
        $orderPayType && $model = $model->where('payType', $orderPayType);
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
                "orderReason" => $v->orderReason,
                "orderStatus" => $v->orderStatus,
                "orderStatusDesc" => $code['recharge']['orderStatus'][$v->orderStatus] ?? null,
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType] ?? null,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]['shortName'] : null,
                "serviceCharge" => $v->serviceCharge,
                "channelServiceCharge" => $v->channelServiceCharge,
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

}