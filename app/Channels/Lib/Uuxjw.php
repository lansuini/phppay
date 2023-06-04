<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

class Uuxjw extends Channels
{
    protected $payType = [

        'EBank' => 'bank',
        'Quick' => null,
        'OnlineWechatQR' => null,
        'OnlineAlipayQR' => 'alipay',

        'OnlineWechatH5' => null,
        'OnlineAlipayH5' => 'alipay',
        'QQPayQR' => null,
        'UnionPayQR' => null,
        'JDPayQR' => null,
        'EBankQR' => null,
    ];

    public function getPayOrder($orderData)
    {
        $path = '/api/shopApi/order/createorder2';
        $params = [
            'shopAccountId' => $this->params['cid'],
            'shopUserId' => $orderData['thirdUserId'],
            'amountInString' => $orderData['orderAmount'],
            'payChannel' => $this->payType[$orderData['payType']] ?? 'alipay',
            // 'payChannel' => 'wechat',
            'shopNo' => $orderData['platformOrderNo'],
            'shopCallbackUrl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            // 'returnUrl' => $orderData['frontNoticeUrl'],
            'target' => 3,
        ];

        $sign = md5($params['shopAccountId'] . $params['shopUserId'] . $params['amountInString'] . $params['shopNo'] . $params['payChannel'] . $this->params['apiKey']);
        $params['sign'] = $sign;
        $this->logger->debug('向上游发起支付请求：' . $this->gateway . $path, $params);
        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug('上游支付回复：[status_code]:' . $req->status_code . ', [resp_body]:' . $req->body);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['code']) && $res['code'] == 0) {
                return [
                    'status' => 'Success',
                    'payUrl' => $res['page_url'],
                    'orderNo' => $res['trade_no'],
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
        $sign = md5($params['shopAccountId'] . $params['user_id'] . $params['trade_no'] . $this->params['apiKey'] . $params['money'] . $params['type']);

        if ($sign != $params['sign']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['order_no'],
                'orderAmount' => $params['money'],
                'failReason' => '验签失败:' . json_encode($params),
                'attr' => $sign,
            ];
        }

        if ($orderData['platformOrderNo'] != $params['shop_no']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['order_no'],
                'orderAmount' => $params['money'],
                'failReason' => '回调订单号异常:' . json_encode($params),
                'attr' => $sign,
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params['status'] == 0 ? 'Success' : 'Fail',
            'orderNo' => $params['order_no'],
            'orderAmount' => $params['money'],
            'failReason' => json_encode($params),
        ];
    }

    public function queryOrder($platformOrderNo)
    {
        // $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/api/shopApi/order/queryorder';
        $params = [
            'out_trade_no' => $platformOrderNo,
            'shop_id' => $this->params['cid'],
        ];
        $sign = md5($params['shop_id'] . $params['out_trade_no'] . $this->params['apiKey']);
        $params['sign'] = strtolower($sign);
        print_r($params);
        $req = Requests::get($this->gateway . $path . '?' . http_build_query($params), [], ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['status']) && $res['status'] == 0) {
                $sign = md5($res['shop_no'] . $res['user_id'] . $res['trade_no'] . $this->params['apiKey'] . $res['money'] . $res['type']);
                if ($sign != $res['sign']) {
                    return [
                        'status' => 'Fail',
                        'orderAmount' => 0,
                        'orderNo' => '',
                        'failReason' => '验签失败:' . $req->body,
                        'attr' => $sign,
                    ];
                }

                return [
                    'status' => 'Success',
                    'orderAmount' => $res['money'],
                    'orderNo' => $res['order_no'],
                    'failReason' => $req->body,
                ];
            } else {
                $statusCode = [0 => 'Success', 1 => 'WaitPayment', 2 => 'Expired', 3 => 'Fail'];
                return [
                    'status' => isset($res['status']) && isset($statusCode[$res['status']]) ? $statusCode[$res['status']] : 'Fail',
                    'orderAmount' => $res['money'],
                    'orderNo' => $res['order_no'],
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

}
