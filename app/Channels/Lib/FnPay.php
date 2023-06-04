<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

/**
 * 蜂鸟支付
 * Class FnPay
 * @package App\Channels\Lib
 */
class FnPay extends Channels
{
    private $content;

    protected $payType = [
        'EBank' => null,
        'Quick' => null,
        'OnlineWechatQR' => 'wx_scan_1',
        'OnlineAlipayQR' => 'ali_scan',
        'OnlineWechatH5' => 'wx_h5',
        'OnlineAlipayH5' => 'ali_h5',
        'QQPayQR' => null,
        'UnionPayQR' => null,
        'JDPayQR' => null,
        'EBankQR' => null,
    ];

    public function getPayOrder($orderData)
    {
        $path = '/businessgate/bxpay';
        $params = [
            'partner' => $this->params['cId'],
            'input_charset' => "utf-8",
            'sign_type' => "SHA1WITHRSA",
            'request_time' => date('ymdhis', time()),
        ];

        $this->content = [
            'out_trade_no' => $orderData['platformOrderNo'],
            'amount' => $orderData['orderAmount'],
            'pay_type' => $this->payType[$orderData['payType']],
            'subject' => "GOODS",
            'sub_body' => "PAY",
            'notify_url' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
        ];

        //秘钥存入 token字段中

        $params['content'] = $this->encodeRSA($this->content);

        $params['sign'] = $this->rsaSHA1Sign($this->params['apiKey']);
        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
//            var_dump($res);
            if (isset($res['is_succ']) && $res['is_succ'] == "T") {
                $payurl = $this->decrypt($res['response'], $this->params['apiKey']);
                return [
                    'status' => 'Success',
                    'payUrl' => $payurl,
                    'orderNo' => $orderData['platformOrderNo'],
                    'failReason' => $req->body,
                ];
            } else {
                return [
                    'status' => 'Fail',
                    'payUrl' => '',
                    'orderNo' => '',
                    'failReason' => '第三方请求失败:' . $req->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'payUrl' => '',
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function successResponse($response)
    {
        return $response->write("success");
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        $resStr = $this->decryptCommon($params['response'], $this->params['apiKey']);
        $resData = $this->urlStringToArray($resStr);

        if ($resData['pay_status'] != 1) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['out_trade_no'],
                'orderAmount' => $resData['money'],
                'failReason' => '付款失败！',
                'attr' => '',
            ];
        }


        if ($orderData['platformOrderNo'] != $params['out_trade_no']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['out_trade_no'],
                'orderAmount' => $resData['money'],
                'failReason' => '回调订单号异常:' . json_encode($params),
                'attr' => '',
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params['pay_status'] == 1 ? 'Success' : 'Fail',
            'orderNo' => $params['out_trade_no'],
            'orderAmount' => $resData['money'],
            'failReason' => json_encode($params),
        ];
    }

    public function queryOrder($platformOrderNo)
    {
        // $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/businessgate/order';

        $cont = [
            'out_trade_no' => $platformOrderNo,
        ];

        $params = [
            'content' => $this->encodeRSA($cont),
            'input_charset' => 'UTF-8',
            'partner' => $this->params['cId'],
        ];
        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            $statusCode = [1 => 'Success', 0 => 'Fail'];
            return [
                'status' => isset($res['paytype']) && isset($statusCode[$res['paytype']]) ? $statusCode[$res['paytype']] : 'Fail',
                'orderAmount' => $res['money'],
                'orderNo' => $res['out_trade_no'],
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
