<?php
namespace App\Channels\Lib;

use App\Channels\Channels;
use Requests;

class MockTest extends Channels
{
    public function queryBalance()
    {
        return [
            'status' => 'Success',
            'failReason' => '',
            'balance' => 999999,
        ];
    }

    public function queryOrder($platformOrderNo)
    {
        return [
            'status' => 'Success',
            'failReason' => '',
            'orderAmount' => 0,
            'orderNo' => 'ABCD' . date('YmdHis') . rand(100000, 99999),
        ];
    }

    public function getPayOrder($orderData)
    {
        try {
            $req = Requests::get($this->gateway . '/pay/successUrl?cb=' .
                $this->getPayCallbackUrl($orderData['platformOrderNo']), [],
                ['timeout' => $this->timeout]);
            $data = json_decode($req->body, true);
        } catch (\Exception $e) {
            return [
                'status' => 'Fail',
                'failReason' => $e->getMessage(),
                'payUrl' => '',
                'orderNo' => '',
            ];
        }

        return [
            'status' => 'Success',
            'failReason' => '',
            'payUrl' => $data['payUrl'],
            'orderNo' => $data['orderNo'],
        ];
    }

    public function getSettlementOrder($orderData)
    {
        try {
            $req = Requests::get($this->gateway . '/settlement/successUrl?cb=' .
                $this->getSettlementCallbackUrl($orderData['platformOrderNo']), [],
                ['timeout' => $this->timeout]);
            $data = json_decode($req->body, true);
        } catch (\Exception $e) {
            return [
                'status' => 'Exception',
                'failReason' => $e->getMessage(),
                'orderAmount' => 0,
                'orderNo' => '',
            ];
        }

        return [
            'status' => 'Success',
            'failReason' => '',
            'orderNo' => isset($data['orderNo']) ? $data['orderNo'] : '',
            'orderAmount' => 0,
        ];
    }

    public function doCallback($orderData, $request)
    {
        return [
            'status' => 'Success',
            'orderStatus' => 'Success',
            'orderNo' => '',
            'orderAmount' => 0,
            'failReason' => '',
        ];
    }
}
