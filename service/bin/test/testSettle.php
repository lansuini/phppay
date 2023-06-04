<?php
use App\Models\MerchantChannelSettlement;

$d = '[{
	"setId": 14,
	"merchantId": 2,
	"merchantNo": "88888889",
	"channel": "mockTest",
	"channelMerchantId": 1,
	"channelMerchantNo": "99999999",
	"settlementChannelStatus": "Normal",
	"settlementAccountType": "UsableAccount",
	"accountBalance": "100.00",
	"accountReservedBalance": "0.00",
	"openTimeLimit": 0,
	"beginTime": 0,
	"endTime": 0,
	"openOneAmountLimit": 0,
	"oneMaxAmount": "5000.99",
	"oneMinAmount": "200.01",
	"openDayAmountLimit": 0,
	"dayAmountLimit": "1500.01",
	"openDayNumLimit": 0,
	"dayNumLimit": 25,
	"status": "Normal",
	"created_at": "2019-04-24 10:24:21",
	"updated_at": "2019-04-24 10:24:21"
}]';

$d = json_decode($d, true);

$conf = (new MerchantChannelSettlement)->fetchConfig($merchantNo = '88888889', $merchantChannelData = $d, $settlementType = '', $payMoney = 300);
// dump($conf);

function a($res)
{
    $status = ['Success', 'Fail', 'Exception', 'Execute'];
    $check = [
        'status',
        'orderNo',
        'orderAmount',
        'failReason',
    ];

    if (!is_array($res)) {
        return false;
    }

    foreach ($check as $k) {
        if (!isset($res[$k])) {
            return false;
        }
    }
    if (!in_array($res['status'], $status)) {
        return false;
    }
    return true;
}

$a = [
    'status' => 'Success',
    'orderAmount' => 60,
    'orderNo' => '',
    'failReason' => '',
];

dump(!a($a) ? 'Y' : 'N');
