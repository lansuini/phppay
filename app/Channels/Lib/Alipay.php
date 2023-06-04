<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverExpectedCondition;

class Alipay extends Channels
{
    private $RESPONSE_SUFFIX = "_response";
    private $SIGN_NODE_NAME = "sign";
    protected $supportRechargeType = [
        'AlipayEBank' => 'getAlipayEBankRechargeOrder',
    ];
    public static $driver = '';

    public function getRechargeOrder($orderData){

        $return = ['status'=>'Fail','payUrl'=>'','failReason'=>'','orderNo'=>''];

        if(!isset($this->supportRechargeType[$orderData['payType']])){
            $return['status'] = 'Exception';
            $return['failReason'] = "渠道不支持{$orderData['payType']}";
            return $return;
        }

        $rechargeAction = $this->supportRechargeType[$orderData['payType']];
        try{
            $return['payUrl'] = $this->$rechargeAction($orderData);
            $return['status'] = 'Success';
            $this->logger->debug("充值发起成功：", $orderData);
        }catch (\Exception $e){
            $this->logger->debug("充值发起失败：", $orderData);
            $return['status'] = 'Exception';
        }
        return $return ;
    }

    //AlipayEBank  支付宝网银充值
    public function getAlipayEBankRechargeOrder($rechargeData){
        global $app;
        $cache = $app->getContainer()->redis;
        if($cache->get('lastAlipayEbankOrder')){
            return '系统繁忙，请稍后再试';
        }

        $channelMerchantNo = $rechargeData['channelMerchantNo'];
        $key = 'chrome_driver_'.$rechargeData['channelMerchantNo'];
        $ext = ['payType'=>'alipayEbank','sessionId'=>'','isLogin'=>0];
        $codeDst = '/data/image/dzpay/'.$channelMerchantNo.'/qrcode.png';

        return  $this->getSpecialPayUrl($rechargeData, $ext);

        try{
            $caches = $cache->hgetAll($key);

            if($caches){
//                echo '使用缓存session:';

                $sessionId = $cache->hget($key,'sessionId');
                $ext['sessionId'] = $sessionId;
                $driver = RemoteWebDriver::createBySessionID($sessionId);
                $driver->get('https://business.alipay.com/user/home');
                $currentUrl = $driver->getCurrentURL();
                if(strpos($currentUrl,'login') != false ){
                    $driver->manage()->window()->maximize();    //将浏览器最大化
                    $driver->takeScreenshot($codeDst);  //截取当前网页，该网页有我们需要的验证码
                    //J-qrcode-body,J-barcode-container，J-qrcode-img
                    $element = $driver->findElement(WebDriverBy::id('J-qrcode-body'));
                    Tools::generateVcodeIMG($element->getLocation(), $element->getSize(),$codeDst);
                }else{
                    $ext['isLogin'] = 1;
                    $cache->expire($key,600);
                }
                return  $this->getCBRechargeUrl($rechargeData, $ext);

            }else{
//                echo '开始创建新应用：';
                $host = 'http://localhost:4444/wd/hub';        // selenium-server地址，此处传入默认值

                $waitSeconds = 5;
                $options = new ChromeOptions();
//            $options->setBinary('/usr/bin/chromedriver');  //指定浏览器程序路径
                $options->addArguments(
                    array(
                        '--no-sandbox',                        // 解决DevToolsActivePort文件不存在的报错
//                '--whitelisted-ips',
                        'window-size=1080x1920',               // 指定浏览器分辨率
//                        '--disable-gpu',                       // 谷歌文档提到需要加上这个属性来规避bug
                        '--hide-scrollbars',                   // 隐藏滚动条, 应对一些特殊页面
                        'blink-settings=imagesEnabled=true',  // 不加载图片, 提升速度
                        '--headless',                          // 浏览器不提供可视化页面
//                        '--disable-dev-shm-usage',
                    )
                );

                $capabilities = DesiredCapabilities::chrome();
                $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

                $driver = RemoteWebDriver::create($host, $capabilities,10000);
//                $driver->get('https://auth.alipay.com/login/index.htm?goto=https://mrchportalweb.alipay.com/user/home');
                $driver->get('https://business.alipay.com/user/home');
                $currentUrl = $driver->getCurrentURL();
                if(strpos($currentUrl,'login') === false ){
                    $ext['isLogin'] = 1;
                    $cache->expire($key,600);
                    return  $this->getCBRechargeUrl($rechargeData, $ext);
                }
                $js = "window.scrollTo(0,document.body.scrollHeight)";	//滚动至底部
                //$js = "window.scrollBy(0,100000000);";  //也可以把值设大一点，达到底部的效果
                $driver->executeScript($js);
                $driver->manage()->window()->maximize();    //将浏览器最大化
                $driver->takeScreenshot($codeDst);  //截取当前网页，该网页有我们需要的验证码

                //J-qrcode-body,J-barcode-container，J-qrcode-img
                $element = $driver->findElement(WebDriverBy::id('J-qrcode-body'));
                Tools::generateVcodeIMG($element->getLocation(), $element->getSize(),$codeDst);

                $driver->wait($waitSeconds)->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(
                        WebDriverBy::id('J-loginMethod-tabs')
                    )
                );

                $cookies = $driver->manage()->getCookies();
                $sessionId = $driver->getSessionID();

                echo "获取应用session：".$sessionId;
                echo "获取应用cookie：";
//                print_r($cookies);
                $cache->hset($key,'sessionId',$sessionId);
                $cache->hset($key,'cookies',serialize($cookies));
                $cache->hset($key,'checkcode','');
                $cache->expire($key,600);
                $ext['sessionId'] = $sessionId;
                return  $this->getSpecialPayUrl($rechargeData, $ext);
            }
        }catch (\Exception $e){
            echo $e->getMessage();
            return print_r($e->getMessage());
        }
//        RemoteWebDriver::getAllSessions();
//        $numProducts = $cache->getItem('stats.num_products');
//        $numProducts->set(4711);
//        $cache->save($numProducts);
//        $numProducts = $cache->getItem('stats.num_products');
//        if (!$numProducts->isHit()) {
//            echo '元素不存在';
//        }
//        $total = $numProducts->get();


    }

    protected function getResponseDataKey($apiMethodName)
    {
        return str_replace(".", "_", $apiMethodName) . $this->RESPONSE_SUFFIX;
    }

    protected function getBizContent($arrBizContent)
    {
        $strBizContent = "{";

        foreach ($arrBizContent ?? [] as $key => $val) {
            $strBizContent .= '"' . $key . '":"' . $val . '",';
        }

        if (strlen($strBizContent) > 1) {
            $strBizContent = \substr($strBizContent, 0, -1);
        }

        $strBizContent .= "}";

        return $strBizContent;
    }

    protected function getSignContent($params)
    {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === Tools::checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset($k, $v);
        return $stringToBeSigned;
    }

    protected function createRsaSign($params, $rsaPrivateKey)
    {
        $sign = '';
        $content = $this->getSignContent($params);
        $key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($rsaPrivateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($content, $sign, $key, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        return $sign;
    }

    protected function getGatewayUrl($params)
    {
        $requestUrl = $this->gateway . "?";
        foreach ($params as $key => $val) {
            $requestUrl .= "$key=" . urlencode($val) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);

        return $requestUrl;
    }

    protected function getHeaderParams()
    {
        return ['content-type: application/x-www-form-urlencoded;charset=UTF-8'];
    }

    protected function parseJsonSign($strResponse)
    {
        if (!Tools::isJsonString($strResponse)) {
            return '';
        }

        $arr = json_decode($strResponse, true);
        if (is_array($arr) && isset($arr[$this->SIGN_NODE_NAME])) {
            return $arr[$this->SIGN_NODE_NAME];
        }

        return '';
    }

    protected function parseJsonSignSourse($strResponse, $apiMethodName)
    {
        if (!Tools::isJsonString($strResponse)) {
            return '';
        }

        $respDataKey = $this->getResponseDataKey($apiMethodName);
        $dataKeyIndex = strpos($strResponse, $respDataKey);
        if ($dataKeyIndex === false) {
            return '';
        }

        $signDataStartIndex = $dataKeyIndex + strlen($respDataKey) + 2;
        $signIndex = strrpos($strResponse, '"' . $this->SIGN_NODE_NAME . '"');
        if ($signIndex === false) {
            return '';
        }
        // 签名前-逗号
        $signDataEndIndex = $signIndex - 1;
        $indexLen = $signDataEndIndex - $signDataStartIndex;
        if ($indexLen < 0) {
            return '';
        }

        return substr($strResponse, $signDataStartIndex, $indexLen);
    }

    protected function checkSign($strResponse, $apiMethodName)
    {
        $originalSign = $this->parseJsonSign($strResponse);
        $signData = $this->parseJsonSignSourse($strResponse, $apiMethodName);
        if ($originalSign == '' || $signData == '') {
            return false;
        }

        $pubKey = $this->params['aliPublicKey'];
        if(isset($this->params['alipayRootCert']) && $this->params['alipayRootCert']){
            $ss = array_filter(explode('-----BEGIN CERTIFICATE-----',$pubKey));
            $correctKey = '';
            foreach ($ss as &$s){
                $s = '-----BEGIN CERTIFICATE----- '. $s;
                $one = $this->getCorrectCert($s);
                $correctKey .= "\n";
                $correctKey .= $one;
            }
        }else{
            $correctKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        }
        return (openssl_verify($signData, base64_decode($originalSign), $correctKey, OPENSSL_ALGO_SHA256) === 1);
    }

    public function getSettlementOrder($orderData)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $apiMethodName = 'alipay.fund.trans.toaccount.transfer';

        $sysParams = [
            'app_id' => $this->params['appId'],
            'method' => $apiMethodName,
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s"),
            'version' => '1.0',
        ];

        if(isset($this->params['alipayRootCert']) && $this->params['alipayRootCert']){
            $apiMethodName = 'alipay.fund.trans.uni.transfer';
            $sysParams['method'] = $apiMethodName;
            $sysParams['app_cert_sn'] = $this->get_app_cert_sn();
            $sysParams['alipay_root_cert_sn'] = $this->get_root_cert_sn();
            $bizContent = [
                'out_biz_no' => $orderData['platformOrderNo'],
                'trans_amount' => sprintf("%.2f", $orderData['orderAmount']),
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene' => 'DIRECT_TRANSFER',
                'payee_info' =>[
                    'identity' => Tools::decrypt($orderData['bankAccountNo']),
                    'identity_type' =>'ALIPAY_LOGON_ID',
                    'name' =>$orderData['bankAccountName']
                ],
                'remark' => '单笔转账',

            ];
            $sysParams['biz_content'] = json_encode($bizContent);
            $sysParams['sign'] = $this->createRsaSign($sysParams, $this->params['appPrivateKey']);
            $requestUrl = $this->getGatewayUrl($sysParams);
            $this->logger->debug('向上游发起代付请求：' . $requestUrl, $sysParams);
            $rsp = Requests::post($requestUrl, $this->getHeaderParams(), $sysParams, ['timeout' => $this->timeout, 'verify'=>false]);
        }else{
            $bizContent = [
                'out_biz_no' => $orderData['platformOrderNo'],
                'payee_type' => 'ALIPAY_LOGONID',
                'payee_account' => Tools::decrypt($orderData['bankAccountNo']),
                'payee_real_name' => $orderData['bankAccountName'],
                'amount' => $orderData['orderAmount'],
//            'remark' => $orderData['orderReason'],
            ];
            $reason = explode("|", $orderData['orderReason']);
            if(count($reason)>1){
                $bizContent['payer_show_name'] = $reason[0];//付款方姓名
                $bizContent['remark'] = $reason[1];//转账备注
            }else{
                $payername = \App\Models\Merchant::where('merchantNo', $orderData['merchantNo'])->value('payerName');
                if(!empty($payername)){
                    $bizContent['payer_show_name'] = $payername;//付款方姓名
                }
                $bizContent['remark'] = $orderData['orderReason'];//转账备注
            }
            $apiParams = [
                'biz_content' => $this->getBizContent($bizContent),
            ];
            $sysParams['sign'] = $this->createRsaSign(\array_merge($apiParams, $sysParams), $this->params['appPrivateKey']);
            $requestUrl = $this->getGatewayUrl($sysParams);
            $this->logger->debug('向上游发起代付请求：' . $requestUrl, $apiParams);
            $rsp = Requests::post($requestUrl, $this->getHeaderParams(), $apiParams, ['timeout' => $this->timeout, 'verify'=>false]);
        }


        $this->logger->debug('上游代付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Success';
            $output['failReason'] = '第三方请求失败：[status_code]:' . $rsp->status_code;
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        $strResp = trim($rsp->body);
//        $strResp='{"alipay_fund_trans_uni_transfer_response":{"code":"40004","msg":"Business Failed","sub_code":"PAYMENT_INFO_INCONSISTENCY","sub_msg":"两次请求商户单号一样，但是参数不一致"},"alipay_cert_sn":"aabed12b3c973e99dbfb38102ccdf028","sign":"PcVCej20DsO7LPwBPHnr7u7AdukPAcOKxwCdb1MRWLMX9b/U4WxkhROd1iJa0RgS0r9WpBIz2NhSLf+t+Gpfh6v+JEfc7yHXhoqVDfBK4DAM2/yEPxnWaxauHNIkjMu2hzQRmCV3jcIQC2PW0pRJMvBRPfRAznttKK1ivCM/WOT0bf+jYMXRhYrKV4WZtkIW3RGqxJ2cQbRMPnT7O/erWsPD/P8pFx1iag/c9rCmL0VSPaP57w5GFtG6A16KkEylXR4i6qxJPNv+TdPL2mxtWXEJMjrY7zcNjR76VphPZaaTax/7q1x4WmPnKUJTYZVLA3zeqVGaWt6Nxtz7h1azBg=="}';
        if (!$this->checkSign($strResp, $apiMethodName)) {
//            $output['status'] = 'Success';
//            $output['failReason'] = '返回数据验签失败';
//            $output['pushChannelTime'] = date('YmdHis');
//
//            return $output;
        }
        $arrResp = json_decode($strResp, true);
        $respDataKey = $this->getResponseDataKey($apiMethodName);
        //直接返回转账成功
        if (isset($arrResp[$respDataKey]['code']) && $arrResp[$respDataKey]['code'] == '10000') {
            $output['status'] = 'DirectSuccess';
            $output['orderNo'] = $arrResp[$respDataKey]['order_id'] ?? '';
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        //账号异常不进入下次代付轮询中
//        if ((isset($arrResp[$respDataKey]['sub_code']) && in_array($arrResp[$respDataKey]['sub_code'],['PAYER_STATUS_ERROR','PAYER_BALANCE_NOT_ENOUGH']))) {
        if ((isset($arrResp[$respDataKey]['sub_code']) && in_array($arrResp[$respDataKey]['sub_code'],['PAYER_STATUS_ERROR']))){
            $output['status'] = 'Exception';
            $output['orderNo'] = $arrResp[$respDataKey]['order_id'] ?? '';
            $output['failReason'] = '支付宝账号异常';
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        //需要查询确认，{"code":"20000","msg":"Service Currently Unavailable","sub_code":"aop.SYSTEM_ERROR","sub_msg":"系统繁忙"}
        if ((isset($arrResp[$respDataKey]['code']) && $arrResp[$respDataKey]['code'] == '20000')
            || (isset($arrResp[$respDataKey]['code']) && isset($arrResp[$respDataKey]['sub_code']) && $arrResp[$respDataKey]['code'] == '40004' && strtoupper($arrResp[$respDataKey]['sub_code']) == 'SYSTEM_ERROR')) {
            $output['status'] = 'Success';
            $output['orderNo'] = $arrResp[$respDataKey]['order_id'] ?? '';
            $output['failReason'] = '代付订单需要查询确认是否成功';
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        //转账失败
        $output['status'] = 'Fail';
        $output['failReason'] = '代付失败,' . 'code:' . ($arrResp[$respDataKey]['code'] ?? '') . ',sub_code:' . ($arrResp[$respDataKey]['sub_code'] ?? '') . ',sub_msg:' . ($arrResp[$respDataKey]['sub_msg'] ?? '');
        $output['orderNo'] = $arrResp[$respDataKey]['order_id'] ?? '';
        $output['pushChannelTime'] = date('YmdHis');

        return $output;
    }

    public function querySettlementOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $apiMethodName = 'alipay.fund.trans.order.query';

        $sysParams = [
            'app_id' => $this->params['appId'],
            'method' => $apiMethodName,
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA2',
            'timestamp' => '2021-11-19 15:28:04',
            'version' => '1.0',
        ];
        if(isset($this->params['alipayRootCert']) && $this->params['alipayRootCert']){
            $apiMethodName = 'alipay.fund.trans.common.query';
            $sysParams['method'] = $apiMethodName;
            $sysParams['app_cert_sn'] = $this->get_app_cert_sn();
            $sysParams['alipay_root_cert_sn'] = $this->get_root_cert_sn();
            $bizContent = [
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene'    => 'DIRECT_TRANSFER',
                'out_biz_no' => $platformOrderNo,
            ];
            $sysParams['biz_content'] = json_encode($bizContent);
            $sysParams['sign'] = $this->createRsaSign($sysParams, $this->params['appPrivateKey']);
            $requestUrl = $this->getGatewayUrl($sysParams);
            $this->logger->debug('向上游发起代付查询请求：' . $requestUrl, $sysParams);
            $rsp = Requests::post($requestUrl, $this->getHeaderParams(), $sysParams, ['timeout' => $this->timeout]);
        }else{
            $bizContent = [
                'out_biz_no' => $platformOrderNo,
            ];
            $apiParams = [
                'biz_content' => $this->getBizContent($bizContent),
            ];

            $sysParams['sign'] = $this->createRsaSign(\array_merge($apiParams, $sysParams), $this->params['appPrivateKey']);
            $requestUrl = $this->getGatewayUrl($sysParams);
            $this->logger->debug('向上游发起代付查询请求：' . $requestUrl, $apiParams);
            $rsp = Requests::post($requestUrl, $this->getHeaderParams(), $apiParams, ['timeout' => $this->timeout]);
        }


        $this->logger->debug('上游代付查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body;

            return $output;
        }

        $strResp = trim($rsp->body);
//        if (!$this->checkSign($strResp, $apiMethodName)) {
//            $output['status'] = 'Execute';
//            $output['failReason'] = '返回数据验签失败：' . $strResp;
//
//            return $output;
//        }

        $arrResp = json_decode($strResp, true);
        $respDataKey = $this->getResponseDataKey($apiMethodName);
        //订单成功
        if (isset($arrResp[$respDataKey]['code']) && $arrResp[$respDataKey]['code'] == '10000' && isset($arrResp[$respDataKey]['status']) && strtoupper($arrResp[$respDataKey]['status']) == 'SUCCESS') {
            $output['status'] = 'Success';
            $output['orderNo'] = $arrResp[$respDataKey]['order_id'] ?? '';
            $output['failReason'] = '';

            return $output;
            //订单失败
        } else if (isset($arrResp[$respDataKey]['code']) && $arrResp[$respDataKey]['code'] == '10000' && isset($arrResp[$respDataKey]['status']) && strtoupper($arrResp[$respDataKey]['status']) == 'FAIL') {
            $output['status'] = 'Fail';
            $output['orderNo'] = $arrResp[$respDataKey]['order_id'] ?? '';
            $output['failReason'] = '代付失败，' . ($arrResp[$respDataKey]['fail_reason'] ?? '');

            return $output;
        } else if (isset($arrResp[$respDataKey]['code']) && $arrResp[$respDataKey]['code'] == '40004' && $arrResp[$respDataKey]['sub_code'] == 'ORDER_NOT_EXIST'){//转账订单号不存在
            $output['status'] = 'Fail';
            $output['orderNo'] = '';
            $output['failReason'] = '代付失败，' . ($arrResp[$respDataKey]['sub_msg'] ?? '').',请重新提交！';

            return $output;
        }

        //否则仍需要确认
        $output['status'] = 'Execute';
        return $output;
    }

    public function queryBalance()
    {
        $output = ['status' => 'Success', 'balance' => 0, 'failReason' => ''];
        if(!isset($this->params['pid'])) {
            return $output;
        }

        $apiMethodName = 'alipay.fund.account.query';

        $sysParams = [
            'app_id' => $this->params['appId'],
            'method' => $apiMethodName,
            'format' => 'JSON',
            'charset' => 'UTF-8',
            'sign_type' => 'RSA2',
            'timestamp' => date("Y-m-d H:i:s"),
            'version' => '1.0',
        ];
        if(isset($this->params['alipayRootCert']) && $this->params['alipayRootCert']){
            $sysParams['app_cert_sn'] = $this->get_app_cert_sn();
            $sysParams['alipay_root_cert_sn'] = $this->get_root_cert_sn();
            $bizContent = [
                'alipay_user_id' => $this->params['pid'],
                'account_type' => 'ACCTRANS_ACCOUNT',
            ];
            $sysParams['biz_content'] = json_encode($bizContent);
            $sysParams['sign'] = $this->createRsaSign($sysParams, $this->params['appPrivateKey']);
            $requestUrl = $this->getGatewayUrl($sysParams);
            $this->logger->debug('向上游发余额查询请求：' . $requestUrl, $sysParams);
            $rsp = Requests::post($requestUrl, $this->getHeaderParams(), $sysParams, ['timeout' => $this->timeout]);
        }else{
            $bizContent = [
                'alipay_user_id' => $this->params['pid'],
                'account_type' => 'ACCTRANS_ACCOUNT',
            ];
            $apiParams = [
                'biz_content' => $this->getBizContent($bizContent),
            ];

            $sysParams['sign'] = $this->createRsaSign(\array_merge($apiParams, $sysParams), $this->params['appPrivateKey']);

            $requestUrl = $this->getGatewayUrl($sysParams);
            $this->logger->debug('向上游发起余额查询请求：' . $requestUrl, $apiParams);
            $rsp = Requests::post($requestUrl, $this->getHeaderParams(), $apiParams, ['timeout' => $this->timeout]);
        }

        $this->logger->debug('上游余额查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body;

            return $output;
        }

        $strResp = trim($rsp->body);

        $arrResp = json_decode($strResp, true);
        $output['status'] = 'Success';
        $output['balance'] = $arrResp['alipay_fund_account_query_response']['available_amount'] ?? 0;
        $output['failReason'] = '余额查询成功';
        return $output;
    }

    /**
     * 从应用公钥证书中提取序列号
     */
    private function get_app_cert_sn(){
        $cert = $this->getCorrectCert($this->params['appPublicKey']);
        $ssl = openssl_x509_parse($cert);
        $SN = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    private function getCorrectCert($cert=''){
        if(!$cert){
            return '';
        }
        $certs = array_merge(array_filter(explode(' ',$cert)));
        $count = count($certs);
        $certHead = $certs[0] . ' ' .$certs[1];
        unset($certs[0],$certs[1]);
        $certBack = $certs[$count-2] . ' ' .$certs[$count-1];
        unset($certs[$count-2],$certs[$count-1]);

        $certs = implode("\n",$certs);
        return "$certHead\n" . $certs . "\n$certBack";
    }

    private function array2string($array){
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }
    /**
     * 提取根证书序列号
     */
    public function get_root_cert_sn(){
        $cert = $this->params['alipayRootCert'];
        $array = explode("-----END CERTIFICATE-----", $cert);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $cert = $this->getCorrectCert($array[$i] . "-----END CERTIFICATE-----");
            $ssl[$i] = openssl_x509_parse($cert);
            if(strpos($ssl[$i]['serialNumber'],'0x') === 0){
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumber']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $SN = $SN . "_" . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }

    private function hex2dec($hex)
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }

}
