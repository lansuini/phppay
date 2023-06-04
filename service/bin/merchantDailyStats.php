<?php
use App\Logics\MerchantLogic;

$logic = new MerchantLogic($container);
$date = isset($argv[2]) ? $argv[2] : date('Y-m-d',strtotime("-1 day")) ;
$logic->dayStats($date);


