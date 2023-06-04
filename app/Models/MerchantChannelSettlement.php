<?php

namespace App\Models;

use App\Models\ChannelMerchant;
use Illuminate\Database\Eloquent\Model;

class MerchantChannelSettlement extends Model
{
    protected $table = 'merchant_channel_settlement';

    protected $primaryKey = 'setId';

    protected $fillable = [
        'merchantId',
        'merchantNo',
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

    public function fetchConfig($merchantNo, $merchantChannelData, $settlementType, $payMoney, $card, $channels = [], $bankCode = '')
    {
        global $app;
        $logger = $app->getContainer()->logger;
        $config = [];
        $st = intval(date("Hi"));
        $channelMerchant = new ChannelMerchant();
        $channelMerchantData = [];
        $settlementAccountType = 'UsableAccount';
        $channelSettlementConfig = new ChannelSettlementConfig();
        $settlementOpenType = 'bank';
        if($bankCode == "ALIPAY") {
            $settlementOpenType = 'alipay';
        }
        $logger_str = [];
        foreach ($merchantChannelData as $v) {
            if ($v['settlementChannelStatus'] != 'Normal') {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-settlementChannelStatus:'.$v['settlementChannelStatus'];
                continue;
            }

            if ($v['status'] != 'Normal') {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-status:'.$v['status'];
                continue;
            }

            // if ($payType != $v['payType']) {
            //     continue;
            // }

             if ($v['accountBalance'] != 0 && $payMoney > $v['accountBalance'] - $v['accountReservedBalance']) {
                 $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-accountBalance:'.$v['accountBalance'].'-accountReservedBalance:'.$v['accountReservedBalance'];
                 continue;
             }

            if ($settlementAccountType != $v['settlementAccountType']) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-settlementAccountType:'.$v['settlementAccountType'];
                continue;
            }

            if ($v['openOneAmountLimit'] && $v['oneMinAmount'] > 0 && $v['oneMinAmount'] > $payMoney) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-oneMinAmount:'.$v['oneMinAmount'];
                continue;
            }

            if ($v['openOneAmountLimit'] && $v['oneMaxAmount'] > 0 && $v['oneMaxAmount'] < $payMoney) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-oneMaxAmount:'.$v['oneMaxAmount'];
                continue;
            }

            if ($v['openTimeLimit'] && $v['beginTime'] != 0 && $st < $v['beginTime']) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-beginTime:'.$v['beginTime'];
                continue;
            }

            if ($v['openTimeLimit'] && $v['endTime'] != 0 && $st > $v['endTime']) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-endTime:'.$v['endTime'];
                continue;
            }

            if ($v['openDayAmountLimit'] && $this->getCacheByDayAmountLimit($merchantNo, $v['channelMerchantNo']) + $payMoney * 100 > $v['dayAmountLimit'] * 100) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-cacheDayAmountLimit:'.$this->getCacheByDayAmountLimit($merchantNo, $v['channelMerchantNo']).'-dayAmountLimit:'.$v['dayAmountLimit'];
                continue;
            }

            if ($v['openDayNumLimit'] && $this->getCacheByDayNumLimit($merchantNo, $v['channelMerchantNo']) + 1 > $v['dayNumLimit']) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-cacheDayNumLimit:'.$this->getCacheByDayNumLimit($merchantNo, $v['channelMerchantNo']).'-dayNumLimit:'.$v['dayNumLimit'];
                continue;
            }

            if (!isset($channelMerchantData[$v['channelMerchantNo']])) {
                $channelMerchantData[$v['channelMerchantNo']] = $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
            }

            if (empty($channelMerchantData[$v['channelMerchantNo']])) {
                continue;
            }

            if ($channels[$channelMerchantData[$v['channelMerchantNo']]['channel']]['openSettlement'] == false) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-openSettlement:'.$channels[$channelMerchantData[$v['channelMerchantNo']]['channel']]['openSettlement'];
                continue;
            }

            if(!array_key_exists($settlementOpenType, $channels[$channelMerchantData[$v['channelMerchantNo']]['channel']]['settlementType'])) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-settlementOpenType:'.$settlementOpenType;
                continue;
            }

            if ($channelMerchantData[$v['channelMerchantNo']]['status'] != 'Normal') {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-channelMerchantNo-status:'.$channelMerchantData[$v['channelMerchantNo']]['status'];
                continue;
            }

            if (!$channelSettlementConfig->fetchConfig($v['channelMerchantNo'], $payMoney, $card, $settlementAccountType)) {
                $logger_str[] = $v['merchantNo'].'-'.$v['channelMerchantNo'].'-card:'.$card;
                continue;
            }

            // $v['dbParam'] = $channelMerchantData[$v['channelMerchantNo']];
            $config[] = $v;
        }
        if(empty($config)){
            $logger->debug('merchantChannelSettlement.fetchConfig: '.implode(';', $logger_str));
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

    public function getCacheByDayAmountLimit($merchantNo, $channelMerchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('merchantChannelSettle:tc:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo);
    }

    public function getCacheByDayNumLimit($merchantNo, $channelMerchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('merchantChannelSettle:c:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo);
    }

    public function incrCacheByDayAmountLimit($merchantNo, $channelMerchantNo, $payMoney)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('merchantChannelSettle:tc:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo, $payMoney);
        $app->getContainer()->redis->expire('merchantChannelSettle:tc:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo, 86400);
        return $num;
    }

    public function incrCacheByDayNumLimit($merchantNo, $channelMerchantNo, $num = 1)
    {
        global $app;
        $num = (float) $app->getContainer()->redis->incrby('merchantChannelSettle:c:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo, $num);
        $app->getContainer()->redis->expire('merchantChannelSettle:c:' . date('Ymd') . ':' . $merchantNo . ':' . $channelMerchantNo, 86400);
        return $num;
    }

    public function getCacheByMerchantNo($merchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantChannelSettle:n:" . $merchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantId($merchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantChannelSettle:i:" . $merchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByChannelMerchantNo($channelMerchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantChannelSettle:cn:" . $channelMerchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByChannelMerchantId($channelMerchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantChannelSettle:ci:" . $channelMerchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function setCacheByChannelMerchantNo($channelMerchantNo,$data){
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex("merchantChannelSettle:cn:" . $channelMerchantNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
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
                $redis->setex("merchantChannelSettle:n:" . $v->merchantNo, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("merchantChannelSettle:i:" . $v->merchantId, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                foreach ($data as $t ){
                    $redis->setex("merchantChannelSettle:cn:" . $t['channelMerchantNo'], 30 * 86400, json_encode($t, JSON_UNESCAPED_UNICODE));
                    $redis->setex("merchantChannelSettle:ci:" . $t['channelMerchantId'], 30 * 86400, json_encode($t, JSON_UNESCAPED_UNICODE));
                }
           }
        }
    }
}
