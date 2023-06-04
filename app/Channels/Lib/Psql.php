<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use App\Models\PlatformSettlementOrder;
use Requests;

class Psql extends Channels
{
    protected $supportRechargeType = [
        'EnterpriseEBank' => 'getEpEBankRechargeOrder',
        'PersonalEBank' => 'getPsEBankRechargeOrder',
        'PersonalEBankDNA' => 'getPsEBankDnaRechargeOrder',
    ];
    protected $exchangeBankCode = [
        'PAB'=>'SDB',
        'BCOM'=>'BOCO',
        'GDB'=>'CGB',
    ];
    //发起代付
    public function getSettlementOrder($orderData)
    {
        //代付默认成功，即使请求500状态也有可能在第三方生成订单
        $output = ['status' => 'Success', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];

        //请求表单初始化
        $form = [
            'version'    => '1.0',
            'nodeId'     => $this->params['nodeId'],
            'orgId'      => $this->params['appAccount'],
            'orderTime'  => date('YmdHis'),
            'txnType'    => 'T30201',
            'signType'   => 'RSA2',
            'charset'    => 'UTF-8',
            'bizContext' => '',
            'sign'       => '',
            'reserve1'   => '66',
        ];
        if(in_array($orderData['bankCode'],['PAB','BCOM','GDB'])){
            $orderData['bankCode'] = $this->exchangeBankCode[$orderData['bankCode']];
        }

        $model=new PlatformSettlementOrder;
        $orderInfo=$model->where('platformOrderNo',$orderData['platformOrderNo'])->first();
        if(!$orderInfo){
            $this->logger->debug('订单异常：讯宝代付表查询信息：' . $orderData['platformOrderNo']);
            $output = ['status' => 'Fail', 'orderNo' => $orderData['platformOrderNo'], 'failReason' => '订单异常', 'orderAmount' => $orderData['orderAmount']];
            return $output;
        }

        // 业务参数，不同类型有差异，需对应修改
        $bizContext = array(
            'outTradeNo'  => $orderData['platformOrderNo'],
            'totalAmount' =>sprintf("%.2f",$orderData['orderAmount']), //元，限额 150~20000

            'payeeBank'    => $orderData['bankCode'],
            'payeeAcc'     => Tools::decrypt($orderData['bankAccountNo']),
            'payeeName'    => $orderData['bankAccountName'],
            'privateFlag'  =>'S',//G：对公，S：对私
            'currency'  =>'CNY',//G：对公，S：对私
            'chargeType'   =>'1',//1：外扣，从手续费账号扣,2：内扣，从订单金额扣
            'notifyUrl'   => '',
            'remark' => '结算',

//            'userId'  => rand(10, 99),
            //'deviceInfo'  => '',
            //'appName'     => '',
            //'appId'       => '',
//            'feeRate'     => '0.5', //根据需求修改
            //'dfFee'       => '2',
            'reserve1'    => '66'
        );
        $this->logger->debug('讯宝代付请求订单参数：' . $orderData['platformOrderNo'], $bizContext);
        // 1. 业务参数 json 编码
        $bizContextJson = json_encode($bizContext);
        // 2. 业务参数签名
        $rsaPrivateKey = $this->getRsaPrivateKey($this->params['rsaPrivateKey']);
        $bizContextSign = $this->rsaSHA1Sign($bizContextJson, $rsaPrivateKey);
        // 3. 业务参数加密
        $bizContextAESEncrypt = $this->AESEncrypt($bizContextJson, $this->params['aesKey']);
        // 4. 回填表单
        $form['sign']       = $bizContextSign;
        $form['bizContext'] = $bizContextAESEncrypt;

        $gateway = $this->params['gateway'].'/payProcess';
//        $gateway = 'https://120.78.196.14/testPay';
        // 5. 发送请求
        $this->logger->debug('向上游psql发起代付请求：' . $gateway, $form);
        $response = $this->postForm($gateway, $form);
        $this->logger->debug('psql代付回复：'. $orderData['platformOrderNo'] . $response);
        $output['orderAmount'] = $orderData['orderAmount'];
        $output['pushChannelTime'] = date('YmdHis');
        // 解析响应 json
        $response = json_decode($response, TRUE);
        if($response && isset($response['retCode'])){
            $output['failReason'] = $response['retMsg'];
            $output['orderNo'] = $response['tradeNo'];
            return $output;
        }

        // 业务参数解密
        $bizContextAESDecrypt = $this->AESDecrypt($response['bizContext'], $this->params['aesKey']);
        $this->logger->debug('psql代付请求结果详情：'. $orderData['platformOrderNo'] . $bizContextAESDecrypt);
//        print_r($bizContextAESDecrypt);
//        // 验签
//        $rsaPublicKey = $this->getRsaPublicKey($this->params['rsaPublicKey']);
//        $verify = $this->rsaSHA1Verify($bizContextAESDecrypt, $response['sign'], $rsaPublicKey);
//        print_r($response);
//        var_dump($response);

        $output['orderNo'] = $orderData['platformOrderNo'];
        return $output;
    }

    //查询代付订单
    public function querySettlementOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];

        //支付网关 URL
        $gateway = $this->params['gateway'].'/payProcess';

        //请求表单初始化
        $form = array(
            'version'    => '1.0',
            'nodeId'     => $this->params['nodeId'],
            'orgId'      => $this->params['appAccount'],
            'orderTime'  => date('YmdHis'),
            'txnType'    => 'Q30101',
            'signType'   => 'RSA2',
            'charset'    => 'UTF-8',
            'bizContext' => '',
            'sign'       => '',
            'reserve1'   => '55',
        );


        // 业务参数，不同类型有差异，需对应修改
        $bizContext = array(
            'outTradeNo'  => $platformOrderNo,
            'reserve1' => '55',
        );

        $this->logger->debug('psql代付查询请求：' . $platformOrderNo, $bizContext);
        // 1. 业务参数 json 编码
        $bizContextJson = json_encode($bizContext);

        // 2. 业务参数签名
        $rsaPrivateKey = $this->getRsaPrivateKey($this->params['rsaPrivateKey']);
        $bizContextSign = $this->rsaSHA1Sign($bizContextJson, $rsaPrivateKey);
        // 3. 业务参数加密
        $bizContextAESEncrypt = $this->AESEncrypt($bizContextJson, $this->params['aesKey']);

        // 4. 回填表单
        $form['sign']       = $bizContextSign;
        $form['bizContext'] = $bizContextAESEncrypt;


        // 5. 发送请求
        $this->logger->debug('psql查询请求：' . $gateway, $form);
        $response = $this->postForm($gateway, $form);
        $this->logger->debug('psql查询回复：'. $platformOrderNo . $response);
        // 解析响应 json
        $response = json_decode($response, TRUE);
        if(!$response){
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方请求异常' ;

            return $output;
        }

        // 业务参数解密
        $bizContextAESDecrypt = $this->AESDecrypt($response['bizContext'], $this->params['aesKey']);
        $this->logger->debug('psql查询结果详情：' . $platformOrderNo . $bizContextAESDecrypt);
        // 验签
        $rsaPublicKey = $this->getRsaPublicKey($this->params['rsaPublicKey']);
        $verify = $this->rsaSHA1Verify($bizContextAESDecrypt, $response['sign'], $rsaPublicKey);
        if(!$verify){
            $output['status'] = 'Execute';
            $output['failReason'] = '验签失败';
            return $output;
        }

        $result = json_decode($bizContextAESDecrypt,true);
        $output['orderNo'] = $platformOrderNo;

        if($result && $result['retCode'] == 'RC0000'){
            $output['status'] = 'Success';
            $output['failReason'] = '代付成功: ' . $result['retCode'];

            return $output;
        }

        //RC0008-交易不存在，RC0009-订单重复，RC0042-余额不足
        if($result && in_array($result['retCode'],['RC0003','RC0008','RC0009','RC0042'])){
            $output['status'] = 'Fail';
            $msg=isset($result['retMsg'])?$result['retMsg']:'';
            $output['failReason'] = '代付失败: ' . $result['retCode'].'错误信息：'. $msg;

            return $output;
        }


        //否则仍需要确认
        $output['status'] = 'Execute';
        return $output;
    }

    public function queryBalance()
    {
        $output = ['status' => 'Success', 'balance' => 0, 'failReason' => ''];
        //支付网关 URL
        $gateway = $this->params['gateway'] . '/payProcess';
        //请求表单初始化
        $form = array(
            'version'    => '1.0',
            'nodeId'     => $this->params['nodeId'],
            'orgId'      => $this->params['appAccount'],
            'orderTime'  => date('YmdHis'),
            'txnType'    => 'Q00201',
            'signType'   => 'RSA2',
            'charset'    => 'UTF-8',
            'bizContext' => '',
            'sign'       => '',
            'reserve1'   => '1',
        );

        // 业务参数，不同类型有差异，需对应修改
        $bizContext = array(
            'outTradeNo'  => 20210802115932,
            'reserve1' => '1',
        );


        // 1. 业务参数 json 编码
        $bizContextJson = json_encode($bizContext);

        // 2. 业务参数签名
        $rsaPrivateKey = $this->getRsaPrivateKey($this->params['rsaPrivateKey']);
        $bizContextSign = $this->rsaSHA1Sign($bizContextJson, $rsaPrivateKey);
        // 3. 业务参数加密
        $bizContextAESEncrypt = $this->AESEncrypt($bizContextJson, $this->params['aesKey']);

        // 4. 回填表单
        $form['sign']       = $bizContextSign;
        $form['bizContext'] = $bizContextAESEncrypt;

        $this->logger->debug('向上游发起余额查询请求：' . $gateway, $form);
        // 5. 发送请求
        $response = $this->postForm($gateway, $form);
        $this->logger->debug('上游余额查询回复：:' . $response);

        // 解析响应 json
        $response = json_decode($response, TRUE);
        if(!$response || $response['code'] != 'SUCCESS'){
            $output = ['status' => 'Fail', 'balance' => 0, 'failReason' => $response && $response['msg'] ? $response['msg'] : ''];
            return $output;
        }
        // 业务参数解密
        $bizContextAESDecrypt = $this->AESDecrypt($response['bizContext'], $this->params['aesKey']);
        $this->logger->debug('上游余额查询回复：:' . $bizContextAESDecrypt);
//        // 验签
//        $rsaPublicKey = $this->getRsaPublicKey($this->params['rsaPublicKey']);
//        $verify = $this->rsaSHA1Verify($bizContextAESDecrypt, $response['sign'], $rsaPublicKey);
//        var_dump($verify);exit;

        $arrResp = json_decode($bizContextAESDecrypt, true);
        $output['balance'] = $arrResp['avAccBal'] ?? 0;
        $output['failReason'] = '余额查询成功';
        return $output;
    }

    //代付回调
//    public function doSettlementCallback($orderData, $request)
//    {
//        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];
//        $param = file_get_contents("php://input");
//        $param = trim($param);
//        if (!Tools::isJsonString($param)) {
//            $output['status'] = 'Fail';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '回调返回参数格式错误';
//
//            return $output;
//        }
//
//        $arrParam = json_decode($param, true);
//        if (!isset($arrParam['sign']) || !isset($arrParam['notify_msg']) || !Tools::isJsonString($arrParam['notify_msg'])) {
//            $output['status'] = 'Fail';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '回调请求参数缺少必要参数';
//
//            return $output;
//        }
//
//        $sign = strtoupper(sha1($arrParam['notify_msg'] . '&key=' . $this->params['apiKey']));
//        if ($sign != strtoupper($arrParam['sign'])) {
//            $output['status'] = 'Fail';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '回调验签失败';
//
//            return $output;
//        }
//
//        $arrNotifyMsg = json_decode($arrParam['notify_msg'], true);
//        if (isset($arrNotifyMsg['merchant_id']) && $arrNotifyMsg['merchant_id'] != $this->params['cNo']) {
//            $output['status'] = 'Fail';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '回调商户号与订单不符';
//            return $output;
//        }
//
//        if (isset($arrNotifyMsg['order_no']) && $arrNotifyMsg['order_no'] != $orderData['platformOrderNo']) {
//            $output['status'] = 'Fail';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '回调商户订单号与订单不符';
//            return $output;
//        }
//
//        if (isset($arrNotifyMsg['trade_status']) && strtoupper($arrNotifyMsg['trade_status']) == 'SUCCESS') {
//            $output['status'] = 'Success';
//            $output['orderStatus'] = 'Success';
//        } elseif (isset($arrNotifyMsg['trade_status']) && strtoupper($arrNotifyMsg['trade_status']) == 'FAILUR') {
//            $output['status'] = 'Success';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '失败原因未知，请联系第三方确认';
//        } else {
//            $output['status'] = 'Fail';
//            $output['orderStatus'] = 'Fail';
//            $output['failReason'] = '回调通知缺少trade_status或状态未知';
//        }
//
//        $output['orderNo'] = $arrNotifyMsg['trade_no'] ?? '';
//        $output['orderAmount'] = $arrNotifyMsg['order_amount'] ?? 0;
//        $output['channelServiceCharge'] = $arrNotifyMsg['fee'] ?? 0;
//
//        return $output;
//    }

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

    protected function getRsaPrivateKey($key){
        return "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($key, 64, "\n", TRUE)."\n-----END RSA PRIVATE KEY-----";
    }

    protected function getRsaPublicKey($key){
        return "-----BEGIN PUBLIC KEY-----\n".wordwrap($key, 64, "\n", TRUE)."\n-----END PUBLIC KEY-----";
    }


    /**
     * 签名  生成签名串  基于sha1withRSA
     *
     * @param string $data 签名前的字符串
     *
     * @param string $privateKey
     *
     * @return string 签名串
     */
    public function rsaSHA1Sign($data, $privateKey) {
        $signature = '';
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    /**
     * AES/PKCS5_PADDING/ECB 128 位加密
     *
     * @param string $preEncryptString 原始 json 字符串
     * @param string $aesKey           base64_encode 编码过的 key
     *
     * @return string base64_encode 编码过的加密字符串
     */
    public function AESEncrypt($preEncryptString, $aesKey) {
        $aesKey = base64_decode($aesKey);

        $size             = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $preEncryptString = $this->pkcs5_pad($preEncryptString, $size);
        $td               = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv               = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $aesKey, $iv);
        $encryptData = mcrypt_generic($td, $preEncryptString);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        $encryptData = base64_encode($encryptData);

        return $encryptData;
    }

    /**
     * AES/PKCS5_PADDING/ECB 128 位解密
     *
     * @param string $encrypted base64_encode 编码过的加密字符串
     * @param string $aesKey    base64_encode 编码过的秘钥
     * @param string $charset   字符集，未使用
     *
     * @return string 原始 json 字符串
     */
    public function AESDecrypt($encrypted, $aesKey, $charset = 'UTF-8') {
        $aesKey    = base64_decode($aesKey);
        $encrypted = base64_decode($encrypted);

        $decrypted = mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $aesKey,
            $encrypted,
            MCRYPT_MODE_ECB
        );

        $decrypted = self::pkcs5_unpad($decrypted);

        return $decrypted;
    }

    /**
     * 验签  验证签名  基于sha1withRSA
     *
     * @param string $data      签名前的原字符串
     * @param string $signature 签名串
     * @param string $publicKey
     *
     * @return int
     */
    public function rsaSHA1Verify($data, $signature, $publicKey) {
        $signature = base64_decode($signature);

//        $publicKey = openssl_pkey_get_public($publicKey);
        //		$keyData = openssl_pkey_get_details($publicKey);

        $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);//openssl_verify 验签成功返回 1，失败 0，错误返回 -1

        return $result;
    }

    public function pkcs5_pad($text, $blocksize) {
        $pad = $blocksize-(strlen($text)%$blocksize);

        return $text.str_repeat(chr($pad), $pad);
    }

    /**
     * @param string $decrypted 经过补码的字符串
     *
     * @return string 去除补码的字符串
     */
    public function pkcs5_unpad($decrypted) {
        $len       = strlen($decrypted);
        $padding   = ord($decrypted[$len-1]);
        $decrypted = substr($decrypted, 0, -$padding);

        return $decrypted;
    }


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

    //个人网银
    public function getPsEBankRechargeOrder($rechargeData){
        return $this->getEBankRechargeOrder($rechargeData,'Ps');
    }

    //企业网银
    public function getEpEBankRechargeOrder($rechargeData){
        return $this->getEBankRechargeOrder($rechargeData,'Ep');
    }

    /**
     * 拼接form表单
     * @param array $rechargeData
     * @param string $type
     */
    private function getEBankRechargeOrder($rechargeData = [] , $type = 'Ps'){

        $dataParams=[
            'version'    => '1.0',
            'nodeId'     => $this->params['nodeId'],
            'orgId'      => $this->params['appAccount'],
            'orderTime'  => date('YmdHis'),
            'signType'   => 'RSA2',
            'charset'    => 'UTF-8',
            'reserve1'   => '66',
            'txnType'   =>$type == 'Ps' ? 'T11302' : 'T11301',
        ];
        $bizContext=[
            'outTradeNo'=> $rechargeData['platformOrderNo'],
            'totalAmount'=>$rechargeData['orderAmount'],
            'payerBank'=> $rechargeData['bankCode'],
            'cardType'=>$type == 'Ps' ? 'DEBIT' : 'PUBLIC',
            'currency'=> 'CNY',
            'body'=>'RECHARGE',
            'orgCreateIp'=>Tools::getIp(),
            'notifyUrl'=>$this->getRechargeCallbackUrl($rechargeData['platformOrderNo']),
            'pageUrl'=>$this->getRechargeReturnUrl(),
            'reserve1' => '66',
        ];
        //$data['trade_way'] = $type == 'Ps' ? 'b2c' : 'b2b' ;
        $this->logger->debug('讯宝充值请求订单参数：' . $rechargeData['platformOrderNo'], $bizContext);
        // 1. 业务参数 json 编码
        $bizContextJson = json_encode($bizContext);
        // 2. 业务参数签名
        $rsaPrivateKey = $this->getRsaPrivateKey($this->params['rsaPrivateKey']);
        $bizContextSign = $this->rsaSHA1Sign($bizContextJson, $rsaPrivateKey);
        // 3. 业务参数加密
        $bizContextAESEncrypt = $this->AESEncrypt($bizContextJson, $this->params['aesKey']);
        // 4. 回填表单
        $dataParams['sign']       = $bizContextSign;
        $dataParams['bizContext'] = $bizContextAESEncrypt;
        $this->logger->debug('讯宝充值请求订单最终参数：' . $rechargeData['platformOrderNo'], $dataParams);

        //建立请求
        $url =$this->params['gateway'] . '/onlinePayProcess';
        $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $sHtml .= "<form id='paysubmit' name='paysubmit' action='".$url."' method='post'>";
        foreach ($dataParams as $key => $value){
            $sHtml.= "<input type='hidden' name='".$key."' value='".$value."'/>";
        }
        $sHtml = $sHtml."<input type='submit' value='' style='display:none;'></form>";
        $sHtml = $sHtml."loading...";
        $sHtml = $sHtml."<script>document.forms['paysubmit'].submit();</script>";

        return  $this->getHtmlToUrl($rechargeData['platformOrderNo'], $sHtml);
    }

    //充值回调
    public function doRechargeCallBack($orderData, $request){

        $params = $request->getParams();
        // 业务参数解密
        $paramsDecryptJson = $this->AESDecrypt($params['bizContext'], $this->params['aesKey']);
        $paramsDecrypt=json_decode(trim($paramsDecryptJson,' '),true);
        $return = [
            'status' => 'Fail',
            'orderStatus' => '',
            'orderNo' => $paramsDecrypt['outTradeNo'],
            'orderAmount' => $paramsDecrypt['totalAmount'],
            'failReason' => '',
            'outputType' => 'string',
            'output' => 'SUCCESS'
        ];

        $this->logger->debug('doRechargeCallBack：',$paramsDecrypt);
        $this->logger->debug('doRechargeCallBack：',$orderData);

        // 验签
        $rsaPublicKey = $this->getRsaPublicKey($this->params['rsaPublicKey']);
        $verify = $this->rsaSHA1Verify($paramsDecryptJson, $params['sign'], $rsaPublicKey);
        if(!$verify){
            $return['failReason'] = '验签失败';
            return $return ;
        }

        if ($orderData['orderStatus'] == 'Success' || $orderData['orderStatus'] == 'Fail') {
            $return['failReason'] = $orderData['platformOrderNo']. '已回调！';
            $return['orderStatus'] = $orderData['orderStatus'];
            return $return;
        }

        if (in_array($paramsDecrypt['retCode'],['RC0031','RC0002','RC9900','RC0001'])) {
            $return['orderStatus'] = '';
            $return['failReason'] = '待支付';
            return $return ;
        }


        if($paramsDecrypt['retCode'] == 'RC0000' ) {
            $return['status'] = 'Success';
            $return['orderStatus'] = 'Success';
            $return['failReason'] = '支付成功';
            return $return ;
        }

        $return['failReason'] = $paramsDecrypt['retMsg'] ?? '';
        $return['status'] = 'Fail';
        $return['orderStatus'] = 'Fail';

        return $return ;

    }
}
