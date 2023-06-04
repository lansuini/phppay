<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelPayConfig extends Model
{
    protected $table = 'channel_pay_config';

    protected $primaryKey = 'channelMerchantId';

    protected $fillable = [
        'bankCode',
        'cardType',
        'channel',
        'channelMerchantId',
        'channelMerchantNo',
        'payChannelStatus',
        'payType',
        'openTimeLimit',
        'beginTime',
        'endTime',
        'openOneAmountLimit',
        'oneMaxAmount',
        'oneMinAmount',
        'openDayAmountLimit',
        'dayAmountLimit',
        'openDayNumLimit',
        'dayNumLimit',
        'status',
    ];

    public function fetchConfig($channelMerchantNo, $payType, $payMoney, $bankCode, $cardType)
    {
        $existConfig = false;
        $st = intval(date("Hi"));
        $channelMerchantData = $this->getCacheByChannelMerchantNo($channelMerchantNo);

        if (empty($channelMerchantData)) {
            return true;
        }

        foreach ($channelMerchantData as $v) {

            if ($payType != $v['payType']) {
                continue;
            }

            if ($v['payChannelStatus'] != 'Normal') {
                continue;
            }

            if ($v['status'] != 'Normal') {
                continue;
            }

            if ($bankCode != $v['bankCode']) {
                continue;
            }

            if ($cardType != $v['cardType']) {
                continue;
            }

            if ($v['openOneAmountLimit'] && $v['oneMinAmount'] > 0 && $v['oneMinAmount'] > $payMoney) {
                continue;
            }

            if ($v['openOneAmountLimit'] && $v['oneMaxAmount'] > 0 && $v['oneMaxAmount'] < $payMoney) {
                continue;
            }

            if ($v['openTimeLimit'] && $v['beginTime'] != 0 && $st < $v['beginTime']) {
                continue;
            }

            if ($v['openTimeLimit'] && $v['endTime'] != 0 && $st > $v['endTime']) {
                continue;
            }

            if ($v['openDayAmountLimit'] && $this->getCacheByDayAmountLimit($v['channelMerchantNo'], $payType, $bankCode, $cardType) + $payMoney * 100 > $v['dayAmountLimit'] * 100) {
                continue;
            }

            if ($v['openDayNumLimit'] && $this->getCacheByDayNumLimit($v['channelMerchantNo'], $payType, $bankCode, $cardType) + 1 > $v['dayNumLimit']) {
                continue;
            }

            $existConfig = true;
        }
        return $existConfig;
    }
    public function getCacheByDayAmountLimit($channelMerchantNo, $payType, $bankCode, $cardType)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('channelPayConfig:tc:' . date('Ymd') . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType);
    }

    public function getCacheByDayNumLimit($channelMerchantNo, $payType, $bankCode, $cardType)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('channelPayConfig:c:' . date('Ymd') . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType);
    }

    public function incrCacheByDayAmountLimit($channelMerchantNo, $payType, $bankCode, $cardType, $payMoney)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('channelPayConfig:tc:' . date('Ymd') . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, $payMoney);
        $app->getContainer()->redis->expire('channelPayConfig:tc:' . date('Ymd') . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, 86400);
        return $num;
    }

    public function incrCacheByDayNumLimit($channelMerchantNo, $payType, $bankCode, $cardType, $num = 1)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('channelPayConfig:c:' . date('Ymd') . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, $num);
        $app->getContainer()->redis->expire('channelPayConfig:c:' . date('Ymd') . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, 86400);
        return $num;
    }

    public function getCacheByChannelMerchantNo($channelMerchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelPayConfig:n:" . $channelMerchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByChannelMerchantId($channelMerchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelPayConfig:i:" . $channelMerchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (!empty($param)) {
            $merchant = ChannelMerchant::where($param)->get();
        } else {
            $merchant = ChannelMerchant::all();
        }

        foreach ($merchant as $v) {
            $data = self::where("channelMerchantNo", $v->channelMerchantNo)->get();
            if (!empty($data)) {
                $data = $data->toArray();
                $redis->setex("channelPayConfig:n:" . $v->channelMerchantNo, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("channelPayConfig:i:" . $v->channelMerchantId, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
