<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantAccount extends Model
{
    protected $table = 'merchant_account';
    protected $primaryKey = "accountId";
    protected $fillable = [
        'loginName',
        'loginPwd',
        'securePwd',
        'userName',
        'loginFailNum',
        'loginPwdAlterTime',
        'merchantId',
        'merchantNo',
        'platformType',
        'status',
        'userLevel',
        'latestLoginTime',
        'googleAuthSecretKey',
    ];

    // public function create($request)
    // {
    //     $createdOffer = self::firstOrCreate([
    //         'name' => $request->getParam('name'),
    //         'account' => $request->getParam('account'),
    //         'pass' => $request->getParam('pass'),
    //     ]);

    //     return $createdOffer;
    // }

    public function getCacheByLoginName($loginName)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $data = $redis->get("merchantAccount:" . $loginName);
        return $data ? json_decode($data, true) : [];
    }

    public function delCacheByLoginName($loginName)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->del("merchantAccount:" . $loginName);
    }

    public function refreshCache($param = [])
    {
        global $app;
        $redis = $app->getContainer()->redis;
        if (empty($param)) {
            $data = self::get();
        } else {
            $data = self::where($param)->get();
        }

        foreach ($data as $v) {
            $redis->setex("merchantAccount:" . $v->loginName, 30 * 86400, json_encode($v, JSON_UNESCAPED_UNICODE));
        }
    }
}
