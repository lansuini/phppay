<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantTransformRate extends Model
{
    protected $table = 'merchant_transform_rate';

    protected $primaryKey = 'rateId';

    protected $fillable = [
        'beginTime',
        'endTime',
        'maxServiceCharge',
        'minServiceCharge',
        'merchantId',
        'merchantNo',
        'rateType',
        'rate',
        'fixed',
        'status',
    ];

    //获取商户转换费率
    public function getServiceCharge($merchantRateData, $orderData)
    {
        if (empty($merchantRateData) || empty($orderData)) {
            return 0;
        }

        $serviceCharge = 0;
        if ($merchantRateData['rateType'] == 'Rate') {//按比例
            $serviceCharge = bcmul($orderData['money'], $merchantRateData['rate'], 3);
        } else if ($merchantRateData['rateType'] == 'FixedValue') {//固定值
            $serviceCharge = $merchantRateData['fixed'];
        }else if($merchantRateData['rateType'] == 'Mixed'){//混合
            $serviceCharge = bcadd($merchantRateData['fixed'], bcmul($orderData['money'], $merchantRateData['rate'], 3), 3);
        }

        if ($merchantRateData['minServiceCharge'] > 0 && $serviceCharge < $merchantRateData['minServiceCharge']) {
            $serviceCharge = $merchantRateData['minServiceCharge'];
        }

        if ($merchantRateData['maxServiceCharge'] > 0 && $serviceCharge > $merchantRateData['maxServiceCharge']) {
            $serviceCharge = $merchantRateData['maxServiceCharge'];
        }

        return round($serviceCharge, 2);
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
