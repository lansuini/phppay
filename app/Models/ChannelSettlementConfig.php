<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelSettlementConfig extends Model
{
    protected $table = 'channel_settlement_config';

    protected $primaryKey = 'setId';

    protected $fillable = [
        'channel',
        'channelMerchantId',
        'channelMerchantNo',
        'settlementChannelStatus',
        'settlementAccountType',
        'accountBalance',
        'accountReservedBalance',
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

    public function fetchConfig($channelMerchantNo, $payMoney, $card, $settlementAccountType)
    {
        $existConfig = false;
        $st = intval(date("Hi"));
        $channelMerchantData = $this->getCacheByMerchantNo($channelMerchantNo);
        if (empty($channelMerchantData)) {
            return true;
        }
        foreach ($channelMerchantData as $v) {
            // if ($v['settlementChannelStatus'] != 'Normal') {
            //     continue;
            // }

            if ($v['status'] != 'Normal') {
                continue;
            }

            if ($settlementAccountType != $v['settlementAccountType']) {
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

            if ($v['openDayAmountLimit'] && $this->getCacheByDayAmountLimit($channelMerchantNo) + $payMoney * 100 > $v['dayAmountLimit'] * 100) {
                continue;
            }

            if ($v['openDayNumLimit'] && $this->getCacheByDayNumLimit($channelMerchantNo) + 1 > $v['dayNumLimit']) {
                continue;
            }

            if ($v['openOneSettlementMaxAmountLimit'] && $this->getCacheByCardDayAmountLimit($card, $channelMerchantNo) + $payMoney * 100 > $v['oneSettlementMaxAmount'] * 100) {
                continue;
            }

            if ($v['openCardDayNumLimit'] && $this->getCacheByCardDayNumLimit($card, $channelMerchantNo) + 1 > $v['cardDayNumLimit']) {
                continue;
            }

            $existConfig = true;
        }
        return $existConfig;
    }

    public function getRandConfig($fetchConfig)
    {
        if (empty($fetchConfig)) {
            return null;
        }

        $len = count($fetchConfig);
        $r = rand(0, $len - 1);
        return $fetchConfig[$r];
    }

    public function getCacheByDayAmountLimit($channelMerchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('channelSettleConfig:tc:' . date('Ymd') . ':' . $channelMerchantNo);
    }

    public function getCacheByDayNumLimit($channelMerchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('channelSettleConfig:c:' . date('Ymd') . ':' . $channelMerchantNo);
    }

    public function getCacheByCardDayAmountLimit($card, $channelMerchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('channelSettleConfig:cda:' . date('Ymd') . ':' . $card . ':' . $channelMerchantNo);
    }

    public function getCacheByCardDayNumLimit($card, $channelMerchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('channelSettleConfig:cdn:' . date('Ymd') . ':' . $card . ':' . $channelMerchantNo);
    }

    public function incrCacheByDayAmountLimit($channelMerchantNo, $payMoney)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('channelSettleConfig:tc:' . date('Ymd') . ':' . $channelMerchantNo, $payMoney);
        $app->getContainer()->redis->expire('channelSettleConfig:tc:' . date('Ymd') . ':' . $channelMerchantNo, 86400);
        return $num;
    }

    public function incrCacheByDayNumLimit($channelMerchantNo, $num = 1)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('channelSettleConfig:c:' . date('Ymd') . ':' . $channelMerchantNo, $num);
        $app->getContainer()->redis->expire('channelSettleConfig:c:' . date('Ymd') . ':' . $channelMerchantNo, 86400);
        return $num;
    }

    public function incrCacheByCardDayAmountLimit($card, $channelMerchantNo, $payMoney)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('channelSettleConfig:cda:' . date('Ymd') . ':' . $card . ':' . $channelMerchantNo, $payMoney);
        $app->getContainer()->redis->expire('channelSettleConfig:cda:' . date('Ymd') . ':' . $card . ':' . $channelMerchantNo, 86400);
        return $num;
    }

    public function incrCacheByCardDayNumLimit($card, $channelMerchantNo, $num = 1)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('channelSettleConfig:cdn:' . date('Ymd') . ':' . $card . ':' . $channelMerchantNo, $num);
        $app->getContainer()->redis->expire('channelSettleConfig:cdn:' . date('Ymd') . ':' . $card . ':' . $channelMerchantNo, 86400);
        return $num;
    }

    public function getCacheByMerchantNo($merchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelSettleConfig:n:" . $merchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantId($merchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelSettleConfig:i:" . $merchantId);
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
            $data = self::where("channelMerchantNo", $v->channelMerchantNo)->get();
            if (!empty($data)) {
                $data = $data->toArray();
                $redis->setex("channelSettleConfig:n:" . $v->channelMerchantNo, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("channelSettleConfig:i:" . $v->channelMerchantId, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
