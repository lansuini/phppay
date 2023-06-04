<?php

namespace App\Models;

use App\Models\ChannelMerchant;
use Illuminate\Database\Eloquent\Model;

class MerchantChannelRecharge extends Model
{
    protected $table = 'merchant_channel_recharge';

    protected $primaryKey = 'setId';

    protected $fillable = [
        'merchantId',
        'merchantNo',
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

    public function fetchConfig($merchantNo, $merchantChannelData, $payType, $payMoney, $bankCode, $cardType, $channels)
    {
        $config = [];
        $st = intval(date("Hi"));
        $channelMerchant = new ChannelMerchant();
        $channelMerchantData = [];
        $channelPayConfig = new ChannelPayConfig();
        foreach ($merchantChannelData as $v) {
            if ($v['payChannelStatus'] != 'Normal') {
                continue;
            }

            if ($v['status'] != 'Normal') {
                continue;
            }

            if ($payType != $v['payType']) {
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

            if ($v['openDayAmountLimit'] && $this->getCacheByDayAmountLimit($merchantNo, $v['channelMerchantNo'], $payType, $bankCode, $cardType) + $payMoney * 100 > $v['dayAmountLimit'] * 100) {
                continue;
            }

            if ($v['openDayNumLimit'] && $this->getCacheByDayNumLimit($merchantNo, $v['channelMerchantNo'], $payType, $bankCode, $cardType) + 1 > $v['dayNumLimit']) {
                continue;
            }

            if (!isset($channelMerchantData[$v['channelMerchantNo']])) {
                $channelMerchantData[$v['channelMerchantNo']] = $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
            }

            if (empty($channelMerchantData[$v['channelMerchantNo']])) {
                continue;
            }

//            if ($channelMerchantData[$v['channelMerchantNo']]['openPay'] == false) {
//                continue;
//            }

            if ($channels[$channelMerchantData[$v['channelMerchantNo']]['channel']]['openPay'] == false) {
                continue;
            }

            if ($channelMerchantData[$v['channelMerchantNo']]['status'] == 'Close') {
                continue;
            }

            if (!$channelPayConfig->fetchConfig($v['channelMerchantNo'], $payType, $payMoney, $bankCode, $cardType)) {
                continue;
            }

            // $v['dbParam'] = $channelMerchantData[$v['channelMerchantNo']];
            $config[] = $v;
        }
        return $config;
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

    public function getCacheByDayAmountLimit($merchantNo, $channelMerchantNo, $payType, $bankCode, $cardType)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('merchantChannelRecharge:tc:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType);
    }

    public function getCacheByDayNumLimit($merchantNo, $channelMerchantNo, $payType, $bankCode, $cardType)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('merchantChannelRecharge:c:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType);
    }

    public function incrCacheByDayAmountLimit($merchantNo, $channelMerchantNo, $payType, $bankCode, $cardType, $payMoney)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('merchantChannelRecharge:tc:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, $payMoney);
        $app->getContainer()->redis->expire('merchantChannelRecharge:tc:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, 86400);
        return $num;
    }

    public function incrCacheByDayNumLimit($merchantNo, $channelMerchantNo, $payType, $bankCode, $cardType, $num = 1)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('merchantChannelRecharge:c:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, $num);
        $app->getContainer()->redis->expire('merchantChannelRecharge:c:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo . ':' . $payType . ':' . $bankCode . ':' . $cardType, 86400);
        return $num;
    }

    public function getCacheByMerchantNo($merchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantChannelRecharge:n:" . $merchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantId($merchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantChannelRecharge:i:" . $merchantId);
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
                $redis->setex("merchantChannelRecharge:n:" . $v->merchantNo, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("merchantChannelRecharge:i:" . $v->merchantId, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
