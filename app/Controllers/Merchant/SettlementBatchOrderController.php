<?php

namespace App\Controllers\Merchant;

use App\Helpers\Tools;
use App\Logics\MerchantLogic;
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
use App\Models\BlackUserSettlement;
use App\Models\SettlementRechargeOrder;
use App\Models\SystemAccountActionLog;
use App\Queues\SettlementFetchExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;
use App\Channels\Lib\Yilian;
use App\Channels\ChannelProxy;
use App\Helpers\GoogleAuthenticator;

class SettlementBatchOrderController extends MerchantController
{


    public function create(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'merchant/settlementbatchorder.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
            'downBanklUrl' => '/resource/bankBatchSelltementTmpl.csv',
            'downAlipaylUrl' => '/resource/alipayBatchSettlementTmpl.csv',
        ]);
    }

    public function getBankCode(Request $request, Response $response, $args){
        $cardNo = $request->getParam('cardNo');
        return $response->withJson([
            'result' =>  MerchantLogic::getBankCodeByCardNo($cardNo),
            'success' => 1,
        ]);
    }

    //文件上传方式的批量代付
    public function doCreate(Request $request, Response $response, $args)
    {
        $orderReason = $request->getParam('orderReason');
        $applyPerson = $request->getParam('applyPerson');
        $googleAuth = $request->getParam('googleAuth');
        $file = $request->getUploadedFiles();
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

        $logger->debug("商户后台批量代付请求");
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '文件不能为空',
                'success' => 0,
            ]);
        }
        $fname = $_FILES['file']['name'];
        if (substr($fname,strrpos($fname,'.')) != '.csv') {
            return $response->withJson([
                'result' => '请上传CSV文件',
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
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $googleAuth, 2);

        if(!$checkResult) {
            $logger->error("谷歌验证码错误，请重新输入 ！");
            return $response->withStatus(200)->withJson([
                'result' => '谷歌验证码错误，请重新输入！',
                'success' => 2,
            ]);
        }

        $merchantAccount = new MerchantAccount;
        $merchantAccountData = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if (Tools::getHashPassword($applyPerson) != $merchantAccountData->securePwd) {
            $logger->error('支付密码错误，hash pwd:' . Tools::getHashPassword($applyPerson) . '，secure pwd:' . $merchantAccountData->securePwd);
            return $response->withJson([
                'result' => "验证不通过",
                'success' => 0,
            ]);
        }

//        if ($request->getParam('uploadNotify') != 1) {//风控消息
//            $warning = new \App\Logics\WarningLogic($this->c);
//            $notify_params = [
//                'merchantId' => $_SESSION['merchantId'],//商户id
//                'merchantNo' => $_SESSION['merchantNo'],//商户号
//                'accountName' => '',//支付宝姓名
//                'accountNo' => '',//支付宝账户
//                'orderAmount' => '',//出款金额
//                'settlement_type' => '文件上传方式的批量出款',//出款类型
//            ];
//            $warning->system_minutes($notify_params);
//            $notify_return = $warning->merchant_minutes($notify_params);
//            if($notify_return['code'] != 'SUCCESS'){
//                return $response->withStatus(200)->withJson([
//                    'result' => $notify_return['msg'] . ',请谨慎操作！',
//                    'success' => 3,
//                ]);
//            }
//        }

        $key = 'doDoCreate' . ':' . $_SESSION['merchantNo'] ;
        if($this->c->redis->get($key)){
            $logger->error('文件上传批量代付操作频繁');
            return $response->withJson([
                'result' => "文件上传批量代付操作频繁",
                'success' => 0,
            ]);
        }
        $this->c->redis->setex($key,60,1);

//        setlocale(LC_ALL,array('zh_CN.gbk','zh_CN.gb2312','zh_CN.gb18030','en_US.utf8'));
        $csv = new \ParseCsv\Csv();
        $csv->fields = [0, 1, 2,3, 4, 5, 6, 7, 8];
        $csv->use_mb_convert_encoding = true;
        $csv->auto($file['file']->file);
        $data = $csv->data;
        if(!$data){
            return $response->withJson([
                'result' => "无数据",
                'success' => 0,
            ]);
        }
        $c = count($data[0]);
        if(!in_array($c,[4,9])){
            return $response->withJson([
                'result' => "请按模板文件上传",
                'success' => 0,
            ]);
        }
        $d = [   //创建订单以防报错
          'orderReason' => '',
          'bankAccountName' => '',
          'bankAccountNo' => '',
          'bankCode' => '',
          'bankName' => '',
          'city' => '',
          'province' => '',
        ];
        $channels = $this->c->code['channel'];
        $bankCode = array_keys($this->c->code['bankCode']);
        $success = 0;
        $error = '';
        foreach ($data as $val) {
            if($c == 4){  //支付宝批量代付     0=》支付宝账号   1=》支付宝姓名   2=》代付金额   3=》代付原因
                $d['aliAccountNo'] = $val[0];
                $d['aliAccountName'] = iconv('gbk','utf-8',$val[1]);
                $d['orderAmount'] = $val[2];
                $val[3] = iconv('gbk','utf-8',$val[3]);
                $d['orderReason'] = $val[3] ? $val[3] : $orderReason;
                $res = $this->aliSettlement($request,$d,$channels);
            }else{
                $d['bankAccountName'] = iconv('gbk','utf-8',$val[0]);
                $d['bankAccountNo'] = $val[1];
                $d['bankCode'] = $val[3];
                $d['orderAmount'] = $val[4];
                $d['province'] = iconv('gbk','utf-8',$val[5]);
                $d['city'] = iconv('gbk','utf-8',$val[6]);
                $d['orderReason'] = iconv('gbk','utf-8',$val[8]);
                $res = $this->bankSettlement($request,$d,$channels,$bankCode);
            }
            if($res == 'SUCCESS') $success++;
            if(!in_array($res,['SUCCESS','E0000'])) {
                $error = $res;
                break; //直接中断批量代付
            }
        }
        $s = count($data);
        $msg = '批量代付，总条数:' . $s . '，成功代付条数:' . $success;
        $logger->error($msg);
        $resultS=isset($this->c->code['status'][$error]) ? $this->c->code['status'][$error].'  '.$msg : $msg;

        return $response->withJson([
            'result' => $resultS,
            'success' => 1,
        ]);
    }

    //文本填写方式批量代付
    public function inputDoCreate(Request $request, Response $response, $args){
        $logger = $this->c->logger;
        $logger->debug("商户后台文本方式方式批量代付：".$_SESSION['merchantNo'], $request->getParams());
        $params =$request->getParam('data');
        $params=json_decode($params,true);
        $orderReason = $request->getParam('orderReason');
        $applyPerson = $request->getParam('applyPerson');
        $googleAuth = $request->getParam('googleAuth');
        $type = $request->getParam('type','');
        $channels = $this->c->code['channel'];
        $bankCode = array_keys($this->c->code['bankCode']);
        $logger = $this->c->logger;


        if(!$_SESSION['googleAuthSecretKey']){
            $logger->error("请绑定谷歌验证码后再操作！！");
            return $response->withStatus(200)->withJson([
                'result' => '请绑定谷歌验证码后再操作！',
                'success' => 2,
            ]);
        }

        $secret = Tools::decrypt($_SESSION['googleAuthSecretKey']);
        $checkResult = (new GoogleAuthenticator)->verifyCode($secret, $googleAuth, 2);

        if(!$checkResult) {
            $logger->error("谷歌验证码错误，请重新输入 ！");
            return $response->withStatus(200)->withJson([
                'result' => '谷歌验证码错误，请重新输入！',
                'success' => 2,
            ]);
        }

        $merchantAccount = new MerchantAccount;
        $merchantAccountData = $merchantAccount->where('accountId', $_SESSION['accountId'])->first();
        if (Tools::getHashPassword($applyPerson) != $merchantAccountData->securePwd) {
            $logger->error('支付密码错误，hash pwd:' . Tools::getHashPassword($applyPerson) . '，secure pwd:' . $merchantAccountData->securePwd);
            return $response->withJson([
                'result' => "支付密码错误",
                'success' => 0,
            ]);
        }

//        if ($request->getParam('alipayNotify') != 1) {//风控消息
//            $warning = new \App\Logics\WarningLogic($this->c);
//            $notify_params = [
//                'merchantId' => $_SESSION['merchantId'],//商户id
//                'merchantNo' => $_SESSION['merchantNo'],//商户号
//                'accountName' => '',//支付宝姓名
//                'accountNo' => '',//支付宝账户
//                'orderAmount' => '',//出款金额
//                'settlement_type' => '文本填写方式的批量出款',//出款类型
//            ];
//            $warning->system_minutes($notify_params);
//            $notify_return = $warning->merchant_minutes($notify_params);
//            if($notify_return['code'] != 'SUCCESS'){
//                return $response->withStatus(200)->withJson([
//                    'result' => $notify_return['msg'] . ',请谨慎操作！',
//                    'success' => 3,
//                ]);
//            }
//        }

        $key = 'inputDoCreate' . ':' . $_SESSION['merchantNo'] ;
        if($this->c->redis->get($key)){
            $logger->error('文本批量代付操作频繁');
            return $response->withJson([
                'result' => "文本批量代付操作频繁",
                'success' => 0,
            ]);
        }
        $this->c->redis->setex($key,60,1);

        $success=0;
        foreach ( $params as $item) {
            $item['orderReason']=$orderReason;
            $item['applyPerson']=$applyPerson;

            if($type!='alipay'){
                $res=$this->bankSettlement($request,$item,$channels,$bankCode);
            }else{
                $res=$this->aliSettlement($request,$item,$channels);
            }
            if($res == 'SUCCESS') $success++;
            if(!in_array($res,['SUCCESS','E0000'])) {
                $error = $res;
                break; //直接中断批量代付
            }
        }

        $s = count($params);
        $msg = '批量代付，总条数:' . $s . '，成功代付条数:' . $success;
        $logger->error($msg);

        $resultS=isset($this->c->code['status'][$error]) ? $this->c->code['status'][$error].'  '.$msg : $msg;

        return $response->withJson([
            'result' => $resultS,
            'success' => 1,
        ]);

    }

    private function aliSettlement($request,$data,$channels)
    {

        $code = 'SUCCESS';
        $logger = $this->c->logger;
        $db = $this->c->database;

        $logger->pushProcessor(function ($record) use ($data) {
            $record['extra']['a'] = 'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = 'batchOrderAliSettlement';
            $record['extra']['p'] = $data;
            return $record;
        });

        $logger->debug("商户后台支付宝批量代付请求");

        $validator = $this->c->validator->validate($data, [
            'aliAccountNo' => Validator::noWhitespace()->notBlank(),
            'aliAccountName' => Validator::noWhitespace()->notBlank(),
            'orderReason' => Validator::noWhitespace()->notBlank(),
            'orderAmount' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('valid', $validator->getErrors());
            $code = 'E0000';
        }


        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $logger->error("未有该商户信息");
                $code = 'E2001';
            } else if (!$merchantData['openAliSettlement']) {
                $logger->error("未开户支付宝代付");
                $code = 'E2007';
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $logger->error("未有该商户信息");
                $code = 'E2001';
            } else if (isset($merchantData['openManualSettlement']) && !$merchantData['openManualSettlement']) {
                $logger->error("商户未开通手动代付");
                $code = 'E2202';
            }
        }

        if ($code == 'SUCCESS') {
            if ($merchant->isExceedDaySettleAmountLimit($data['orderAmount'], $merchantData)) {
                $logger->error("超过商户单日累计代付总金额限制");
                $code = 'E2107';
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
            $orderAmount = bcmul($data['orderAmount'] , 100 ,0)/100;
            $params['orderAmount'] = $orderAmount;
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $data, 'Settlement');
            if ($data['orderAmount'] + $serviceCharge > $amountData['availableBalance']) {
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
            $merchantChannelConfig = $merchantChannel->fetchConfig($_SESSION['merchantNo'], $merchantChannelData, $settlementType, $data['orderAmount'], $data['aliAccountNo'], $channels, "ALIPAY");
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug("merchantChannelSettelement fetchConfig失败", $merchantChannelData);
            }
        }


        if ($code == 'SUCCESS') {
            $blackUserSettlement = new BlackUserSettlement();
            $isblackUserExists = $blackUserSettlement->checkBlackUser('ALIPAY',$data['aliAccountName'],$data['aliAccountNo']);
            if($isblackUserExists){
                $code = 'E2201';
                $logger->error("代付请求：代付黑名单用户！");
            }
        }

        if ($code == 'SUCCESS' && getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT')) {

            //风控限制
            $settlementRecordKeys = $this->c->redis->redis_keys('settlement:'.$data['aliAccountName'].':' . "*");
            if($settlementRecordKeys){
                if(count($settlementRecordKeys) >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_COUNT')){
                    $code = 'E2108';
                    $logger->error("代付请求：1小时内同一出款人笔数超限！");
                }
                $temp = 0;
                foreach ($settlementRecordKeys as $settlementRecordKey){
                    $temp += $this->c->redis->get($settlementRecordKey);
                }
                if($temp + $data['orderAmount'] >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_AMOUNT')){
                    $code = 'E2109';
                    $logger->error("代付请求：1小时内同一出款人金额超限！");
                }
            }
        }

        if ($code == 'SUCCESS') {
            // $channelMerchantRate = new ChannelMerchantRate;
            // $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

            try {
                $db->getConnection()->beginTransaction();
                $accountDate = Tools::getAccountDate($merchantData['settlementTime']);
                $merchantAmount = new MerchantAmount;
                $merchantAmountLockData = $merchantAmount->where('merchantId', $merchantData['merchantId'])->lockForUpdate()->first();
                $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], $merchantAmountLockData->toArray(), $merchantData);

                $platformOrderNo = Tools::getPlatformOrderNo('S');
                $data['orderAmount'] = $orderAmount;
                $data['merchantNo'] = $_SESSION['merchantNo'];
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $data, 'Settlement');
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
                    '商户后台批量发起',
                    $data
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

                $this->c->redis->setex('settlement:'.$data['aliAccountName'].':'.$platformOrderNo,60*60,$data['orderAmount']);

            } catch (\Exception $e) {
//                print_r($e->getMessage());exit;
                $logger->error('Exception:' . $e->getMessage());
                $code = 'E2105';
            }
        }

        return $code;
    }

    private function bankSettlement($request,$data,$channels,$bankCode)
    {
        //是否是支付宝代付到银行卡
        $alipayToBank = false;

        $code = 'SUCCESS';
        $logger = $this->c->logger;
        $db = $this->c->database;

        $logger->pushProcessor(function ($record) use ($data) {
            $record['extra']['a'] = $_SESSION['merchantNo'].'settlement';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = 'batchOrderBankSettlement';
            $record['extra']['p'] = $data;
            return $record;
        });

        $logger->debug("商户后台银行卡批量代付请求");

        $validator = $this->c->validator->validate($data, [
            'bankCode' => Validator::in($bankCode)->notBlank(),
            'bankAccountNo' => Validator::noWhitespace()->notBlank(),
            'bankAccountName' => Validator::notBlank(),
            'province' => Validator::notBlank(),
            'city' => Validator::notBlank(),
            'orderReason' => Validator::noWhitespace()->notBlank(),
            'orderAmount' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error($_SESSION['merchantNo'].' valid', $validator->getErrors());
            $code = 'E0000';
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $logger->error($_SESSION['merchantNo']."未有该商户信息");
                $code = 'E2001';
            } else if (!$merchantData['openSettlement']) {
                $logger->error($_SESSION['merchantNo']."未有开户银行卡代付");
                $code = 'E2002';
            }
        }

        if ($code == 'SUCCESS') {
            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantData)) {
                $logger->error($_SESSION['merchantNo']."未有该商户信息");
                $code = 'E2001';
            } else if (isset($merchantData['openManualSettlement']) && !$merchantData['openManualSettlement']) {
                $logger->error($_SESSION['merchantNo']."商户未开通手动代付");
                $code = 'E2202';
            }
        }


        if ($code == 'SUCCESS') {
            if ($merchant->isExceedDaySettleAmountLimit($data['orderAmount'], $merchantData)) {
                $logger->error($_SESSION['merchantNo']."超过商户单日累计代付总金额限制");
                $code = 'E2107';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantRate = new MerchantRate();
            $merchantRateData = $merchantRate->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantRateData)) {
                $logger->error($_SESSION['merchantNo']."代付请求：商户未配置费率");
                $code = 'E2006';
            }
        }

        if ($code == 'SUCCESS') {
            $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], '', $merchantData);
            $data['orderAmount'] = bcmul($data['orderAmount'] , 100,0) / 100;
            $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $data, 'Settlement');
            if ($data['orderAmount'] + $serviceCharge > $amountData['availableBalance']) {
                $logger->error($_SESSION['merchantNo']."代付余额不足");
                $code = 'E2105';
            }
        }

        if ($code == 'SUCCESS') {
            $merchantChannel = new MerchantChannelSettlement();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($_SESSION['merchantNo']);
            if (empty($merchantChannelData)) {
                $logger->error($_SESSION['merchantNo']."代付请求：未配置商户代付通道");
                $code = 'E2003';
            }
        }

        if ($code == 'SUCCESS') {
            if($data['bankCode'] != 'ALIPAY'){//判断银行是否维护
                $bankModel = new Banks();
                if($bankModel->is_open($data['bankCode']) === false){
                    $logger->error($_SESSION['merchantNo']."代付银行维护中");
                    $code = 'E2110';
                }
            }
        }

        if ($code == 'SUCCESS') {
            $settlementType = '';
            $merchantChannelConfig = $merchantChannel->fetchConfig($_SESSION['merchantNo'], $merchantChannelData, $settlementType, $data['orderAmount'], $data['bankAccountNo'], $channels, $data['bankCode']);
            $b = $data['bankCode'];
            if(in_array($b,array_keys(Merchant::$aliBanks)) && empty($merchantChannelConfig) && isset($merchantData['openAliSettlement']) && $merchantData['openAliSettlement']){     //若无任何可代付到银行卡的上游，读取支付宝代付银行卡配置
                $alipayToBank = true;
                $merchantChannelConfig = $merchantChannel->fetchConfig($_SESSION['merchantNo'], $merchantChannelData, $settlementType, $data['orderAmount'], $data['bankAccountNo'], $channels, 'ALIPAY');
            }
            if (empty($merchantChannelConfig)) {
                $code = 'E2003';
                $logger->debug($_SESSION['merchantNo']."merchantChannelSettelement fetchConfig失败", $merchantChannelData);
            }
        }

        if ($code == 'SUCCESS') {
            $blackUserSettlement = new BlackUserSettlement();
            $isblackUserExists = $blackUserSettlement->checkBlackUser('EBANK',$data['bankAccountName'],$data['bankAccountNo']);
            if($isblackUserExists){
                $code = 'E2201';
                $logger->error("代付请求：代付黑名单用户！");
            }
        }

//        if ($code == 'SUCCESS' && getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT')) {
//
//            //风控限制
//            $settlementRecordKeys = $this->c->redis->redis_keys('settlement:'.$data['bankAccountName'].':' . "*");
//            if($settlementRecordKeys){
//                if(count($settlementRecordKeys) >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_COUNT')){
//                    $code = 'E2108';
//                    $logger->error("代付请求：1小时内同一出款人笔数超限！");
//                }
//                $temp = 0;
//                foreach ($settlementRecordKeys as $settlementRecordKey){
//                    $temp += $this->c->redis->get($settlementRecordKey);
//                }
//                if($temp + $data['orderAmount'] >= getenv('MERCHANT_MANUAL_SETTLEMENT_LIMIT_AMOUNT')){
//                    $code = 'E2109';
//                    $logger->error("代付请求：1小时内同一出款人金额超限！");
//                }
//            }
//        }

        if ($code == 'SUCCESS') {
            try {
                $db->getConnection()->beginTransaction();
                $accountDate = Tools::getAccountDate($merchantData['settlementTime']);
                $merchantAmount = new MerchantAmount;
                $merchantAmountLockData = $merchantAmount->where('merchantId', $merchantData['merchantId'])->lockForUpdate()->first();
//                $orderAmount = intval($request->getParam('orderAmount') * 100) / 100;
                $amountData = (new MerchantAmount)->getAmount($merchantData['merchantId'], $merchantAmountLockData->toArray(), $merchantData);

                $platformOrderNo = Tools::getPlatformOrderNo('S');
                $data['merchantNo'] = $_SESSION['merchantNo'];
                $serviceCharge = $merchantRate->getServiceCharge($merchantRateData, $data, 'Settlement');
                if ($serviceCharge === null) {
                    throw new \Exception("getServiceCharge异常");
                }

                if ($data['orderAmount'] + $serviceCharge > $amountData['availableBalance']) {
                    throw new \Exception("余额异常");
                }

                $PlatformSettlementOrder = new PlatformSettlementOrder();
                if(!$merchantData['openAutoSettlement'] || (getenv('AUTO_SETTLEMENT_AMOUNT',0) && $data['orderAmount'] < getenv('AUTO_SETTLEMENT_AMOUNT',0)))  $settlementType = 'manualSettlement';
                $data = $PlatformSettlementOrder->create($request,
                    $platformOrderNo,
                    $data['orderAmount'],
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
                    '商户后台批发银行卡代付发起',
                    $data
                );

                $merchantAmountLockData->settlementAmount = $merchantAmountLockData->settlementAmount - $data['orderAmount'] - $serviceCharge;
                // $merchantAmountLockData->settledAmount = $merchantAmountLockData->settledAmount + $orderAmount + $serviceCharge;
                // $merchantAmountLockData->todaySettlementAmount = $merchantAmountLockData->todaySettlementAmount + $orderAmount + $serviceCharge;
                // $merchantAmountLockData->todayServiceCharge = $merchantAmountLockData->todayServiceCharge + $serviceCharge;
                $merchantAmountLockData->save();

                Finance::insert([
                    [
                        'merchantId' => $data->merchantId,
                        'merchantNo' => $data->merchantNo,
                        'platformOrderNo' => $platformOrderNo,
                        'amount' => $data['orderAmount'],
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
                }elseif (!$merchantData['openAutoSettlement'] || (getenv('AUTO_SETTLEMENT_AMOUNT',0) && $data['orderAmount'] < getenv('AUTO_SETTLEMENT_AMOUNT',0))){
                    //手动处理代付
                    $this->c->redis->incr('cacheSettlementKey');
                }else {
                    (new SettlementFetchExecutor)->push(0, $platformOrderNo);
                }
                $merchantAmountLockData->refreshCache(['merchantId' => $merchantAmountLockData->merchantId]);

                (new Merchant)->incrCacheByDaySettleAmountLimit($data->merchantNo, intval($data->orderAmount * 100));

                $db->getConnection()->commit();

                $this->c->redis->setex('settlement:'.$data['bankAccountName'].':'.$platformOrderNo,60*60,$data['orderAmount']);

            } catch (\Exception $e) {
                $logger->error($_SESSION['merchantNo'].'Exception:' . $e->getMessage());
                $db->getConnection()->rollback();
                $code = 'E9001';
            }
        }
        return $code;
    }

}
