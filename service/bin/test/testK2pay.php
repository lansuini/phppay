<?php
use App\Channels\ChannelProxy;


/* $data = (new ChannelProxy)->setForceChannelMerchantId(29)->setForceOrderAmount(100)->getPayOrder('P20190605174012057981');

$data = (new ChannelProxy)->setForceChannelMerchantId(4)->queryPayOrder('P20190408094532071825'); */

//$data = (new ChannelProxy)->setForceChannelMerchantId(785)->getSettlementOrder('S20220726120816827690');
$data = (new ChannelProxy)->setForceChannelMerchantId(786)->querySettlementOrder('S20221031203914728941');
print_r($data);
