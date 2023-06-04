<?php
use App\Channels\ChannelProxy;
use App\Models\ChannelMerchant;
use App\Models\MerchantChannelSettlement;
$logger = $app->getContainer()->logger;
$redis = $app->getContainer()->redis;
$channels = $app->getContainer()->code['channel'];
$data = ChannelMerchant::where('status', 'Normal')
// ->where('openSettlement', 1)
// ->where('openQuery', 1)
    ->groupBy('channelMerchantId')
    ->get()
    ->toArray();
//print_r($data);exit;
foreach ($data ?? [] as $v) {
    try {
        $ch = isset($channels[$v->channel]) ? $channels[$v->channel] : [];
        if (empty($ch)) {
            continue;
        }

        if (!$ch['openSettlement'] || !$ch['openQuery']) {
            continue;
        }

        $balance = ((new ChannelProxy)->queryBalance($v->channelMerchantId));
        $balance = (float) $balance['balance'];
        $oldBalance = (float) $redis->get('cmq:' . $v->channelMerchantId);
        if ($oldBalance != $balance || true) {

            MerchantChannelSettlement::where('channelMerchantId', $v->channelMerchantId)->update([
                'accountBalance' => $balance,
            ]);
        }
        $redis->setex('cmq:' . $v->channelMerchantId, 86400, $balance);
    } catch (\Exception $e) {
        $logger->error("queryBalance error:" . $e->getMessage());
    }
}
