<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $table = 'agent';

    protected $primaryKey = 'id';


    protected $fillable = [
        'loginName',
        'nickName',
        'loginPwd',
        'securePwd',
        'balance',
        'freezeBalance',
        'bailBalance',
        'inferisorNum',
        'settleAccWay',
        'settleAccRatio',
        'status',
        'loginIP',
        'loginDate',
        'loginIpWhite',
        'created_at',
        'updated_at',
    ];

    public function getCacheByAgentId($agentId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("agent:i:" . $agentId);
        return $data ? json_decode($data, true) : [];
    }
    public function getCacheByLoginName($loginName)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("agent:n:" . $loginName);
        return $data ? json_decode($data, true) : [];
    }

    public function setCacheLoginFailNum($agentId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $k = "agent:login:fail" . $agentId;
        $c = $redis->get($k) ? : 0;
        $redis->setex($k, 24*60*60, $c + 1);
        return $c + 1;
    }

    public function getCacheLoginFailNum($agentId)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $k = "agent:login:fail" . $agentId;
        return $redis->get($k) ? : 0;
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
            $redis->setex("agent:i:" . $v->id, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
            $redis->setex("agent:n:" . $v->loginName, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
        }
    }


}
