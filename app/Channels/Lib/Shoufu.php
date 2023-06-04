<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use App\Helpers\Tools;
use Requests;

class Shoufu extends Channels
{
    protected $payType = [

        'EBank' => 3,
        'OnlineWechatH5' => 1,
        'OnlineAlipayH5' => 2,
    ];

    public function getPayOrder($orderData)
    {
        $path = '/pay/create/yishoufu';
        $params = [
            'merchantOrderNumber' => (string)$orderData['platformOrderNo'],
            'amount' => intval($orderData['orderAmount'] * 100),
            'merchantId' => (string)$this->params['cId'],
            'returnUrl' => (string)$this->getPayCallbackUrl($orderData['platformOrderNo']),
        ];
        $str = '{';
        foreach ($params as $key => $val){
            if(in_array($key,['amount'])){
                $str .= '"' . $key .'":' . $val . ',';
            }else {
                $str .= '"' . $key . '":"' . $val . '",';
            }
        }
        $str = rtrim($str,',');
        $str .='}';

        //秘钥存入 token字段中
        $params['sign'] = (string)strtoupper(md5($str));
        $params['type'] = (string)$this->payType[$orderData['payType']];

        $req = Requests::post($this->gateway . $path, [], $params, ['timeout' => $this->timeout]);
        if ($req->status_code == 200) {
            $res = json_decode($req->body, true);
            if (isset($res['data']) && $res['data']) {
//                $qr = Tools::getQR($res['data']);
                return [
                    'status' => 'Success',
                    'payUrl' => $this->qrcodeUrl.$res['data'],
                    'orderNo' => '',
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


    public function isAllowPayOrderOrderAmountNotEqualRealOrderAmount(){
        return true;
    }

    public function doCallback($orderData, $request)
    {
        $params = $request->getParams();
        $sign = $params['sign'];
        $money = $params['amount'] / 100 ;
        $tmp = [
            "amount" => $params['amount'],
            "orderNumber" => $params['orderNumber'],
            "merchantId" => $this->params['cId'],
        ];
        $str = '{';
        foreach ($tmp as $key => $val){
            if($key == 'amount'){
                $str .= '"' . $key .'":' . $val . ',';
            }else {
                $str .= '"' . $key . '":"' . $val . '",';
            }
        }
        $str = rtrim($str,',');
        $str .='}';
        if (strtoupper($sign) != strtoupper(md5($str))) {
            return [
                'status' => 'Fail',
                'orderStatus' => 'Fail',
                'orderNo' => $params['orderNumber'],
                'orderAmount' => $money,
                'failReason' => '验签失败:' . json_encode($params),
                'attr' => $sign,
            ];
        }
        return [
            'status' => 'Success',
            'orderStatus' => $params['status'] == 0 ? 'Success' : 'Fail',
            'orderNo' => $params['orderNumber'],
            'orderAmount' => $money,
            'failReason' => json_encode($params),
        ];
    }

}
