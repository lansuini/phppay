<?php
namespace App\Channels\Lib;

use App\Channels\Channels;

class InnerChannel extends Channels
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
            'orderNo' => '',
        ];
    }

    public function getPayOrder($orderData)
    {

        return [
            'status' => 'Success',
            'failReason' => '',
            'payUrl' => '',
            'orderNo' => '',
        ];
    }

    public function getSettlementOrder($orderData)
    {

        return [
            'status' => 'Success',
            'failReason' => '',
            'orderNo' => '',
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
