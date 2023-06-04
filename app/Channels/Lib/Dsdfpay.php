<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

class Dsdfpay extends Channels
{
    protected $payType = [

        'EBank' => ['type' => 'online'],
        'Quick' => ['type' => 'quick'],
        'OnlineWechatQR' => ['type' => 'qrcode', 'tflag' => 'WebMM'],
        'OnlineAlipayQR' => ['type' => 'qrcode', 'tflag' => 'ALIPAY'],

        'OnlineWechatH5' => ['type' => 'qrcode', 'tflag' => 'WebMM'],
        'OnlineAlipayH5' => ['type' => 'qrcode', 'tflag' => 'ALIPAY'],
        'QQPayQR' => ['type' => 'qrcode', 'tflag' => 'QQPAY'],
        'UnionPayQR' => ['type' => 'qrcode', 'tflag' => 'UNIPAY'],
        'JDPayQR' => ['type' => 'qrcode', 'tflag' => 'JDPAY'],
        'MeiTuanQR' => ['type' => 'qrcode', 'tflag' => 'MEITUAN'],
        'EBankQR' => ['type' => 'online'],
    ];

    public function getNotDirectPayOrder($orderData)
    {
        $path = '/dsdf/customer_pay/init_din';
        $params = [
            'cid' => $this->params['cid'],
            'uid' => $orderData['thirdUserId'],
            'time' => time(),
            'amount' => $orderData['orderAmount'],
            'order_id' => $orderData['platformOrderNo'],
            'ip' => $orderData['userIp'],
            'syncurl' => $orderData['frontNoticeUrl'],
        ];

        if ($this->payType[$orderData['payType']]) {
            $params = array_merge($params, $this->payType[$orderData['payType']]);
            if (!empty($orderData['bankCode'])) {
                $params['tflag'] = $orderData['bankCode'];
            }
        } else {
            // $params['type'] = 'remit';
        }

        $data = "cid={$this->params['cid']}&uid={$this->params['cid']}&time={$params['time']}&amount={$params['amount']}&order_id={$params['order_id']}&ip={$params['ip']}";

        $dig64 = base64_encode(hash_hmac('sha1', $data, $this->params['apiKey'], true));
        $reqdata = $data . "&sign={$dig64}";
        $url = $this->gateway . $path . "?" . $reqdata;
        return [
            "status" => "Success",
            'payUrl' => $url,
            'orderNo' => $this->emptyOrderNo,
            'failReason' => '',
        ];
    }

    public function getPayOrderHahahahaha($orderData)
    {
        $path = '/dsdf/api/place_order';
        $params = [
            'cid' => $this->params['cid'],
            'uid' => $orderData['thirdUserId'],
            'time' => time(),
            'amount' => $orderData['orderAmount'],
            'order_id' => $orderData['platformOrderNo'],
            // 'ip' => $orderData['userIp'],
            'category' => '',
            'from_bank_flag' => '',
            // 'qsign' => '',
        ];

        if ($this->payType[$orderData['payType']]) {
            $params['category'] = $this->payType[$orderData['payType']]['type'];
            $params['from_bank_flag'] = $this->payType[$orderData['payType']]['tflag'] ?? '';

            if (!in_array($params['category'], ['remit', 'qrcode'])) {
                return [
                    'status' => 'Fail',
                    'payUrl' => '',
                    // 'orderAmount' => 0,
                    'orderNo' => '',
                    'failReason' => '不支持的支付类型',
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'payUrl' => '',
                // 'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '不支持的支付类型',
            ];
        }
        $data = json_encode($params);
        echo $data, PHP_EOL;
        $dig = base64_encode(hash_hmac('sha1', $data, $this->params['apiKey'], true));
        $headers = ['Content-Hmac' => $dig, 'Content-Type' => 'application/json'];
        print_r($params);
        $req = Requests::post($this->gateway . $path, $headers, json_encode($params), ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if ($res['success']) {
                return [
                    'status' => 'Success',
                    'payUrl' => $res['data']['qrurl'],
                    // 'orderAmount' => $orderData['orderAmount'],
                    'orderNo' => $this->emptyOrderNo,
                    'failReason' => $res->body,
                ];
            } else {
                return [
                    'status' => 'Fail',
                    'payUrl' => '',
                    // 'orderAmount' => 0,
                    'orderNo' => '',
                    'failReason' => '第三方请求失败:' . $req->body,
                ];
            }
        } else {
            return [
                'status' => 'Fail',
                'payUrl' => '',
                // 'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '第三方请求失败:' . $req->body,
            ];
        }
    }

    public function successResponse($response)
    {
        return $response->write("true");
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        $dig = base64_encode(hash_hmac('sha1', file_get_contents("php://input"), $this->params['apiKey'], true));
        if ($dig != $request->getHeader('Content-Hmac')) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderAmount' => 0,
                'orderNo' => '',
                'failReason' => '验签失败:' . $req->body,
                // 'hmac' => $request->getHeader('Content-Hmac'),
            ];
        }
        $status = ['verified' => 'Success', 'timeout' => 'Fail', 'revoked' => 'Fail'];
        $payType = 'Ebank';

        foreach ($this->payType as $pt => $val) {
            if ($val['type'] == $params['type']) {
                $payType = $pt;
            }

            if (isset($val['tflag']) && $val['tflag'] == $params['customer_bankflag']) {
                $payType = $pt;
            }
        }

        if ($orderData['platformOrderNo'] != $params['order_id']) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['order_id'],
                'orderAmount' => $params['money'],
                'failReason' => '回调订单号异常:' . json_encode($params),
            ];
        }

        return [
            'status' => 'Success',
            'orderStatus' => $params[$status] ?? 'Fail',
            'orderNo' => $this->emptyOrderNo,
            'orderAmount' => $params['amount'] / 100,
            'failReason' => json_encode($params),
            'payType' => $payType,
        ];
    }

    public function queryPayOrder($platformOrderNo)
    {
        $output = ['status' => '', 'orderAmount' => '', 'orderNo' => '', 'failReason' => ''];
        $path = '/dsdf/api/query_order';
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

}
