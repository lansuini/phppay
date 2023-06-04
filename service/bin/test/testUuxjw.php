<?php
use App\Channels\ChannelProxy;

$data = (new ChannelProxy)->setForceChannelMerchantId(5)->setForceOrderAmount(50)->getPayOrder('P20190423113648802761');

// $data = (new ChannelProxy)->setForceChannelMerchantId(4)->queryPayOrder('P20190408094532071825');
print_r($data);
