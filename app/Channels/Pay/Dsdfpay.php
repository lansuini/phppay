<?php
namespace App\Channels\Pay;

use App\Channels\Channels;
use Requests;

class Dsdfpay extends Channels
{
    public function jumpToCustomerPay($orderData) {
        $path = '/customer_pay/init_din';
        $params = [
            'cid' => '',
            'uid' => $orderData['thirdUserId'],
            'time' => time(),
            'amount' => $orderData['orderAmount'],
            'order_id' => $orderData['platformOrderNo'],
            'ip' => $orderData['userIp'],
            'syncurl' => getenv('CB_DOMAIN') . '/pay/callback/' . $orderData['platformOrderNo'],
            // 'clevel' => '',
            // 'gflag' => '',
            // 'type' => '',
            // 'tflag' => '',
            // 'extend' => '',
        ];

        $data = "cid=" . $company_id . "&uid=" . $player_username .
         "&time=" . time() . "&amount=" . $amount . 
        "&order_id=" . $order_id . "&ip=" . $_SERVER['REMOTE_ADDR'];
        $dig64 =  base64_encode(hash_hmac('sha1', $data, $company_api_key, true));
        $reqdata = $data . "&type=remit&sign=" . $dig64;
        $url =  "https://www.dsdfpay.com/dsdf/customer_pay/init_din?" . $reqdata;

    }

    public function getPlaceOrder($orderData) {
        $path = '/api/place_order';
        $params = [
            'cid' => '',
            'uid' => '',
            'time' => time(),
            'amount' => ,
            'order_id' => $orderData['platformOrderNo'],
            'category' => '',
            'from_bank_flag' => 
        ];
    }

    protected function createParams($params)
    {
        return $params;
    }

    protected function createSign($params)
    {
        return 'I am sign';
    }

    protected function checkSign($params)
    {
        return true;
    }

    public function getStandardParam($orderData, $param)
    {
        return ['status' => 'Success', 'orderAmount' => $orderData['orderAmount'], 'failReason' => ''];
    }

    protected function doRequest($params, $sign)
    {
        try {
            $req = Requests::get($this->gateway . '/pay/successUrl?cb=' . $params['CB'], [], ['timeout' => $this->timeout]);
            $data = json_decode($req->body, true);
        } catch (\Exception $e) {
            return [];
        }

        return [
            'status' => 'Success',
            'failReason' => '',
            'payUrl' => $data['payUrl'],
            'orderNo' => $data['orderNo'],
        ];
    }
}
