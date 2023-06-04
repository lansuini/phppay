<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class Wuxingzhifu extends Channels
{
    protected $statusCode = [
        'S' => 'Success',
        'F' => 'Fail',
        'P' => 'Execute',
    ];

    protected $payType = [
        // 'webBank', //网银
        // 'webBankFast', //网银快捷
        // 'webBankScanCode', //银联扫码
        // 'webBankWAP', //网银WAP
        // 'weChatScanCode', //微信扫码
        // 'weChatWAP', //微信WAP
        // 'aliPayScanCode', //支付宝扫码
        // 'aliPayWAP', //支付宝WAP
        // 'qqScanCode', //QQ扫码
        // 'qqWAP', //QQWAP
        // 'jdScanCode', //京东钱包扫码
        // 'baiduScanCode', //百度钱包扫码WAP

        'EBank' => 'webBank',
        'Quick' => 'webBankFast',
        'OnlineWechatQR' => 'weChatScanCode',
        'OnlineAlipayQR' => 'aliPayScanCode',

        'OnlineWechatH5' => 'weChatWAP',
        'OnlineAlipayH5' => 'aliPayWAP',
        'QQPayQR' => 'qqScanCode',
        'UnionPayQR' => 'webBankScanCode',
        'JDPayQR' => 'jdScanCode',
        'EBankQR' => 'webBankScanCode',
    ];

    public function queryOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/api/merchant/order_query';
        $params = [
            'orderId' => $platformOrderNo,
            'orderType' => 'pay',
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($this->checkSign($res)) {
                $output['status'] = $this->statusCode[$res['status']] ?? 'Exception';
                $output['orderAmount'] = $res['amount'] ?? 0;
                $output['orderNo'] = $res['orderId'] ?? "";
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败';
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }
        return $output;
    }

    public function getSettlementOrder($orderData)
    {
        $output = ['status' => '', 'orderNo' => '', 'failReason' => '', 'orderAmount' => 0];
        $path = '/api/merchant/pay';
        $params = [
            'amount' => $orderData['orderAmount'],
            'charset' => 'utf-8',
            'currency' => '156',
            'notifyUrl' => $this->getSettlementCallbackUrl($orderData['platformOrderNo']),
            'orderId' => $orderData['platformOrderNo'],
            'remark' => '',
            'toBankAccName' => $orderData['bankAccountName'],
            'toBankAccNumber' => Tools::decrypt($orderData['bankAccountNo']),
            'toBankBranch' => $orderData['bankName'],
            'toBankCity' => $orderData['city'],
            'toBankCode' => $orderData['bankCode'],
            'toBankProvince' => $orderData['province'],
            'version' => '1.00',
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        $this->logger->debug($this->gateway . $path, $params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);

            if ($this->checkSign($res)) {
                $output['status'] = 'Success';
                $output['orderNo'] = '';
                $output['failReason'] = '';
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '返回值数据验签失败' . $req->body;
            }
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
        }

        return $output;
    }

    public function queryBalance()
    {
        $output = ['status' => '', 'balance' => 0, 'failReason' => ''];
        $path = '/api/merchant/balance_query';
        $params = [
            'signTime' => date('YmdHis'),
            'signType' => 'hmacsha256',
        ];

        $sign = $this->createSign($params);
        $params['sign'] = $sign;
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
//            echo $req->body;
            $res = json_decode($req->body, true);
            // if ($this->checkSign($res)) {
            //     $output['status'] = 'Success';
            //     $output['balance'] = $res['balance'];
            // } else {
            //     $output['status'] = 'Fail';
            //     $output['failReason'] = '返回值数据验签失败'.json_encode($res);
            // }

            $output['status'] = 'Success';
            $output['balance'] = $res['balance'] ?? 0;
        } else {
            $res = json_decode($req->body, true);
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . (isset($res['code']) ? $res['code'] : '') . ':' . (isset($res['msg']) ? $res['msg'] : '');
            $output['attr'] = $this->gateway . $path . $req->body;
        }
        return $output;
    }

    protected function getHeaderParams()
    {
        return ['api_key' => $this->params['apiKey'], 'Content-Type' => 'application/json'];
    }

    protected function createSign($params)
    {
        // $newParam = array_filter($param);
        $newParam = $params;
        if (!empty($newParam)) {
            $fields = array_keys($newParam);
            $sortParam = [];
            sort($fields);
            foreach ($fields as $k => $v) {
                $sortParam[] = $v . '=' . $newParam[$v];
            }
            $originalString = implode('&', $sortParam);
        } else {
            $originalString = '';
        }
        // $c = hash_hmac('sha256', $originalString, $key);
        return hash_hmac('sha256', $originalString, $this->params['apiSerect']);
    }

    protected function checkSign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $origin = $this->createSign($params);
        return $origin == $sign ? true : false;
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        if (!$this->checkSign($params)) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $request->getParam('orderId'),
                'orderAmount' => $request->getParam('amount'),
                'failReason' => '',
            ];
        }

        if ($orderData['platformOrderNo'] != $request->getParam('merchantOrderId')) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $request->getParam('orderId'),
                'orderAmount' => $request->getParam('amount'),
                'failReason' => '回调订单号异常:' . json_encode($params),
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $this->statusCode[$request->getParam('status')],
            'orderNo' => $request->getParam('orderId'),
            'orderAmount' => $request->getParam('amount'),
            'failReason' => '',
        ];

    }
}
