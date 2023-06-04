<?php
namespace App\Channels\Settlement;

use App\Channels\ChannelsSettlement;
use Requests;

class MockTest extends ChannelsSettlement
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
            $req = Requests::get($this->gateway . '/settlement/successUrl?cb=' . $params['CB'], [], ['timeout' => $this->timeout]);
            echo $this->gateway . '/settlement/successUrl?cb=' . $params['CB'].PHP_EOL;
            // exit;
            $data = json_decode($req->body, true);
        } catch (\Exception $e) {
            return [];
        }

        return [
            'status' => 'Success',
            'orderNo' => $data['orderNo'],
            'failReason' => '',
        ];

        // return [
        //     'status' => 'Fail',
        //     'orderNo' => ',
        //     'failReason' => '啊啊',
        // ];

        // return [
        //     'status' => 'WaitTransfer',
        //     'orderNo' => '',
        //     'failReason' => '啊啊',
        // ];
    }

    public function queryBalance() {
        return 99999999;
    }
}
