<?php
use App\Models\MerchantChannelSettlement;
// (new MerchantAmount)->moveTodayToYesterday();

$d = '[{
	"setId": 1,
	"merchantId": 1,
	"merchantNo": "88888888",
	"channel": "mockTest",
	"channelMerchantId": 1,
	"channelMerchantNo": "99999999",
	"settlementChannelStatus": "Normal",
	"settlementAccountType": "UsableAccount",
	"accountBalance": "1.00",
	"accountReservedBalance": "0.00",
	"openTimeLimit": 0,
	"beginTime": 20,
	"endTime": 2215,
	"openOneAmountLimit": 1,
	"oneMaxAmount": "50000.00",
	"oneMinAmount": "1.00",
	"openDayAmountLimit": 0,
	"dayAmountLimit": "0.00",
	"openDayNumLimit": 0,
	"dayNumLimit": 0,
	"status": "Normal",
	"created_at": "2019-04-19 14:42:02",
	"updated_at": "2019-04-19 14:48:10"
}]';
$d = json_decode($d, true);
dump($d);
$conf = (new MerchantChannelSettlement)->fetchConfig($merchantNo = '88888888', $merchantChannelData = $d, $settlementType = '', $payMoney = 100);

print_r($conf);
