<?php

namespace App\Models;

class Channel
{

    public function setBalance($channelMerchantNo, $balance)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $redis->setex("channel:balance:" . $channelMerchantNo, 86400, $balance);
    }

    public function getBalance($channelMerchantNo)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        return (float) $redis->get("channel:balance:" . $channelMerchantNo);
    }
}
