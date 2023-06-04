<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class YilianPlus extends Channels
{
    protected $payType = [

        'EBank' => 3,
    ];
    public $settlementGateway;
    public $settlementMerchantNo;
    public $settlementMerchantKey;
    public $settlementThirdPubKey;
    public $settlementRsaKeyPath;
    public $settlementSelfPubKey;
    public $settlementSelfPriKey;
    public $settle_pubk;
    public $settle_prik;
    public $priKey ;
    public $pubKey ;
    public $des_key = '';
    public $signPreString = '';
    public $settleParams = [
        'VERSION' => "2.1",
        'BATCH_NO' => '',
        'USER_NAME' => '',
        'MSG_TYPE' => '',
        'TRANS_STATE' => '',
        'MSG_SIGN' => '',
        'TRANS_DETAILS' => [
            'SN'=>'',
            'BANK_CODE'=>'',
            'ACC_NO'=>'',
            'ACC_NAME'=>'',
            'ACC_PROVINCE'=>'',
            'ACC_CITY'=>'',
            'AMOUNT'=>'',
            'MOBILE_NO'=>'',
            'PAY_STATE'=>'',
            'BANK_NO'=>'',
            'BANK_NAME'=>'',
            'ACC_TYPE'=>'',
            'ACC_PROP'=>'',
            'ID_TYPE'=>'',
            'ID_NO'=>'',
            'CNY'=>'CNY',
            'EXCHANGE_RATE'=>'',
            'SETT_AMOUNT'=>'',
            'USER_LEVEL'=>'',
            'SETT_DATE'=>'',
            'REMARK'=>'',
            'RESERVE'=>'',
            'RETURN_URL'=>'',
            'MER_ORDER_NO'=>'',
            'MER_SEQ_NO'=>'',
            'QUERY_NO_FLAG'=>'',
            'TRANS_DESC'=>'',
            'SMS_CODE'=>''
        ]
    ];

    public function sendCodeByMobile($orderData , $param){
//        $orderData['platformOrderNo'] = 't20190628190106089362';
//        $param['mobileNo'] = '13552535506';
//        $param['name'] = '全渠道';
//        $param['code'] = '000000';
//        $param['idCard'] = '341126197709218366';
//        $param['bankCard'] = '6216261000000000018';
        $params = [
            'Version' => '2.0.0',
            'MerchantId' => $this->params['cId'],
            'SmId' => md5($orderData['platformOrderNo']),
            'MerchOrderId' => $orderData['platformOrderNo'],
            'TradeTime' => date('YmdHis'),
            'MobileNo' => $param['mobileNo'],
            'VerifyTradeCode' => 'PayByAccV2',
            'SmParam' => "|{$param['name']}|{$param['idCard']}|{$param['bankCard']}||",
        ];
        $params['Sign'] = $this->currentOpenssl($params);
        $params['TradeCode'] = 'ApiSendSmCodeV2';
        $params['SmParam'] = base64_encode($params['SmParam']);
//        print_r($params);
        $res = Requests::post($this->gateway.'/ppi/merchant/itf.do', [], $params, ['timeout' => $this->timeout]);
        $res = $this->parseXML($res->body);
//        echo $this->gateway.'/ppi/merchant/itf.do?' . $this->arrayToURL($params);
//        print_r($res);exit;
        if(!isset($res['head']) || isset($res['head']['retCode']) && $res['head']['retCode'] == '0000') {
            return ['status' => 1 ];
        }else {
            $msg = $res['head']['retMsg'];
            if(isset($res['head']['retCode']) && in_array($res['head']['retCode'],['G044','G042'])) $msg = '该订单短信验证码已达上限，请重新下单';
            return ['status' => 0 , 'msg' => $msg ?? '已超时，请重新下单' ];
        }
    }

    public function withholdMoney($orderData,$param){
        /*$param['mobileNo'] = '13552535506';
        $param['name'] = '全渠道';
        $param['code'] = '000000';
        $param['idCard'] = '341126197709218366';
        $param['bankCard'] = '6216261000000000018';
        $orderData['platformOrderNo'] = 't20190628190106089362';*/
        $params = [
            'Version' => '2.0.0',
            'MerchantId' => $this->params['cId'],
            'IndustryId' => '15',
            'MerchOrderId' => $orderData['platformOrderNo'],
            'Amount' => $orderData['orderAmount'],
            'OrderDesc' => 'Goods_'.time(),
            'TradeTime' => date('YmdHis'),
            'ExpTime' => '',
            'NotifyUrl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'ExtData' => "",
            'MiscData' => "{$param['mobileNo']}|||{$param['name']}|{$param['idCard']}|{$param['bankCard']}|||||||||",
            'NotifyFlag' => 0,
            'SmId' => md5($orderData['platformOrderNo']),
            'SmCode' => $param['code'],
            'pwd' => '',
        ];
        $params['Sign'] = $this->currentOpenssl($params);
        $params['OrderDesc'] = base64_encode($params['OrderDesc']);
        $params['TradeCode'] = 'PayByAccV2';
        $params['NotifyUrl'] = urlencode($params['NotifyUrl']);
        $params['MiscData'] = base64_encode($params['MiscData']);
//        print_r($params);
        $res = Requests::post($this->gateway.'/ppi/merchant/itf.do', [], $params, ['timeout' => $this->timeout]);
//        echo $this->gateway.'/ppi/merchant/itf.do?' . $this->arrayToURL($params);
//        print_r($res->body);exit;
        $res = $this->parseXML($res->body);
        if(isset($res['body']['Status']) && in_array($res['body']['Status'] , ['02','10'])) {
            return ['status' => 1 ];
        }else {
            $msg = $res['head']['retMsg'];
            if(isset($res['head']['retCode']) && in_array($res['head']['retCode'],['G044','G042'])) $msg = '该订单短信已过期，请重新下单';
            return ['status' => 0 , 'msg' => $msg ?? '扣款失败，请核对填写的信息' ];
        }
//        print_r($params);exit;
    }

    public function rechargeMoney($orderData,$param){
        /*$param['mobileNo'] = '13552535506';
        $param['name'] = '全渠道';
        $param['code'] = '000000';
        $param['idCard'] = '341126197709218366';
        $param['bankCard'] = '6216261000000000018';
        $orderData['platformOrderNo'] = 't20190628190106089362';*/
        $params = [
            'Version' => '2.0.0',
            'MerchantId' => $this->params['cId'],
            'IndustryId' => '15',
            'MerchOrderId' => $orderData['settlementRechargeOrderNo'],
            'Amount' => $orderData['orderAmount'],
            'OrderDesc' => 'Goods_'.time(),
            'TradeTime' => date('YmdHis'),
            'ExpTime' => '',
            'NotifyUrl' => $this->getRechargeCallbackUrl($orderData['settlementRechargeOrderNo']),
            'ExtData' => "",
            'MiscData' => "{$param['mobileNo']}|||{$param['name']}|{$param['idCard']}|{$param['bankCard']}|||||||||",
            'NotifyFlag' => 0,
            'SmId' => md5($orderData['settlementRechargeOrderNo']),
            'SmCode' => $param['code'],
            'pwd' => '',
        ];
        $params['Sign'] = $this->currentOpenssl($params);
        $params['OrderDesc'] = base64_encode($params['OrderDesc']);
        $params['TradeCode'] = 'PayByAccV2';
        $params['NotifyUrl'] = urlencode($params['NotifyUrl']);
        $params['MiscData'] = base64_encode($params['MiscData']);
//        print_r($params);
        $res = Requests::post($this->gateway.'/ppi/merchant/itf.do', [], $params, ['timeout' => $this->timeout]);
//        echo $this->gateway.'/ppi/merchant/itf.do?' . $this->arrayToURL($params);
//        print_r($res->body);exit;
        $res = $this->parseXML($res->body);
        if(isset($res['body']['Status']) && in_array($res['body']['Status'] , ['02','10'])) {
            return ['status' => 1 ];
        }else {
            $msg = $res['head']['retMsg'];
            if(isset($res['head']['retCode']) && in_array($res['head']['retCode'],['G044','G042'])) $msg = '该订单短信已过期，请重新下单';
            return ['status' => 0 , 'msg' => $msg ?? '扣款失败，请核对填写的信息' ];
        }
//        print_r($params);exit;
    }
    //获取下单链接封装
    public function getPayOrder($orderData)
    {
        $url = getenv('GATE_DOMAIN') . '/paySpecial/payView/' . $orderData['platformOrderNo'];
        return [
            'status' => 'Success',
            'payUrl' => $url,
            'orderNo' => '',
            'failReason' => $url,
        ];
    }

    public function getInsideRechargeOrder($orderData){
        $url = getenv('GATE_DOMAIN') . '/paySpecial/rechargeView/' . $orderData['settlementRechargeOrderNo'];
        return [
            'status' => 'Success',
            'payUrl' => $url,
            'orderNo' => '',
            'failReason' => $url,
        ];
    }

    public function getSettlementOrder($orderData)
    {
        
        $this->initSettlementParams($this->params);

        $this->des_key = base64_decode($this->generateKey(9999,24));

        $params = $this->settleParams;

        $params['BATCH_NO'] = $orderData['platformOrderNo'];
        $params['USER_NAME'] = $this->settlementMerchantNo;
        $params['MSG_TYPE'] = 100001;
        $params['TRANS_DETAILS']['SN'] = $orderData['merchantOrderNo'];
        $params['TRANS_DETAILS']['BANK_CODE'] = $orderData['bankCode'];
        $params['TRANS_DETAILS']['ACC_NO'] = Tools::decrypt($orderData['bankAccountNo']);
        $params['TRANS_DETAILS']['ACC_NAME'] = $orderData['bankAccountName'];
        $params['TRANS_DETAILS']['ACC_PROVINCE'] = $orderData['province'];
//        $params['TRANS_DETAILS']['ACC_CITY'] = $orderData['city'];
        $params['TRANS_DETAILS']['AMOUNT'] = $orderData['orderAmount'];
//        $params['TRANS_DETAILS']['BANK_NAME'] = $orderData['bankName'];

        $params['MSG_SIGN'] = $this->createSign($params,$this->settlementSelfPriKey);
        $xml = $this->classToXml($params);
        //des 加密
        $req_body_enc = openssl_encrypt($xml, 'DES-EDE3', $this->des_key);
        if (openssl_public_encrypt(base64_encode($this->des_key), $crypted, $this->settlementThirdPubKey, OPENSSL_PKCS1_PADDING))
        {
            $req_key_enc = base64_encode('' . $crypted);
        }else{
            $output['status'] = 'Fail';
            $output['failReason'] = 'des加密失败';
            return $output;
        }
        $sendTxt = $req_body_enc . "|" . $req_key_enc;
        $result = $this->postXmlUrl($this->settlementGateway,$sendTxt,true);
//        print_r($result);
        $this->logger->debug($this->settlementGateway, ['params'=>$params,'result'=>$result]);
        if($result['http_code'] != 200){
            $output['status'] = 'Fail';
            $output['failReason'] = '请求失败：'.$result['data'];
            return $output;
        }
        $res = $result['data'];
        $resultdata = explode("|", $res);
        openssl_private_decrypt(base64_decode($resultdata[1]), $decrypted,$this->settlementSelfPriKey,OPENSSL_PKCS1_PADDING); //私钥匙 rsa  解密
        if($decrypted) $decrypted = '' . $decrypted;
        $this->des_key = base64_decode($decrypted);
        $receiveXml = openssl_decrypt(base64_decode($resultdata[0]),'DES-EDE3', $this->des_key,OPENSSL_PKCS1_PADDING);
        $returnParams = $this->xmlToArray($receiveXml);
        $pay_state = empty($returnParams['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE']) ? '': $returnParams['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE'];
        if($returnParams['TRANS_STATE'] !== '0000'|| ($pay_state !== '0000' &&  $pay_state !== '00A4')){
            $output['status'] = 'Fail';
            $output['failReason'] = '订单提交失败：' . 'TRANS_STATE:'.$returnParams['TRANS_STATE'].'PAY_STATE:'.$pay_state;
            return $output;
        }
        $this->splitSignString($returnParams);
        $returnSign = base64_decode($returnParams['MSG_SIGN']);
//        var_dump($returnParams);exit;
        if (!openssl_verify($this->signPreString, $returnSign, $this->settlementThirdPubKey,OPENSSL_ALGO_MD5)){
            $output['status'] = 'Fail';
            $output['failReason'] = '返回数据验签失败！';
            return $output;

        }
        $output['status'] = 'Success';
        $output['orderNo'] = $orderData['platformOrderNo'];
        $output['failReason'] = '';
        return $output;
    }

    public function querySettlementOrder($platformOrderNo = ''){


        $this->initSettlementParams($this->params);

        $this->des_key = base64_decode($this->generateKey(9999,24));
        $params = $this->settleParams;
        $params['BATCH_NO'] = $platformOrderNo;
        $params['USER_NAME'] = $this->settlementMerchantNo;
        $params['MSG_TYPE'] = 100002;
        $params['TRANS_DETAILS']['QUERY_NO_FLAG'] = 0;
        $params['MSG_SIGN'] = $this->createSign($params,$this->settlementSelfPriKey);
        $xml = $this->classToXml($params);
        $req_body_enc = openssl_encrypt($xml, 'DES-EDE3', $this->des_key);
        if (openssl_public_encrypt(base64_encode($this->des_key), $crypted, $this->settlementThirdPubKey, OPENSSL_PKCS1_PADDING))
        {
            $req_key_enc = base64_encode('' . $crypted);
        }else{
            $output['status'] = 'Fail';
            $output['failReason'] = 'des加密失败';
            return $output;
        }
        $sendTxt = $req_body_enc . "|" . $req_key_enc;
        $result = $this->postXmlUrl($this->settlementGateway,$sendTxt,true);
        $this->logger->debug($this->settlementGateway, ['params'=>$params,'result'=>$result]);
//        print_r($result);
        if($result['http_code'] != 200){
            $output['status'] = 'Execute';
            $output['failReason'] = '请求失败：'.$result['data'];
            return $output;
        }
        $res = $result['data'];
        $resultdata = explode("|", $res);
        openssl_private_decrypt(base64_decode($resultdata[1]), $decrypted,$this->settlementSelfPriKey,OPENSSL_PKCS1_PADDING); //私钥匙 rsa  解密
        if($decrypted) $decrypted = '' . $decrypted;
        $this->des_key = base64_decode($decrypted);
        $receiveXml = openssl_decrypt(base64_decode($resultdata[0]),'DES-EDE3', $this->des_key,OPENSSL_PKCS1_PADDING);
        $returnParams = $this->xmlToArray($receiveXml);
//        print_r($returnParams);exit;
        $pay_state = empty($returnParams['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE']) ? '': $returnParams['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE'];

        if($returnParams['TRANS_STATE'] !== '0000'){
            $output['status'] = 'Execute';
            $output['failReason'] = '查询失败：' . 'TRANS_STATE:'.$returnParams['TRANS_STATE'].'PAY_STATE:'.$pay_state;
            return $output;
        }

        if($pay_state === '00A4'){
            $output['status'] = 'Execute';
            $output['failReason'] = '订单处理中：' . 'TRANS_STATE:'.$returnParams['TRANS_STATE'].'PAY_STATE:'.$pay_state;
            return $output;
        }

        if($pay_state !== '0000'){
            $output['status'] = 'Fail';
            $output['failReason'] = '代付订单失败：' . 'TRANS_STATE:'.$returnParams['TRANS_STATE'].'PAY_STATE:'.$pay_state;
            return $output;
        }

        $this->splitSignString($returnParams);
        $returnSign = base64_decode($returnParams['MSG_SIGN']);
        if (!openssl_verify($this->signPreString, $returnSign, $this->settlementThirdPubKey,OPENSSL_ALGO_MD5)){
            $output['status'] = 'Execute';
            $output['failReason'] = '返回数据验签失败！';
            return $output;
            /*$res_state = [];
            $res_state['TRANS_STATE'] = $returnParams['TRANS_STATE'];
            $res_state['TRANS_DETAILS'] = $returnParams['TRANS_DETAILS'];
            $res_state['PAY_STATE'] = $res_state['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE'];
            print_r($res_state);exit;
            return $res_state;*/
        }
        $output['status'] = 'Success';
        $output['orderNo'] = $platformOrderNo;
        $output['failReason'] = '';
        return $output;
    }

    public function initSettlementParams($settleParams){

        $this->settlementGateway = $settleParams['settlementGateway'];
        $this->settlementMerchantNo = $settleParams['settlementMerchantNo'];
        $this->settlementMerchantKey = $settleParams['settlementMerchantKey'];
        $this->settlementThirdPubKey = $this->getPublicKey($settleParams['settlementThirdPubKey']);
        $this->settlementRsaKeyPath = $settleParams['settlementRsaKeyPath'];
        $pfx= __DIR__ .'/../../../resources/pay/'.$settleParams['settlementRsaKeyPath'];
        $pkcs12 = file_get_contents($pfx);
        openssl_pkcs12_read($pkcs12, $certs, $settleParams['settlementMerchantKey']);
        $this->settlementSelfPubKey = $certs['cert'];
        $this->settlementSelfPriKey = $certs['pkey'];

    }
    
    public function queryBalance(){

        $output = ['status' => '',  'failReason' => '', 'balance' => 0];
        $this->initSettlementParams($this->params);
        $this->des_key = base64_decode($this->generateKey(9999,24));//组装公钥匙
        $params = $this->settleParams;
        $params['USER_NAME'] = $this->settlementMerchantNo;
        $params['MSG_TYPE'] = 600001;
        $params['MSG_SIGN'] = $this->createSign($params,$this->settlementSelfPriKey);
        $xml = $this->classToXml($params);
        $req_body_enc = openssl_encrypt($xml, 'DES-EDE3', $this->des_key);
        if (openssl_public_encrypt(base64_encode($this->des_key), $crypted, $this->settlementThirdPubKey, OPENSSL_PKCS1_PADDING))
        {
            $req_key_enc = base64_encode('' . $crypted);
        }else{
            $output['status'] = 'Fail';
            $output['failReason'] = 'des加密失败';
            return $output;
        }
        $sendTxt = $req_body_enc . "|" . $req_key_enc;

        $result = $this->postXmlUrl($this->settlementGateway,$sendTxt,true);
        $this->logger->debug($this->settlementGateway, ['params'=>$params,'result'=>$result]);
//        print_r($result);
        if($result['http_code'] != 200){
            $output['status'] = 'Fail';
            $output['failReason'] = '请求失败：'.$result['data'];
            return $output;
        }
        $res = $result['data'];

        $resultdata = explode("|", $res);
        openssl_private_decrypt(base64_decode($resultdata[1]), $decrypted,$this->settlementSelfPriKey,OPENSSL_PKCS1_PADDING); //私钥匙 rsa  解密
        if($decrypted) $decrypted = '' . $decrypted;
        $this->des_key = base64_decode($decrypted);
        $receiveXml = openssl_decrypt(base64_decode($resultdata[0]),'DES-EDE3', $this->des_key,OPENSSL_PKCS1_PADDING);

        $returnParams = $this->xmlToArray($receiveXml);
//        print_r($returnParams);exit;
        $pay_state = empty($returnParams['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE']) ? '': $returnParams['TRANS_DETAILS']['TRANS_DETAIL']['PAY_STATE'];

        if($returnParams['TRANS_STATE'] !== '0000'|| $pay_state !== '0000'){
            $output['status'] = 'Fail';
            $output['failReason'] = '查询失败：' . 'TRANS_STATE:'.$returnParams['TRANS_STATE'].'PAY_STATE:'.$pay_state;
            return $output;
        }
        $output['status'] = 'Success';
        $output['balance'] = $returnParams['TRANS_DETAILS']['TRANS_DETAIL']['AMOUNT'];
        return $output;

    }

    public function settlementRecharge($rechargeData){

//        print_r($this->params);exit;
        $privatKey = $this->params['settlementRechargePrikey'];//商户私钥
        $url = $this->params['settlementRechargeUrl'];//请求提交地址
        $Version = '2.0.0'; //接口版本
        $OrderFrom = '12'; //订单来源
        $Currency = 'CNY'; //币种
        $Language = '00'; //语言
        $SynAddress = 'http://merchant-test.wldev01.com'; //同步返回报文地址
//        $AsynAddress = 'http://cb-test.wldev01.com/settlementRecharge/callback'; //异步返回报文地址
        $OrderType = '00'; //订单类型 00=即时支付 01-非即时支�?
        $Description = '商品描述'; //商品描述
        $remark = '备注'; //备注
        $MerchantName = '姓名'; //姓名

        $ProcCode = '0200'; //消息类型
        $AccountNo = ''; //银行卡号
        $ProcessCode = '190011';  //处理�?
        $Amount = $rechargeData->orderAmount;   //金额
        $TransDatetime = date("YmdHis",time()); //传输日期时间
        $AcqSsn = '123456'; //系统跟踪�?
        $TransData = ''; //其他业务资料，用“|”分隔，例如：银行代�?
        $Reference = ''; //系统参考号  原值返�?
        $TerminalNo = '02028828'; //终端�?
        $MerchantNo = $this->params['settlementRechargeMerchantNo'];//'302020000114'; //商户�?
        $MerchantOrderNo = $rechargeData->settlementRechargeOrderNo;//商户系统订单?
//        $AsynAddress = $AsynAddress .'/'. $MerchantOrderNo;
        $AsynAddress = $this->getSettlementRechargeCallbackUrl($MerchantOrderNo);
        //组建令牌
        $macClear = '';
        $macClear .= !empty($ProcCode) ? $ProcCode.' ' : '';
        $macClear .= !empty($AccountNo) ? $AccountNo.' ' : '';
        $macClear .= !empty($ProcessCode) ? $ProcessCode.' ' : '';
        $macClear .= !empty($Amount) ? $Amount.' ' : '';
        $macClear .= !empty($TransDatetime) ? $TransDatetime.' ' : '';
        $macClear .= !empty($AcqSsn) ? $AcqSsn.' ' : '';
        $macClear .= !empty($OrderNo) ? $OrderNo.' ' : '';
        $macClear .= !empty($TransData) ? $TransData.' ' : '';
        $macClear .= !empty($Reference) ? $Reference.' ' : '';
        $macClear .= !empty($TerminalNo) ? $TerminalNo.' ' : '';
        $macClear .= !empty($MerchantNo) ? $MerchantNo.' ' : '';
        $macClear .= !empty($MerchantOrderNo) ? $MerchantOrderNo.' ' : '';
        $macClear = trim($macClear);
        $mac = md5(strtoupper($macClear)." ".$privatKey);
        $mac =  strtoupper($mac);

        //组建xml
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<x:NetworkRequest xmlns:x="http://www.payeco.com" xmlns:xsi="http://www.w3.org">';
        $xml .= '<Version>'.$Version.'</Version>';
        $xml .= '<ProcCode>'.$ProcCode.'</ProcCode>';
        $xml .= '<ProcessCode>'.$ProcessCode.'</ProcessCode>';
        $xml .= '<AccountNo></AccountNo>';
        $xml .= '<AccountType></AccountType>';
        $xml .= '<MobileNo></MobileNo>';
        $xml .= '<Amount>'.$Amount.'</Amount>';
        $xml .= '<Currency>CNY</Currency>';
        $xml .= '<SynAddress>'.$SynAddress.'</SynAddress>';
        $xml .= '<AsynAddress>'.$AsynAddress.'</AsynAddress>';
        $xml .= '<Remark>'.$remark.'</Remark>';
        $xml .= '<TerminalNo>'.$TerminalNo.'</TerminalNo>';
        $xml .= '<MerchantNo>'.$MerchantNo.'</MerchantNo>';
        $xml .= '<MerchantOrderNo>'.$MerchantOrderNo.'</MerchantOrderNo>';
        $xml .= '<OrderNo></OrderNo>';
        $xml .= '<OrderFrom>'.$OrderFrom.'</OrderFrom>';
        $xml .= '<Language>'.$Language.'</Language>';
        $xml .= '<Description>'.$Description.'</Description>';
        $xml .= '<OrderType>'.$OrderType.'</OrderType>';
        $xml .= '<AcqSsn>'.$AcqSsn.'</AcqSsn>';
        $xml .= '<Reference></Reference>';
        $xml .= '<TransDatetime>'.$TransDatetime.'</TransDatetime>';
        $xml .= '<MerchantName>'.$MerchantName.'</MerchantName>';
        $xml .= '<TransData></TransData>';
        $xml .= '<IDCardName></IDCardName>';
        $xml .= '<IDCardNo></IDCardNo>';
        $xml .= '<BankAddress></BankAddress>';
        $xml .= '<IDCardType></IDCardType>';
        $xml .= '<BeneficiaryName></BeneficiaryName>';
        $xml .= '<BeneficiaryMobileNo></BeneficiaryMobileNo>';
        $xml .= '<DeliveryAddress></DeliveryAddress>';
        $xml .= '<IpAddress></IpAddress>';
        $xml .= '<Location></Location>';
        $xml .= '<UserFlag></UserFlag>';
        $xml .= '<MAC>'.$mac.'</MAC>';
        $xml .= '</x:NetworkRequest>';

        $request_text = urlencode(base64_encode($xml));//最终请求格�?
        $this->logger->debug($url, ['params'=>['requestText'=>$request_text]]);
        //建立请求
        $para_temp = ['request_text' => $request_text];
        $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $sHtml .= "<form id='paysubmit' name='paysubmit' action='".$url."' method='post' target='_blank'>";
        foreach ($para_temp as $key => $value){
            $sHtml.= "<input type='hidden' name='".$key."' value='".$value."'/>";
        }
        $sHtml = $sHtml."<input type='submit' value='' style='display:none;'></form>";
        $sHtml = $sHtml."loading...";
        $sHtml = $sHtml."<script>document.forms['paysubmit'].submit();</script>";
        echo $sHtml;exit;
    }

    public function doSettlementRechargeCallback($orderData, $request){


        $return = [
            'status' => 'Fail',
            'orderStatus' => 'Fail',
            'orderNo' => '',
            'orderAmount' => '',
            'failReason' => '',
            'outputType' => 'string',
            'output' => '0000'
        ];
        if ($orderData['orderStatus'] == 'Success' || $orderData['orderStatus'] == 'Fail') {
            $return['failReason'] = $orderData['settlementRechargeOrderNo']. '已回调！';
            $return['orderStatus'] = $orderData['orderStatus'];
            return $return;
        }
        $callbackParams = $request->getParams();
        $this->logger->debug('doSettlementRechargeCallbackDetail：'.$callbackParams['response_text']);


        //接受返回报文
        //参数
        $privatKey = $this->params['settlementRechargePrikey'];//商户私钥
        $xml = base64_decode($callbackParams['response_text']);
        $ret = simplexml_load_string($xml);
        $retoArray = $this->xmlToArray($xml);
        //组建令牌
        $macClear = '';
        $macClear .= !empty($ret->ProcCode) ? $ret->ProcCode.' ' : '';
        $macClear .= !empty($ret->AccountNo) ? $ret->AccountNo.' ' : '';
        $macClear .= !empty($ret->ProcessCode) ? $ret->ProcessCode.' ' : '';
        $macClear .= !empty($ret->Amount) ? $ret->Amount.' ' : '';
        $macClear .= !empty($ret->TransDatetime) ? $ret->TransDatetime.' ' : '';
        $macClear .= !empty($ret->AcqSsn) ? $ret->AcqSsn.' ' : '';
        $macClear .= !empty($ret->OrderNo) ? $ret->OrderNo.' ' : '';
        $macClear .= !empty($ret->TransData) ? $ret->TransData.' ' : '';
        $macClear .= !empty($ret->Reference) ? $ret->Reference.' ' : '';
        $macClear .= !empty($ret->RespCode) ? $ret->RespCode.' ' : '';
        $macClear .= !empty($ret->TerminalNo) ? $ret->TerminalNo.' ' : '';
        $macClear .= !empty($ret->MerchantNo) ? $ret->MerchantNo.' ' : '';
        $macClear .= !empty($ret->MerchantOrderNo) ? $ret->MerchantOrderNo.' ' : '';
        $macClear .= !empty($ret->OrderState) ? $ret->OrderState.' ' : '';
        $macClear = trim($macClear);
        $mac = md5(strtoupper($macClear)." ".$privatKey);  //mac
        $mac =  strtoupper($mac);  //mac

        //验证令牌
        if($ret->MAC != $mac){//验证失败
            $return['failReason'] = "验证失败";
            $return['orderAmount'] = $retoArray['Amount'];
            return $return;
        }

        //验证交易
        if($ret->OrderState != '02' || $ret->RespCode != '0000'){//交易失败
            $return['failReason'] = "交易失败";
            $return['orderAmount'] = $retoArray['Amount'];
            return $return;
        }

        //调用后续逻辑
        //如果要使用返回的对象作为其他方法的参数 需将其转换为字符串类型 如：$Amount = (string) $ret->Amount;
        return [
            'status' => 'Success',
            'orderStatus' => 'Success',
            'orderNo' => $retoArray['MerchantOrderNo'],
            'orderAmount' => $retoArray['Amount'],
            'failReason' => 'OrderState：'.$ret->OrderState . ',RespCode：'.$ret->RespCode,
            'outputType' => 'string',
            'output' => '0000'
        ];
//        echo '0000'; //如果交易完成 则返回'0000'通知系统
    }

    public function doRechargeCallback($orderData, $request){

        $params = $request->getParams();
        if(!in_array($params['Status'] , ['02','10'])) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['MerchOrderId'],
                'orderAmount' => $params['Amount'],
                'failReason' => '支付失败:' . json_encode($params),
                'attr' => json_encode($orderData),
            ];
        }
        if (!$this->pukOpenssl($params)) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['MerchOrderId'],
                'orderAmount' => $params['Amount'],
                'failReason' => '验签失败:' . json_encode($params),
                'attr' => $params['Sign'],
            ];
        }
        return [
            'status' => 'Success',
            'orderStatus' => 'Success',
            'orderNo' => $params['MerchOrderId'],
            'orderAmount' => $params['Amount'],
            'failReason' => json_encode($params),
        ];
    }

    //生成BATCH_NO，每笔订单不可重复，建议：公司简称缩写+yymmdd+流水号
    public function create_batch_no(){
        return "XYZ" . date("Ymd")  . $this->random_numbers(6);
    }

    //    /* 发送数据返回接收数据 */
    public function postXmlUrl($url, $xmlStr, $ssl = false, $type = "Content-type: text/xml")
    {
        $ch = curl_init();
        $params = array();
        if ($type)
            $params[] = $type; //定义content-type为xml
        curl_setopt($ch, CURLOPT_URL, $url); //定义表单提交地址
        if ($ssl)
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }
        curl_setopt($ch, CURLOPT_POST, 1);   //定义提交类型 1：POST ；0：GET
        curl_setopt($ch, CURLOPT_HEADER, 0); //定义是否显示状态头 1：显示 ； 0：不显示
        if ($params)
            curl_setopt($ch, CURLOPT_HTTPHEADER, $params); //定义请求类型
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //定义是否直接输出返回流
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
//        curl_setopt($ch, CURLOPT_TIMEOUT_MS,500);      //  0.5秒超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlStr); //定义提交的数据，这里是XML文件
        //封禁"Expect"头域
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $xml_data = curl_exec($ch);
        $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            return ['http_code'=>500,'data'=>curl_error($ch)];
        } else {
            curl_close($ch);
        }

        return ['http_code'=>$httpCode,'data'=>$xml_data];
    }

    public function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    public function  arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
        $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    public function payView($orderData,$param){
        //返回发送验证码接口  与 提交扣款接口
        $s1 = getenv('GATE_DOMAIN') . '/paySpecial/sendCodeByMobile/' . $orderData['platformOrderNo'];
        $s2 = getenv('GATE_DOMAIN') . '/paySpecial/withholdMoney/' . $orderData['platformOrderNo'];
        return ['view' => 'bank' , 'data' => ['sendCode' => $s1 , 'submit' => $s2 , 'order' => $orderData]];
    }

    public function rechargeView($orderData,$param){
        //返回发送验证码接口  与 提交扣款接口
        $s1 = getenv('GATE_DOMAIN') . '/paySpecial/sendCodeByMobile/' . $orderData['settlementRechargeOrderNo'];
        $s2 = getenv('GATE_DOMAIN') . '/paySpecial/rechargeMoney/' . $orderData['settlementRechargeOrderNo'];
        return ['view' => 'bank' , 'data' => ['sendCode' => $s1 , 'submit' => $s2 , 'order' => $orderData]];
    }

    public function isAllowPayOrderOrderAmountNotEqualRealOrderAmount(){
        return true;
    }

    public function errorResponse($response)
    {
        return $response->withStatus(500)->write('ERROR');
    }

    public function successResponse($response)
    {
        return $response->write('0000');
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        if(!in_array($params['Status'] , ['02','10'])) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['MerchOrderId'],
                'orderAmount' => $params['Amount'],
                'failReason' => '支付失败:' . json_encode($params),
                'attr' => json_encode($orderData),
            ];
        }
        if (!$this->pukOpenssl($params)) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['MerchOrderId'],
                'orderAmount' => $params['Amount'],
                'failReason' => '验签失败:' . json_encode($params),
                'attr' => $params['Sign'],
            ];
        }
        return [
            'status' => 'Success',
            'orderStatus' => 'Success',
            'orderNo' => $params['MerchOrderId'],
            'orderAmount' => $params['Amount'],
            'failReason' => json_encode($params),
        ];
    }

    public function arrayToURL($data) {
        $signPars = "";
        foreach($data as $k => $v) {
            $signPars .= $k . "=" . $v . "&";
        }
        $signPars = rtrim($signPars,'&');
        return $signPars;
    }

    //生成随机字母+数字
    public function random_numbers($size = 4){
        $str = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $code = "";

        $len = strlen($str);
        for ($i = 0; $i < $size; $i++)
        {
            $code .= $str{rand(0, $len)};
        }
        return $code;
    }

    public function classToXml($params)
    {
        $xml = "<MSGBEAN>";
        $xml .= "<VERSION>" . $params['VERSION'] . "</VERSION>";
        $xml .= "<MSG_TYPE>" . $params['MSG_TYPE'] . "</MSG_TYPE>";
        $xml .= "<BATCH_NO>" . $params['BATCH_NO'] . "</BATCH_NO>";
        $xml .= "<USER_NAME>" . $params['USER_NAME'] . "</USER_NAME>";
        $xml .= "<TRANS_STATE>" . $params['TRANS_STATE'] . "</TRANS_STATE>";
        $xml .= "<MSG_SIGN>" . $params['MSG_SIGN'] . "</MSG_SIGN>";
        $xml .= "<TRANS_DETAILS>";
        $xml .= "<TRANS_DETAIL>";
        foreach ($params['TRANS_DETAILS'] as $key =>$item)
        {
            $xml .= "<" . $key . ">" .$item. "</" . $key . ">";
        }
        $xml .= "</TRANS_DETAIL>";

        $xml .= "</TRANS_DETAILS>";
        $xml .= "</MSGBEAN>";
        return $xml;
    }

    /**
     * 整理公钥
     * @param $key
     * @return string
     */
    public function getPublicKey($pubKey = null)
    {
        $pubkey = "-----BEGIN PUBLIC KEY-----\r\n";
        foreach (str_split($pubKey,64) as $str){
            $pubkey = $pubkey . $str ;
        }
        $pubkey .= "\r\n-----END PUBLIC KEY-----";
        $rekey = openssl_get_publickey($pubkey);
        return $rekey;
    }

    /**
     * 整理私钥
     * @param $pkey
     * @return string
     */
    public function getPrivateKey($key = null)
    {
        $private_key = "-----BEGIN RSA PRIVATE KEY-----\r\n";
        foreach (str_split($key,64) as $str){
            $private_key = $private_key . $str ;
        }
        $private_key .=  "\r\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($private_key);
        return $key;
    }

    //公钥加密
    public function pukOpenssl($data){
        $sign = str_replace(' ','+',$data['Sign']);
        unset($data['Sign']);
        foreach ($data as $k => $v) {
            $signPars[] = $k .'=' .$v;
        }
        $signPars = implode('&' , $signPars);
        $key = $this->getPublicKey($this->params['channelPK']);
        return openssl_verify($signPars, base64_decode($sign), $key,OPENSSL_ALGO_MD5);
    }

    //私钥加密
    public function currentOpenssl($data , $way = OPENSSL_ALGO_MD5){
        unset($data['Sign']);
        foreach ($data as $k => $v) {
            $signPars[] = $k .'=' .$v;
        }
        $signPars = implode('&' , $signPars);
//        print_r($signPars);exit;
        $key = $this->getPrivateKey($this->params['privateKey']);
        openssl_sign($signPars,$sign_info,$key,$way);
        return base64_encode($sign_info);
    }

    //返回类签名信息
    public function createSign($params,$pkey){
        $keys = ['SN','PAY_STATE','ACC_NO','ACC_NAME','AMOUNT','CNY'];
        $sign = "";
        $sign .= $params['BATCH_NO'];
//        if ($this->VERSION)
//            $sign .= ($this->VERSION ? " " . $this->VERSION : "");
        $sign .= ($params['USER_NAME'] ? " " . $params['USER_NAME'] : "");
        $sign .= ($params['MSG_TYPE'] ? " " . $params['MSG_TYPE'] : "");
        $sign .= ($params['TRANS_STATE'] ? " " . $params['TRANS_STATE'] : "");
        if ($params['TRANS_DETAILS']) {

            foreach ($params['TRANS_DETAILS'] as $key => $item)
            {
                if($item && in_array($key,$keys)){
//                  echo $key.':',$item . PHP_EOL;
                    $sign .= " " . $item ;
                }
            }

        }
        openssl_sign($sign,$sign_info,$pkey,OPENSSL_ALGO_MD5);
        return base64_encode($sign_info);

    }

    public function splitSignString($params)
    {
        $keys = ['SN','PAY_STATE','ACC_NO','ACC_NAME','AMOUNT','CNY'];
        $sign = "";
        $sign .= $params['BATCH_NO'];
        $sign .= ($params['USER_NAME'] ? " " . $params['USER_NAME'] : "");
        $sign .= ($params['MSG_TYPE'] ? " " . $params['MSG_TYPE'] : "");
        $sign .= ($params['TRANS_STATE'] ? " " . $params['TRANS_STATE'] : "");
        if ($params['TRANS_DETAILS']) {
            $TRANS_DETAIL = $params['TRANS_DETAILS']['TRANS_DETAIL'];
            $signTransDetail = [
                'SN'=>$TRANS_DETAIL['SN'],
                'PAY_STATE'=>$TRANS_DETAIL['PAY_STATE'],
                'ACC_NO'=>$TRANS_DETAIL['ACC_NO'],
                'ACC_NAME'=>$TRANS_DETAIL['ACC_NAME'],
                'AMOUNT'=>$TRANS_DETAIL['AMOUNT'],
                'CNY'=>$TRANS_DETAIL['CNY'],
            ];
            foreach ($signTransDetail as $key => $item)
            {
                if($item) $sign .= " " . $item ;

            }
        }
        $this->signPreString = $sign;
    }

    /**
     * 将数据转为XML
     */
    public function toXml(array $array){
        $xml = '<xml>';
        forEach($array as $k=>$v){
            $xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
        }
        $xml.='</xml>';
        return $xml;
    }

    /**
     * XML解析成数组
     */
    public function parseXML($xmlSrc){
        if(empty($xmlSrc)){
            return false;
        }
        $array = array();
        $xml = simplexml_load_string($xmlSrc);
        $encode = $this->getXmlEncode($xmlSrc);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = $this->parseXML($nodeXml);
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                if($encode!="" && strpos($encode,"UTF-8") === FALSE ) {
                    $k = iconv("UTF-8", $encode, $k);
                    $v = iconv("UTF-8", $encode, $v);
                }
                $array[$k] = $v;
            }
        }
        return $array;
    }

    //获取xml编码
    public function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }

    //生成24位随机码
    public function generateKey($round, $length)
    {
        $key = "";
        for ($i = 0; $i < $length; $i++)
        {
            $random = rand(0, $round) % 16;
            switch ($random)
            {
                case 0: $key .= "0";
                    break;
                case 1: $key .= "1";
                    break;
                case 2: $key .= "2";
                    break;
                case 3: $key .= "3";
                    break;
                case 4: $key .= "4";
                    break;
                case 5: $key .= "5";
                    break;
                case 6: $key .= "6";
                    break;
                case 7: $key .= "7";
                    break;
                case 8: $key .= "8";
                    break;
                case 9: $key .= "9";
                    break;
                case 10: $key .= "A";
                    break;
                case 11: $key .= "B";
                    break;
                case 12: $key .= "C";
                    break;
                case 13: $key .= "D";
                    break;
                case 14: $key .= "E";
                    break;
                case 15: $key .= "F";
                    break;
                default: $i--;
            }
        }

        return base64_encode($key);
    }


}
