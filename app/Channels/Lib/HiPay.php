<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

/**
 * HiPay支付
 * Class HiPay
 * @package App\Channels\Lib
 */
class HiPay extends Channels
{

    protected $bank = [
        'BCOM' => 'COMM',
        'SPABANK' => 'PAB',
        'SRCB' => 'SHRCB',
        'SHB' => 'SHBANK',
        'NBCB' => 'NBBANK',
        'HZB' => 'HZCB',
        'BOB' => 'BJBANK',
        'HXB' => 'HXBANK',
        'PAB' => 'SPABANK',
    ];

    protected $payType = [
        'EBank' => null,
        'Quick' => null,
        'OnlineWechatQR' => null,
        'OnlineAlipayQR' => null,
        'OnlineWechatH5' => null,
        'OnlineAlipayH5' => null,
        'QQPayQR' => null,
        'UnionPayQR' => null,
        'JDPayQR' => null,
        'EBankQR' => null,
    ];

    public function getPayOrder($orderData)
    {
        $path = '/api/transaction';
        $params = [
            'out_trade_no' => $orderData['platformOrderNo'],
            'amount' => (int)$orderData['orderAmount'],
            'notify_url' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
        ];
        $bank=$orderData['bankCode'];


        if (isset($orderData['bankCode']) && in_array($orderData['bankCode'], $this->bank)) {
            $bank=$this->bank[$orderData['bankCode']];
        }
        $params['bank']=$bank;

        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout+10]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (!isset($res['code']) && !isset($res['message'])) {
                return [
                    'status' => 'Success',
                    'payUrl' => $res['uri'],
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

        if (strtolower($params['status']) != 'success') {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['out_trade_no'],
                'orderAmount' => $params['amount'],
                'failReason' => '付款失败！',
                'attr' => '',
            ];
        }


        if ($orderData['platformOrderNo'] != $params['out_trade_no']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['out_trade_no'],
                'orderAmount' => $params['amount'],
                'failReason' => '回调订单号异常:' . json_encode($params),
                'attr' => '',
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => strtolower($params['status']) == 'success' ? 'Success' : 'Fail',
            'orderNo' => $params['out_trade_no'],
            'orderAmount' => $params['amount'],
            'failReason' => json_encode($params),
        ];
    }

    public function queryOrder($platformOrderNo)
    {
        // $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = "/api/transaction/{$platformOrderNo}";


        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), '', ['timeout' => $this->timeout+10]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            $statusCode = ['success' => 'Success', 'progress' => 'Fail', 'timeout' => 'Fail'];
            return [
                'status' => isset($res['status']) && isset($statusCode[$res['status']]) ? $statusCode[$res['status']] : 'Fail',
                'orderAmount' => $res['amount'],
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

    public function getHeaderParams()
    {
        return ['Authorization' => 'Bearer ' . $this->params['apiKey'], 'Content-Type' => 'application/json'];
    }

}
