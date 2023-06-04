<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class LoroPay extends Channels
{
    protected $bankType = [
        'RB' => [
            'code' => 'Robinsons Bank',
            'name' => 'Robinsons Bank',
        ],
    ];

    protected function createSignStr($params)
    {
        ksort($params);
        $str='';
        foreach ($params as $key=>$val) {
            if( (is_numeric($val) && $val ===0) || ($val!='' && $val!=null)){
                $str.=$val;
            }
        }
        return $str;
    }

    //加密
    private function getEncry($str,$private_key){
        //解析私钥
        $res = openssl_pkey_get_private($this->getRsaPrivateKey($private_key));

        $content = '';
        //使用私钥加密
        foreach (str_split($str, 117) as $str1) {
            openssl_private_encrypt($str1, $crypted, $res);
            $content .= $crypted;
        }

        //编码转换
        $encrypted = base64_encode($content);
        return $encrypted;
    }

    private function getDecry($encrypted,$public_key){
        $encrypted = str_replace('-','+',$encrypted);
        $encrypted = str_replace('_','/',$encrypted);
        //解析公钥
        $res = openssl_pkey_get_public($this->getRsaPublicKey($public_key));
       //密文过长，分段解密
        $crypto = '';
        foreach (str_split(base64_decode($encrypted), 128) as $chunk) {
            openssl_public_decrypt($chunk, $decryptData, $res);
            $crypto .= $decryptData;
        }
        return $crypto;
    }

    protected function getRsaPrivateKey($key){
        return "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($key, 64, "\n", TRUE)."\n-----END RSA PRIVATE KEY-----";
    }

    protected function getRsaPublicKey($key){
        return "-----BEGIN PUBLIC KEY-----\n".wordwrap($key, 64, "\n", TRUE)."\n-----END PUBLIC KEY-----";
    }

    public function successResponse($response)
    {
        return $response->write('success');
    }

    public function getPayOrder($orderData)
    {
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];

        $path = '/api/pay/code';
        $params = [
            'merchantNo' => $this->params['merchantNo'],
            'merchantOrderNo' => $orderData['platformOrderNo'],
            'payAmount' => $orderData['orderAmount'],
            'description' => "grabpay",
            'method' => "grabpay",//ussc 711_direct BAYD CEBL Dragonpay gcash grabpay visamc billease
            'name' => 'Riza Bade',
            'feeType' => 0,//1支付金额包含手续费，0不包含
            'mobile' => '9953073842',//手机号
            'email' => '9953073842@gmail.com',//邮箱
            'expiryPeriod' => '1440',//最小值两小时，单位min

//            'notify_url' => $this->getPayCallbackUrl($orderData['platformOrderNo']),//后台设置

        ];
        $signStr = $this->createSignStr($params);
//        print_r($signStr);exit;
        $params['sign'] = $this->getEncry($signStr, $this->params['merchantPrivateKey']);
        $this->logger->debug('loro支付请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, ['Accept' => 'application/json', 'Content-Type' => 'application/json'], json_encode($params), ['timeout' => $this->timeout]);
//        print_r(json_decode($rsp->body, true));
        $this->logger->debug('loro支付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = 'loro支付请求失败:' . $rsp->body;
            return $output;
        }

        if ($rsp->status_code == 200) {
            $res = json_decode($rsp->body, true);
            if (isset($res['status']) && $res['status'] =='200' && isset($res['message']) && $res['message']=='success') {
                $url = $res['data']['paymentLink'];
                if(in_array($orderData['payType'],['gcash','711_direct'])){
                    $sHtml = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
                    $sHtml .= "<form id='paysubmit' name='paysubmit' action='".$url."' method='get'>";
                    $sHtml = $sHtml."<input type='submit' value='' style='display:none;'></form>";
                    $sHtml = $sHtml."loading...";
                    $sHtml = $sHtml."<script>document.forms['paysubmit'].submit();</script>";
                    return [
                        'status' => 'Success',
//                    'payUrl' => $res['data']['paymentLink'],
                        'payUrl' => $this->getHtmlToUrl($orderData['platformOrderNo'], $sHtml),
                        'orderNo' => $orderData['platformOrderNo'],
                        'failReason' => $rsp->body,
                    ];

                }else{
                    return [
                        'status' => 'Success',
                        'payUrl' => $res['data']['paymentLink'],
                        'orderNo' => $orderData['platformOrderNo'],
                        'failReason' => $rsp->body,
                    ];
                }

            } else {
                return [
                    'status' => 'Fail',
                    'payUrl' => '',
                    'orderNo' => '',
                    'failReason' => 'loro支付请求失败:' . $rsp->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'payUrl' => '',
                'orderNo' => '',
                'failReason' => 'loro支付请求失败:' . $rsp->body,
            ];
        }
    }

    public function queryPayOrder($platformOrderNo){

        $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/api/pay/query';
        $params = [
            'cid' => $this->params['cid'],
            'order_id' => $platformOrderNo,
            'time' => time(),
        ];

        $data = json_encode($params);

        $dig64 = base64_encode(hash_hmac('sha1', $data, $this->params['apiKey'], true));
        $headers = ['Content-Hmac' => $dig64, 'Content-Type' => 'application/json'];
        $req = Requests::post($this->gateway . $path, $headers, $data, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            $status = ['created' => 'WaitPayment', 'verified' => 'Success', 'timeout' => 'Fail', 'revoked' => 'Fail'];
            if ($res['success']) {
                return [
                    'status' => $status,
                    'orderAmount' => $res['data']['amount'],
                    'orderNo' => $this->emptyOrderNo,
                    'failReason' => $res->body,
                ];
            } else {
                return [
                    'status' => 'Fail',
                    'orderAmount' => 0,
                    'orderNo' => '',
                    'failReason' => '第三方请求失败:' . $req->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function doPayCallback($orderData, $request)
    {
        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];

        $params = $request->getParams();

        if(!$params){
            $output['failReason'] = '回调数据为空';
            return $output;
        }

        if (isset($params['merchantNo']) && $params['merchantNo'] != $this->params['merchantNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户号与订单不符';
            return $output;
        }

        if (isset($params['merchantOrderNo']) && $params['merchantOrderNo'] != $orderData['platformOrderNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户订单号与订单不符';
            return $output;
        }

        $callbackSign = $params['sign'];
        unset($params['sign']);
        $signStr = $this->createSignStr($params);
        $callSignStr = $this->getDecry($callbackSign,$this->params['platformPublicKey']);
        if ($signStr != $callSignStr) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调验签失败';

            return $output;
        }
        $output['status'] = 'Success';
        $output['orderStatus'] = (isset($params['orderStatus']) && strtoupper($params['orderStatus']) == 'SUCCESS') ? 'Success' : 'Fail';
        $output['orderNo'] = $params['merchantOrderNo'] ?? '';
        $output['orderAmount'] = $params['factAmount'] ?? 0;
        $output['failReason'] = isset($params['orderMessage']) ? $params['orderMessage'] : '';

        return $output;
    }

    public function getSettlementOrder($orderData)
    {
        global $app;
//        $bankCode = $app->getContainer()->code['bankCode'];
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/api/cash';
        $params = [
            'merchantNo' => $this->params['merchantNo'],
            'merchantOrderNo' => $orderData['platformOrderNo'],
            'payAmount' => round($orderData['orderAmount'],2),
            'description' => 'daifu',
            'bankCode' => $orderData['bankCode'],
            'bankNumber' => Tools::decrypt($orderData['bankAccountNo']),
            'accountHoldName' => $orderData['bankAccountName'],
            'address' => $orderData['city'],//收款人姓名地址
            'barangay' => 'Maharlika',//收款人barangay
            'city' => 'Pasig',//收款人city
            'zipCode' => 1110,//收款人邮编
            'gender' => 'Male',//收款人性别
            'firstName' => $orderData['bankAccountName'],//收款人姓名
            'middleName' => $orderData['bankAccountName'],//收款人姓名
            'lastName' => $orderData['bankAccountName'],//收款人姓名
            'mobile' => "9158838275",//10位手机号

        ];
        $signStr = $this->createSignStr($params);
        $params['sign'] = $this->getEncry($signStr, $this->params['merchantPrivateKey']);

        $this->logger->debug('向loroPay代付请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, ['Accept' => 'application/json', 'Content-Type' => 'application/json'], json_encode($params), ['timeout' => $this->timeout]);
        $this->logger->debug('loroPay代付回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        $res = json_decode($rsp->body, true);
        if ($rsp->status_code != 200 && isset($res['status']) && $res['status'] != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败：[http_code]:' . $rsp->status_code  . ', [resp_body]:' . trim($rsp->body);
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }

        // $sign = strtoupper(sha1(json_encode($res['order_rsp'], JSON_UNESCAPED_UNICODE) . '&key=' . $this->params['apiKey']));
        // if ($res['sign'] != $sign) {
        //     $output['status'] = 'Exception';
        //     $output['failReason'] = '返回数据验签失败：' . trim($rsp->body);
        //     $output['pushChannelTime'] = date('YmdHis');

        //     return $output;
        // }

        if ($res['status'] != '200') {
            $output['status'] = 'Fail';
            $output['failReason'] = 'loroPay代付失败，' . ($res['status'] ?? '') . ':' . ($res['message'] ?? '');
            $output['pushChannelTime'] = date('YmdHis');

            return $output;
        }


        $output['status'] = 'Success';
        $output['orderNo'] = $res['data']['platOrderNo'] ?? '';
        $output['failReason'] = '';
        $output['orderAmount'] = $res['data']['payAmount'];
        $output['pushChannelTime'] = date('YmdHis');

        return $output;
    }

    public function doSettlementCallback($orderData, $request)
    {
        $output = ['status' => '', 'orderStatus' => '', 'orderNo' => '', 'orderAmount' => 0, 'failReason' => ''];
        $arrParam = $request->getParams();

        if (!isset($arrParam['sign']) || !isset($arrParam['merchantNo']) || !isset($arrParam['amount'])) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调请求参数缺少必要参数';

            return $output;
        }

        if (isset($arrParam['merchantOrderNo']) && $arrParam['merchantOrderNo'] != $orderData['platformOrderNo']) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调商户订单号与订单不符';
            return $output;
        }

        $callbackSign = $arrParam['sign'];
        unset($arrParam['sign']);
        $signStr = $this->createSignStr($arrParam);
        $callSignStr = $this->getDecry($callbackSign,$this->params['platformPublicKey']);
        if ($signStr != $callSignStr) {
            $output['status'] = 'Fail';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调验签失败';

            return $output;
        }

        if (isset($arrParam['orderStatus']) && strtoupper($arrParam['orderStatus']) == 'SUCCESS') {
            $output['status'] = 'Success';
            $output['orderStatus'] = 'Success';
        } elseif (isset($arrParam['orderStatus']) && strtoupper($arrParam['orderStatus']) == 'FAILED') {
            $output['status'] = 'Success';
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = $arrParam['orderMessage'] ??'失败原因未知，请联系第三方确认';
        } else {
            $output['status'] = 'Fail';//回调不通过
            $output['orderStatus'] = 'Fail';
            $output['failReason'] = '回调通知缺少orderStatus或处理中';
        }

        $output['orderNo'] = $arrParam['merchantOrderNo'] ?? '';
        $output['orderAmount'] = $arrParam['amount'] ?? 0;
        $output['channelServiceCharge'] = $arrParam['fee'] ?? 0;

        return $output;
    }

    public function querySettlementOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/api/cash/query';

        $params = [
            'merchantNo' => $this->params['merchantNo'],
            'merchantOrderNo' => $platformOrderNo,
        ];
        $signStr = $this->createSignStr($params);
        $params['sign'] = $this->getEncry($signStr, $this->params['merchantPrivateKey']);
        $this->logger->debug('loropay代付查询请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, ['Accept' => 'application/json', 'Content-Type' => 'application/json'], json_encode($params), ['timeout' => $this->timeout]);
        $this->logger->debug('loropay代付订单查询回复：'. $platformOrderNo .'[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body);
        if ($rsp->status_code == 202) {
            $strBody = trim($rsp->body);
            $res = json_decode($strBody, true);
            if($res['status'] == 404 && $res['message'] == 'payout order not exist'){
                $output['status'] = 'Fail';
                $output['failReason'] = '第三方订单不存在：[message]:' . $res['message'];

                return $output;
            }
        }

        if ($rsp->status_code != 200) {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . $rsp->body;

            return $output;
        }

        $strBody = trim($rsp->body);
        $res = json_decode($strBody, true);
        if (!isset($res['status']) || $res['status'] != 200) {
            $output['status'] = 'Execute';
            $output['failReason'] = '第三方代付查询返回失败:' . $strBody;

            return $output;
        }

        if (isset($res['data']['orderStatus']) && strtoupper($res['data']['orderStatus']) == 'SUCCESS') {
            $output['status'] = 'Success';
            $output['failReason'] = $res['data']['orderMessage'] ?? '';
        } elseif (isset($res['data']['orderStatus']) && strtoupper($res['data']['orderStatus']) == 'FAILED') {
            $output['status'] = 'Fail';
            $output['failReason'] = ($res['data']['orderMessage'] ?? '') . '，失败原因未知，请联系第三方确认';
        } else {
            $output['status'] = 'Execute';
            $output['failReason'] = $res['data']['orderMessage'] ?? '';
        }

        $output['orderNo'] = $res['data']['platOrderNo'] ?? '';//上游订单号
        $output['orderAmount'] = $res['data']['amount'] ?? 0;

        return $output;
    }

    public function queryBalance()
    {
        $output = ['status' => '', 'balance' => 0, 'failReason' => ''];
        $path = '/api/balance';

        $params = [
            'merchantNo' => $this->params['merchantNo'],
            'timestamp' => time(),
        ];
        $signStr = $this->createSignStr($params);
        $params['sign'] = $this->getEncry($signStr,$this->params['merchantPrivateKey']);

        $this->logger->debug('向loroPay发起余额查询请求：' . $this->gateway . $path, $params);
        $rsp = Requests::post($this->gateway . $path, ['Accept' => 'application/json', 'Content-Type' => 'application/json'], json_encode($params), ['timeout' => $this->timeout]);
        $this->logger->debug('上游loroPay余额查询回复：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body));
        if ($rsp->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求异常：[status_code]:' . $rsp->status_code . ', [resp_body]:' . trim($rsp->body);

            return $output;
        }

        $strBody = trim($rsp->body);
        $res = json_decode($strBody, true);
        if (!isset($res['status']) || $res['status'] != '200') {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方余额查询失败：' . $strBody;

            return $output;
        }

        $balance = isset($res['data']['availableAmount']) ? $res['data']['availableAmount'] : 0;


        //成功
        $output['status'] = 'Success';
        $output['balance'] = $balance;
        $output['failReason'] = '余额查询成功：' . $strBody;
        return $output;
    }
}
