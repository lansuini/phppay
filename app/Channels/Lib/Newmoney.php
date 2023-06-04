<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

class Newmoney extends Channels
{
    protected $payType = [

        'EBank' => '3',
        'Quick' => null,
        'OnlineWechatQR' => null,
        'OnlineAlipayQR' => null,

        'OnlineWechatH5' => '2',
        'OnlineAlipayH5' => '1',
        'QQPayQR' => null,
        'UnionPayQR' => null,
        'JDPayQR' => null,
        'EBankQR' => null,
    ];

    public function getPayOrder($orderData)
    {
        $path = '/createorder';
        $params = [
            'merchantid' => $this->params['organizationNo'],
            'merchantorder' => $orderData['platformOrderNo'],
            'rmb' => $orderData['orderAmount'] * 100,
            'callback' => $this->getPayCallbackUrl($orderData['platformOrderNo']),
            'extend' => $orderData['tradeSummary'],
            'paytype' => $this->payType[$orderData['payType']] ?? 'alipay',
            'time' => $orderData['merchantReqTime'],
        ];

        $sign = $this->createSign($params, $this->params['apiKey']);
        $params['sign'] = $sign;
        // $this->logger->info('line===39', $params);
        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        // $this->logger->info('line===40', $rep);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['code']) && $res['code'] == 0) {
                return [
                    'status' => 'Success',
                    'payUrl' => $res['url'],
                    'orderNo' => $res['orderid'],
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
        return $response->write("0");
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        // $this->logger->info("line===77", $params);
        $returnSign = $params['sign'];
        unset($params['sign']);
        $sign = $this->createSign($params, $this->params['apiKey']);
        // $this->logger->info("line===81" . $sign);

        if ($sign != $returnSign) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['merchantorder'],
                'orderAmount' => $params['rmb'],
                'failReason' => '验签失败:' . json_encode($params),
                'attr' => $sign,
            ];
        }

        if ($orderData['platformOrderNo'] != $params['merchantorder']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['merchantorder'],
                'orderAmount' => $params['rmb'],
                'failReason' => '回调订单号异常:' . json_encode($params),
                'attr' => $sign,
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params['code'] == 0 ? 'Success' : 'Fail',
            'orderNo' => $params['merchantorder'],
            'orderAmount' => $params['rmb'] / 100,
            'failReason' => json_encode($params),
        ];
    }

    protected function createSign($params, $signKey)
    {
        $newParams = array_filter($params);
        ksort($newParams);
        $str = '';
        foreach ($newParams as $k => $v) {
            $str .= $k . "=" . $v . "&";
        }
        $str .= "key=" . $signKey;
        $this->logger->info("line===123" . $str);
        return strtolower(md5($str));
    }

}
