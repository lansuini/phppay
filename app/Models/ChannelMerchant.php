<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelMerchant extends Model
{
    protected $table = 'channel_merchant';

    protected $primaryKey = 'channelMerchantId';

    protected $fillable = [
        'channelMerchantNo',
        'channel',
        'status',
        'platformId',
        'platformNo',
        // 'holidaySettlementMaxAmount',
        // 'holidaySettlementRate',
        // 'holidaySettlementType',
        // 'workdaySettlementMaxAmount',
        // 'workdaySettlementRate',
        // 'workdaySettlementType',
        'D0SettlementRate',
        'settlementTime',
        'oneSettlementMaxAmount',
        // 'openHolidaySettlement',
        'openPay',
        'openQuery',
        'openSettlement',
        // 'openWorkdaySettlement',
        'delegateDomain',
        'param',
    ];

    public function getCacheByChannelMerchantNo($channelMerchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelMerchant:n:" . $channelMerchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByChannelMerchantId($channelMerchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("channelMerchant:i:" . $channelMerchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (empty($param)) {
            $data = self::where('status','!=' ,'Deleted')->get();
        } else {
            $data = self::where($param)->where('status','!=' ,'Deleted')->get();
        }

        foreach ($data as $v) {
            $redis->setex("channelMerchant:n:" . $v->channelMerchantNo, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
            $redis->setex("channelMerchant:i:" . $v->channelMerchantId, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
        }
    }
}
