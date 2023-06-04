<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantRate extends Model
{
    protected $table = 'merchant_rate';

    protected $primaryKey = 'rateId';

    protected $fillable = [
        'bankCode',
        'cardType',
        'channel',
        'beginTime',
        'endTime',
        'maxServiceCharge',
        'minServiceCharge',
        'merchantId',
        'merchantNo',
        'payType',
        'productType',
        'rateType',
        'rate',
        'fixed',
        'status',
    ];

    public function getServiceCharge($merchantRateData, $orderData, $productType)
    {
        $orderAmount = isset($orderData['realOrderAmount']) && $orderData['realOrderAmount'] > 0 ? $orderData['realOrderAmount'] : $orderData['orderAmount'];
        if (empty($merchantRateData) || empty($orderData)) {
            return null;
        }

        if ($productType == 'Settlement') {
            $orderData['payType'] = 'D0Settlement';
        }

        foreach ($merchantRateData as $k => $v) {

            if(!isset($v['merchantNo']) || !isset($orderData['merchantNo'])){
                continue;
            }

            if ($v['merchantNo'] != $orderData['merchantNo']) {
                continue;
            }

            if ($v['status'] != 'Normal') {
                continue;
            }

            if (!empty($v['beginTime']) && strtotime($v['beginTime']) > time()) {
                continue;
            }

            if (!empty($v['endTime']) && strtotime($v['endTime']) < time()) {
                continue;
            }

            if ($v['productType'] != $productType) {
                continue;
            }

            if ($v['payType'] != $orderData['payType']) {
                continue;
            }

            if ($productType == 'Pay' && !empty($orderData['cardType']) && $v['cardType'] != $orderData['cardType']) {
                continue;
            }

            if ($productType == 'Pay' && !empty($orderData['bankCode']) && $v['bankCode'] != $orderData['bankCode']) {
                continue;
            }


            $serviceCharge = 0;
            if ($v['rate'] > 0 || $v['fixed'] > 0 ) {
                if ($v['rateType'] == 'Rate') {
                    $serviceCharge = $v['rate'] * $orderAmount;
                } else if ($v['rateType'] == 'FixedValue') {
                    $serviceCharge = $v['fixed'];
                }else if ($v['rateType'] == 'Mixed') {
                    $serviceCharge = $v['rate'] * $orderAmount + $v['fixed'];
                } else {
                    continue;
                }
            }

            if ($v['minServiceCharge'] > 0 && $serviceCharge < $v['minServiceCharge']) {
                $serviceCharge = $v['minServiceCharge'];
            }

            if ($v['maxServiceCharge'] > 0 && $serviceCharge > $v['maxServiceCharge']) {
                $serviceCharge = $v['maxServiceCharge'];
            }

            return round($serviceCharge, 2);
        }
        return null;
    }

    public function getCacheByMerchantNo($merchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantRate:n:" . $merchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantId($merchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantRate:i:" . $merchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (!empty($param)) {
            // $merchant = self::where($param)->groupBy("merchantId")->get();
            $merchant = Merchant::where($param)->get();
        } else {
            // $merchant = self::groupBy("merchantId")->get();
            $merchant = Merchant::all();
        }

        foreach ($merchant as $v) {
            $data = self::where("merchantId", $v->merchantId)->get();
            if (!empty($data)) {
                $data = $data->toArray();
                $redis->setex("merchantRate:n:" . $v->merchantNo, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("merchantRate:i:" . $v->merchantId, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
