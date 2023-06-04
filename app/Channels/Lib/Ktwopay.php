<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

class Ktwopay extends Channels
{
    protected $statusCode = [
        'S' => 'Success',
        'F' => 'Fail',
        'P' => 'Execute',
    ];


    protected $payType = [
        'OfflineWechatQR' => '200',
        'OnlineWechatQR' => '200',
        'OnlineWechatH5' => '200',
        'OfflineAlipayQR' => '100',
        'OnlineAlipayQR' => '100',
        'OnlineAlipayH5' => '100',
        'EBank' => '300',
    ];



    public function getPayOrder($orderData)
    {
        $path = '/api/order/create';
        $params = [
            'thirdOrderId' => $orderData['platformOrderNo'],
            'thirdUserId' => $this->params['cId'],
            'total' => $orderData['orderAmount'],
            'tradeType' => '1',
            'remark' => '',
            'callBackUrl' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'payMethodCode' => $this->payType[$orderData['payType']] ?? '100',
            'key' => $this->params['apiKey'],
            'currentStamp' => time(),
        ];

        $sign = $this->createSign($params, $this->params['apiSecret']);

        $params['sign'] = $sign;
        /* $this->logger->info("====line 39==   ", $params);
        dump($this->gateway . $path);exit; */

        $req = Requests::post($this->gateway . $path, ['Accept' => 'application/json', 'Content-Type' => 'application/json'], json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {

            $res = json_decode($req->body, true);
            if (isset($res['code']) && $res['code'] == 200) {
                return [
                    'status' => 'Success',
                    'payUrl' => $res['data']['pay']['payUrl'],
                    'orderNo' => isset($res['data']['trade']['tradeNo']) ? $res['data']['trade']['tradeNo'] : '',
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
        $paramsSign = $params['sign'];
        unset($params['sign']);
        $sign = $this->createSign($params, $this->params['apiSecret']);

        if ($sign != $paramsSign) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => '',
                'orderAmount' => 0,
                'failReason' => '验签失败:' . json_encode($params),
                'attr' => $sign,
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params['status'] == 1 ? 'Success' : 'Fail',
            'orderNo' => $params['tradeNo'],
            'orderAmount' => $params['total'],
            'failReason' => json_encode($params),
        ]; 
    }

    public function queryOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/api/order/getTradeDetail';
        $params = [
            'thirdOrderId' => $platformOrderNo,
            'key' => $this->params['apiKey'],
            'currentStamp' => time(),
        ];

        $sign = $this->createSign($params, $this->params['apiSecret']);
        $params['sign'] = $sign;
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['code']) && $res['code'] == 200) {
                $output['status'] = $this->statusCode[$res['status']] ?? 'Exception';
                $output['orderAmount'] = $res['data']['trade']['total'] ?? 0;
                $output['orderNo'] = $res['data']['trade']['tradeNo'] ?? "";
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '第三方请求失败:' . $req->body;
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
        $path = '/api/order/create';

        $params = [
            'thirdOrderId' => $orderData['merchantOrderNo'],
            'thirdUserId' => $this->params['cId'],
            'total' => $orderData['orderAmount'],
            'tradeType' => '0',
            'remark' => '',
            'payMethodCode' => '300',
            'bankOpen' => $orderData['bankName'],
            'account' => $orderData['bankAccountNo'],
            'accountName' => $orderData['bankAccountName'],
            'key' => $this->params['apiKey'],
            'currentStamp' => time(),
        ];
        $sign = $this->createSign($params, $this->params['apiSecret']);
        $params['sign'] = $sign;
        $this->logger->debug('向上游发起代付请求：' . $this->gateway . $path, $params);
        $req = Requests::post($this->gateway . $path, $this->getHeaderParams(), json_encode($params), ['timeout' => $this->timeout]);

        $this->logger->debug('上游代付回复：[status_code]:' . $req->status_code . ', [resp_body]:' . $req->body);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['code']) && $res['code'] == 200) {
                $output['status'] = 'Success';
                $output['orderNo'] = $res['data']['trade']['tradeNo'];
                $output['failReason'] = $req->body;
            } else {
                $output['status'] = 'Fail';
                $output['failReason'] = '第三方请求失败' . $req->body;
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
            echo $req->body;
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

    protected function createSign($params, $signKey)
    {
        if(!empty($params)){
            unset($params['key']);
            $p =  ksort($params);
            if($p){
                $str = '';
                foreach ($params as $k=>$val){
                    $str .= $k .'=' . $val . '&';
                }
                $strs = rtrim($str, '&');
                $originalString = $strs.'&secret='.$signKey;
            }
         } else {
            $originalString = $signKey;
         }
         return md5($originalString);

    }

    protected function checkSign($params)
    {
        $sign = $params['sign'];
        unset($params['sign']);
        $origin = $this->createSign($params);
        return $origin == $sign ? true : false;
    }

}
