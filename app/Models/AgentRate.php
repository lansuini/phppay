<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentRate extends Model
{
    protected $table = 'agent_rate';

    protected $primaryKey = 'rateId';

    protected $fillable = [
        'bankCode',
        'cardType',
        'channel',
        'beginTime',
        'endTime',
        'maxServiceCharge',
        'minServiceCharge',
        'agentId',
        'agentLoginName',
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

            if ($v['agentLoginName'] != $orderData['agentLoginName']) {
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
            if ($v['rate'] > 0) {
                if ($v['rateType'] == 'Rate') {
                    $serviceCharge = $v['rate'] * $orderAmount;
                } else if ($v['rateType'] == 'FixedValue') {
                    $serviceCharge = $v['rate'];
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

    public function getCacheByAgentLoginName($agentLoginName)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("agentRate:n:" . $agentLoginName);
        return $data ? json_decode($data, true) : [];
    }

    public function getCacheByAgentId($agentId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("agentRate:i:" . $agentId);
        return $data ? json_decode($data, true) : [];
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (!empty($param)) {
            // $merchant = self::where($param)->groupBy("merchantId")->get();
            $agent = Agent::where($param)->get();
        } else {
            // $merchant = self::groupBy("merchantId")->get();
            $agent = Agent::all();
        }

        foreach ($agent as $v) {
            $data = self::where("agentId", $v->id)->get();
            if (!empty($data)) {
                $data = $data->toArray();
                $redis->setex("agentRate:n:" . $v->loginName, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
                $redis->setex("agentRate:i:" . $v->id, 30 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
