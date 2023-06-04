<?php
namespace App\Channels\Pay;

use App\Channels\Channels;
use Requests;

class MockTest extends Channels
{
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
