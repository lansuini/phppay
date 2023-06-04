<?php

namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

/**
 * 大菠萝支付
 * Class DblPay
 * @package App\Channels\Lib
 */
class RXPay extends Channels
{

    protected $payType = [
//        'OnlineWechatPB' => 1801,
        'OnlineWechatQR' => 1802,
        'OnlineAlipayQR' => 1803,
        'OnlineWechatH5' => 1801,
        'OnlineAlipayH5' => 1804,
        'QQPhonePay' => 1805,
        'UnionPay' => 1807,
        'QQPayQR' => 1808,
        'BaiduWallet' => 1809,
        'JDPayQR' => 1810,

    ];

    public function getPayOrder($orderData)
    {
        $output = ['status' => '', 'payUrl' => '', 'orderNo' => '', 'failReason' => ''];

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

        $params['pay_md5sign'] = $this->makeSign($params,$this->params['apiKey']);
        $params['pay_productname'] = '烟酒';
        $this->logger->debug('向上游发起支付请求：' . $this->gateway . $path, $params);
        $response = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        $this->logger->debug('上游支付回复：[status_code]:' . $response->status_code . ', [resp_body]:' . $response->body);
        if ($response->status_code != 200) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方请求失败:' . $response->body;
            return $output;
        }

        //如果发生错误返回json字串，如果成功返回自动调转的html
        $rspBody = trim($response->body);

//        print_r($rspBody);exit;
        if (Tools::isJsonString($rspBody)) {
            $arrRspBody = json_decode($rspBody, true);
            $failReason = '第三方返回创建支付订单失败';
            if (isset($arrRspBody['msg'])) {
                $failReason .= '，msg：' . $arrRspBody['msg'];
            }

            $output['status'] = 'Fail';
            $output['failReason'] = $failReason;
            return $output;
        }

        if (!Tools::isHtmlString($rspBody)) {
            $output['status'] = 'Fail';
            $output['failReason'] = '第三方返回参数错误，' . $rspBody;
            return $output;
        }

        $output['orderNo'] = $orderData['platformOrderNo'];
        $output['status'] = 'Success';
        $output['payUrl'] = $this->getHtmlToUrl($orderData['platformOrderNo'], $rspBody);
        $output['failReason']=$rspBody;
//        print_r($output);
        return $output;

    }

    public function successResponse($response)
    {
        return $response->write("success");
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

        $params['pay_md5sign'] = $this->makeSign($params,$this->params['apiKey']);


        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            $statusCode = ['SUCCESS' => 'Success','NOTPAY' => 'Fail'];
            return [
                'status' => $res['returncode']=='00' && isset($statusCode[$res['trade_state']]) ? $statusCode[$res['trade_state']] : 'Fail',
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

    public function makeSign($params,$md5key){

        ksort($params);
        $queryStr = '';
        foreach ($params as $key=>$param){
            $queryStr .= $key . "=" . $param . "&";
        }
        $queryStr .= "key=$md5key" ;
        return strtoupper(md5($queryStr));
    }



}
