<?php
use App\Logics\ChannelLogic;

$logic = new ChannelLogic($container);
$date = isset($argv[2]) ? $argv[2] : date('Y-m-d',strtotime("-1 day")) ;
$logic->dayStats($date);//只跑某天
//$logic->getStats();//统计所有天


