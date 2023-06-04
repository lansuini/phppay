<?php

namespace App\Models;

use App\Helpers\Tools;
use Illuminate\Database\Eloquent\Model;

class PlatformTransformOrder extends Model
{
    protected $table = 'platform_transform_order';

    protected $primaryKey = 'orderId';

    protected $fillable = [
        'fromMerchantId',
        'fromMerchantNo',
        'toMerchantId',
        'toMerchantNo',
        'platformOrderNo',
        'orderAmount',
        'serviceCharge',
    ];

    public static $voiceBalance = 5000;

    public function create($data)
    {
        $createdOrder = self::firstOrCreate($data);

//        $this->setCacheByPlatformOrderNo($platformOrderNo, $createdOrder);
        return $createdOrder;
    }

    public function getErrorMessage()
    {
        return $this->errors;
    }

    public function setCacheByPlatformOrderNo($platformOrderNo, $data)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex("settlementorder:" . $platformOrderNo, 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
        if (!empty($data['merchantOrderNo'])) {
            $redis->setex("settlementorder:m:" . $data['merchantNo'] . ":" . $data['merchantOrderNo'], 7 * 86400, $platformOrderNo);
        }
    }

    public function getCacheByPlatformOrderNo($platformOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("settlementorder:" . $platformOrderNo);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByMerchantOrderNo($merchantNo, $merchantOrderNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $platformOrderNo = $redis->get("settlementorder:m:" . $merchantNo . ":" . $merchantOrderNo);
        if (empty($platformOrderNo)) {
            return [];
        }
        return $this->getCacheByPlatformOrderNo($platformOrderNo);
    }
}
