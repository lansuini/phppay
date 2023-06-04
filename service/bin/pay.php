<?php 
use App\Helpers\Tools;
use App\Channels\ChannelProxy;


// $data = (new ChannelProxy)->setForceChannelMerchantId(3)->setForcePayModel('NotDirect')->getPayOrder('P20190411141518621543');
// print_r($data);

$data = (new ChannelProxy)->setForceChannelMerchantId(3)->setForceOrderAmount(100)->getPayOrder('P20190408094412136249');
print_r($data);

// $data = (new ChannelProxy)->setForceChannelMerchantId(2)->queryPayOrder('P20190411141518621543');
// print_r($data);