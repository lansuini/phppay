<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

/**
 * 亚汇支付
 * Class YHPay
 * @package App\Channels\Lib
 */
class YHPay extends Channels
{
    private $content;

    protected $payType = [
        'OnlineWechatPB' => 901,
        'OnlineWechatQR' => 926,
        'OnlineAlipayQR' => 927,
        'OnlineAlipayOriginalH5' => 941,
//        'OnlineWechatH5' => 901,
        'OnlineAlipayH5' => 904,
        'QQPhonePay' => 905,
        'UnionPay' => 928,//云闪付
        'QQPayQR' => 908,
        'BaiduWallet' => 909,
        'JDPayQR' => 910,
        'EBank' => 931,

    ];

    public function getPayOrder($orderData)
    {
        $path = '/Pay_Index.html';
        $params = [
            'pay_memberid' => $this->params['cId'],
            'pay_orderid' => $orderData['platformOrderNo'],
            'pay_applydate' => date('Y-m-d H:i:s'),
            'pay_bankcode' => $this->payType[$orderData['payType']],
            'pay_notifyurl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'pay_callbackurl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'pay_amount' => $orderData['orderAmount'],
        ];

        $params['pay_md5sign'] = $this->makeSign($params, $this->params['apiKey']);
        $params['pay_productname'] = 'GOODS';

        $path = $this->gateway . $path;
        $content = "<!DOCTYPE html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'/></head><body><form id = 'pay_form' action='{$path}' method='POST' accept-charset='UTF-8'>";

        foreach ($params as $key => $item) {
            $content .= "<input type='hidden' name='{$key}' value='{$item}'/>";
        }
        $content .= "</form></body><script type='text/javascript'>document.all.pay_form.submit();</script></html>";


//        $response = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
//
//        if ($response->status_code != 200) {
//            $output['status'] = 'Fail';
//            $output['failReason'] = '第三方请求失败:' . $response->body;
//            return $output;
//        }
//        var_dump($params,$this->gateway . $path);

        //如果发生错误返回json字串，如果成功返回自动调转的html
//        $rspBody = trim($response->body);
//        if (Tools::isJsonString($rspBody)) {
//
//            $arrRspBody = json_decode($rspBody, true);
//            $failReason = '第三方返回创建支付订单失败';
//            if (isset($arrRspBody['msg'])) {
//                $failReason .= '，msg：' . $arrRspBody['msg'];
//            }
//
//            $output['status'] = 'Fail';
//            $output['failReason'] = $failReason;
//            return $output;
//        }
//
//        if (!Tools::isHtmlString($rspBody)) {
//            $output['status'] = 'Fail';
//            $output['failReason'] = '第三方返回参数错误，' . $rspBody;
//            return $output;
//        }


        $output['orderNo'] = $orderData['platformOrderNo'];
        $output['status'] = 'Success';
        $output['payUrl'] = $this->getHtmlToUrl($orderData['platformOrderNo'], $content);
        $output['failReason'] = '';
        return $output;

    }

    public function successResponse($response)
    {
        return $response->write("ok");
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();

        if ($params['returncode'] != '00') {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['orderid'],
                'orderAmount' => $params['amount'],
                'failReason' => '付款失败！',
                'attr' => '',
            ];
        }


        if ($orderData['platformOrderNo'] != $params['orderid']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['orderid'],
                'orderAmount' => $params['amount'],
                'failReason' => '回调订单号异常:' . json_encode($params),
                'attr' => '',
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params['returncode'] == '00' ? 'Success' : 'Fail',
            'orderNo' => $params['orderid'],
            'orderAmount' => $params['amount'],
            'failReason' => json_encode($params),
        ];
    }

    public function queryOrder($platformOrderNo)
    {
        // $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/Pay_Trade_query.html';

        $params = [
            'pay_memberid' => $this->params['cId'],
            'pay_orderid' => $platformOrderNo,
        ];

        $params['pay_md5sign'] = $this->makeSign($params, $this->params['apiKey']);


        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            $statusCode = ['SUCCESS' => 'Success', 'NOTPAY' => 'Fail'];
            return [
                'status' => $res['returncode'] == '00' && isset($statusCode[$res['trade_state']]) ? $statusCode[$res['trade_state']] : 'Fail',
                'orderAmount' => $res['amount'],
                'orderNo' => $res['orderid'],
                'failReason' => $req->body,
            ];
        } else {
            return [
                'status' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function makeSign($params, $md5key)
    {

        ksort($params);
        $queryStr = '';
        foreach ($params as $key => $param) {
            $queryStr .= $key . "=" . $param . "&";
        }
        $queryStr .= "key=$md5key";
        return strtoupper(md5($queryStr));
    }

    //私钥解密
    public function decrypt($data, $key, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false)
    {
        $ret = '';
        $decryptedTemp = '';
        $data = base64_decode($data);
        if ($data !== false) {
            $key = $this->buildPrivateKey($key);
            $enArray = str_split($data, 128);
            foreach ($enArray as $va) {
                openssl_private_decrypt($va, $decryptedTemp, openssl_get_privatekey($key));//私钥解密
                $ret .= $decryptedTemp;
            }
        } else {
            return false;
        }
        if ($ret) $ret = substr($ret, 7);
        return $ret;
    }

    //RSA加密
    private function encodeRSA($param)
    {
        ksort($param);
        $content = '';
        foreach ($this->content as $k => $v) {
            if ("" != $v) {   //空值参数不参与加密
                $content .= $k . "=" . $v . "&";
            }
        }
        $content = substr($content, 0, -1);
        $public_key = openssl_get_publickey($this->buildPublicKey($this->params['appPublicKey']));

        $encrypt_data = '';
        $crypto = '';

        foreach (str_split($content, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encrypt_data, $public_key);
            $crypto = $crypto . $encrypt_data;
        }
        $crypto = base64_encode($crypto);

        return $crypto;

    }

    protected function buildPublicKey($key)
    {
        return "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
    }

    protected function buildPrivateKey($key)
    {
        return "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($key, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
    }

    /**
     * 签名  生成签名串  基于sha1withRSA
     */
    public function rsaSHA1Sign($privateKey)
    {
        $signPars = "";
        $signedStr = "";
        ksort($this->content);
        foreach ($this->content as $k => $v) {
            if ("" != $v) {   //空值参数不参与加密
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars = substr($signPars, 0, -1);
        openssl_sign($signPars, $signedStr, openssl_get_privatekey($this->buildPrivateKey($privateKey)));
        $encrypted = base64_encode($signedStr);
        return $encrypted;
    }

    //私钥解密
    public function decryptCommon($data, $key, $code = 'base64', $padding = OPENSSL_PKCS1_PADDING, $rev = false)
    {
        $ret = '';
        $data = base64_decode($data);
        if ($data !== false) {
            $key = $this->buildPrivateKey($key);
            $enArray = str_split($data, 256);
            foreach ($enArray as $va) {
                openssl_private_decrypt($va, $decryptedTemp, $key);//私钥解密
                $ret .= $decryptedTemp;
            }
        } else {
            return false;
        }

        return $ret;
    }

    private function urlStringToArray($data)
    {
        $reslut = [];
        foreach (explode('&', $data) as $v) {

            $val = explode('=', $v);
            $reslut[$val[0]] = $val[1];
        }
        return $reslut;
    }

}
