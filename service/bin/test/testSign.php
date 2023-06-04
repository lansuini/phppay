<?php

$d = '{
	"order_no": "112233445566",
	"user_id": "1000010",
	"shop_no": "P20190425134122248516",
	"money": "35.31",
	"type": "mwpay",
	"date": "2019-04-25 15:03:22",
	"trade_no": "112233445566",
	"status": 0,
	"shopAccountId": 40249,
	"sign": "287a10e62da02d53e20a3dfb3d0210ac"
}
';

$params = json_decode($d, true);
$apiKey = 'wLCjLq78D0cJwn2v0Zanyhlph66IJ476';
$sign = md5($params['shopAccountId'] . $params['user_id'] . $params['trade_no'] . $apiKey . $params['money'] . $params['type']);
dump($sign);
dump($sign == $params['sign']);
