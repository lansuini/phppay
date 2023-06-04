<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $table = 'merchant';

    protected $primaryKey = 'merchantId';

    public static $aliBanks = [
        'BOC' => '中国银行',
        'CCB' => '中国建设银行',
        'CMBC' => '中国民生银行',
        'ICBC' => '中国工商银行',
        'ABC' => '中国农业银行',
        'PSBC' => '中国邮政储蓄银行',
        'BCOM' => '中国交通银行',
        'CEB' => '中国光大银行',
        'CITIC' => '中信银行',
        'CMB' => '招商银行',
        'PAB' => '平安银行',
        'SPDB' => '浦发银行',
        'HXB' => '华夏银行',
        'CIB' => '兴业银行',
        'GDB' => '广东发展银行',
        'BOB' => '北京银行',
        'SHB' => '上海银行',
        'NBCB' => '宁波银行',
    ];

    protected $fillable = [
        'merchantNo',
        'fullName',
        'shortName',
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
        // 'openEntrustSettlement',
        // 'openHolidaySettlement',
        'openPay',
        'openQuery',
        'openSettlement',
        'openAliSettlement',
        // 'openWorkdaySettlement',

        'openBackNotice',
        'openCheckAccount',
        'openCheckDomain',
        'openFrontNotice',
        'signKey',
        'domain',
        'platformType',
        'backNoticeMaxNum',
        'description',
    ];

    public function merchantAmount()
    {
        //关联的模型类名, 关系字段
        return $this->hasOne('App\Models\MerchantAmount','merchantId');
    }

    public function create($request)
    {
        $createdOffer = self::firstOrCreate([
            'name' => $request->getParam('name'),
            'account' => $request->getParam('account'),
            'pass' => $request->getParam('pass'),
        ]);

        return $createdOffer;
    }

    //商户订单号唯一校验
    public function getCacheMerchantOrderNo($merchantNo, $merchantOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        //将哈希表 key 中的域 field 的值设置为 value ，当且仅当域 field 不存在。若域 field 已经存在，该操作无效。
        $data = $redis->hsetnx("merchant:orderno:" . $merchantNo, $merchantOrderNo, date('Y-m-d H:i:s'));
        return $data == 1 ? true : false;
    }

    public function getCacheByMerchantNo($merchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchant:n:" . $merchantNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantId($merchantId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchant:i:" . $merchantId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (empty($param)) {
            $data = self::all();
        } else {
            $data = self::where($param)->get();
        }

        foreach ($data as $v) {
            $redis->setex("merchant:n:" . $v->merchantNo, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
            $redis->setex("merchant:i:" . $v->merchantId, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
        }
    }

    public function incrCacheByDaySettleAmountLimit($merchantNo, $settleMoney)
    {
        global $app;
        $amount = (float) $app->getContainer()->redis->incrby('merchant:settle:tc:' . date('Ymd') . ':' . $merchantNo, $settleMoney);
        $app->getContainer()->redis->expire('merchant:settle:tc:' . date('Ymd') . ':' . $merchantNo, 86400);
        return $amount;
    }

    public function getCacheByDaySettleAmountLimit($merchantNo)
    {
        global $app;
        return (float) $app->getContainer()->redis->get('merchant:settle:tc:' . date('Ymd') . ':' . $merchantNo);
    }

    public function isExceedDaySettleAmountLimit($settleAmount, $merchantData)
    {
        if ($this->getCacheByDaySettleAmountLimit($merchantData['merchantNo']) + $settleAmount * 100 > $merchantData['oneSettlementMaxAmount'] * 100) {
            return true;
        }

        return false;
    }
}
