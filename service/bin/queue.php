<?php
use App\Queues\SettlementFetchExecutor;
use App\Queues\SettlementPushExecutor;
use App\Queues\SettlementNotifyExecutor;
use App\Channels\ChannelProxy;
// (new SettlementFetchExecutor)->push(251, 'S20190408103536149750');
// (new SettlementFetchExecutor)->push(252, 'S20190408103551189052');
// (new SettlementFetchExecutor)->pop();

// (new SettlementPushExecutor)->push(2, 'S20190408095212578206', '[]', '{"status":"Success","orderAmount":100,"failReason":""}');
// (new SettlementPushExecutor)->pop();
//$balance = ((new ChannelProxy)->queryBalance(33));
//print_r($balance);
$balance = ((new ChannelProxy)->queryBalance(38));
print_r($balance);
$balance = ((new ChannelProxy)->queryBalance(43));
print_r($balance);
exit;
(new SettlementNotifyExecutor)->push(1, 'S20190408095212578206');
(new SettlementNotifyExecutor)->pop();