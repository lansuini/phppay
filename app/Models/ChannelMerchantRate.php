<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelMerchantRate extends Model
{
    protected $table = 'channel_merchant_rate';

    protected $primaryKey = 'rateId';

    protected $fillable = [
        'bankCode',
        'cardType',
        'channel',
        'beginTime',
        'endTime',
        'maxAmount',
        'minAmount',
        'maxServiceCharge',
        'minServiceCharge',
        'channelMerchantId',
        'channelMerchantNo',
        'payType',
        'productType',
        'rate',
        'fixed',
        'status',
    ];

    public function getServiceCharge($channelMerchantRateData, $orderData, $productType)
    {
        global $app;
        $logger = $app->getContainer()->logger;
        $orderAmount = isset($orderData['realOrderAmount']) && $orderData['realOrderAmount'] > 0 ? $orderData['realOrderAmount'] : $orderData['orderAmount'];
        if (empty($channelMerchantRateData) || empty($orderData)) {
            return null;
        }

        if ($productType == 'Settlement') {
            $orderData['payType'] = 'D0Settlement';
        }

        foreach ($channelMerchantRateData as $k => $v) {

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
            if( ($v['minAmount'] > 0 && $v['maxAmount'] > 0) && ($orderAmount < $v['minAmount'] || $orderAmount > $v['maxAmount'] )){
                $logger->debug('ChannelMerchantRate.getServiceCharge: '.json_encode($v));
                continue;
            }

            $serviceCharge = 0;
            if ($v['rate'] > 0) {
                if ($v['rateType'] == 'Rate') {
                    $serviceCharge = $v['rate'] * $orderAmount;
                } else if ($v['rateType'] == 'FixedValue') {
                    $serviceCharge = $v['fixed'];
                } else if ($v['rateType'] == 'Mixed') {
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

    public function getCacheByChannelMerchantNo($channelMerchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelMerchantRate:n:" . $channelMerchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByChannelMerchantId($channelMerchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelMerchantRate:i:" . $channelMerchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (!empty($param)) {
            // $merchant = self::where($param)->groupBy("merchantId")->get();
            $merchant = ChannelMerchant::where($param)->get();
        } else {
            // $merchant = self::groupBy("merchantId")->get();
            $merchant = ChannelMerchant::all();
        }

        foreach ($merchant as $v) {
            $data = self::where("channelMerchantId", $v->channelMerchantId)->get();
            if (!empty($data)) {
                $data = $data->toArray();
                $redis->setex("channelMerchantRate:n:" . $v->channelMerchantNo, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("channelMerchantRate:i:" . $v->channelMerchantId, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
