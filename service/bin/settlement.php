<?php
use App\Helpers\Tools;
use App\Channels\ChannelProxy;

require '../sdk/gate.php';

# 网关地址
$gateway = 'http://gate.wldev01.com';

# 商户号
$merchantNo = "10000010";

# 密钥
$signKey = "83f3739f72bc936c100989f591949045";

$ddp = new DaDongPay($gateway, $merchantNo, $signKey);

$res = $ddp->getSettlementOrder([
    'merchantNo' => $merchantNo,
    'merchantOrderNo' => date('YmdHis') . rand(100000, 999999),
    'merchantReqTime' => date("YmdHis"),
    'orderAmount' => 200.00,
    'tradeSummary' => '我是代付摘要',
    'bankCode' => 'Robinsons Bank',
    'bankName' => '某某分行',
    'bankAccountNo' => '123123123',
    'bankAccountName' => 'radUjdd',
    'province' => '广东省',
    'city' => 'Pasig',
    'orderReason' => '测试代付',
    'requestIp' => '127.0.0.1',
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://mockmerchant.wldev01.com/merchant/success',
    'merchantParam' => 'abc=1',
]);
print_r($res);
echo 'finish', PHP_EOL;

exit;
/*
$channels = $app->getContainer()->code['channel'];
$ch = $channels['wuxingzhifu'];

$ch['dbParam'] = [];
$cho = new $ch['pathSettlement']($ch);
$orderData = [
    'platformOrderNo' => Tools::getPlatformOrderNo('local'),
    'orderAmount' => 1,
    'payType' => 'OnlineWechatH5',
    'bankCode' => '',
];
print_r($cho->getMerchantCharge($orderData));

*/
//$reqData=['test'=>1];
//$req = Requests::post('https://diaohui017.xyz/response/resXunBaoPayout', ['Content-Type' => 'application/json'], json_encode($reqData), ['timeout' => 15,'verify' => false]);
//print_r($req->body);
////$this->logger->debug("代付补发通知", ['backNoticeUrl' => 'https://diaohui017.xyz/response/resXunBaoPayout', 'reqData' => json_encode($reqData), 'rspCode' => $req->status_code, 'rspBody' => trim($req->body)]);
//$host = 'diaohui017.xyz';
//echo gethostbyname($host);exit;
$orderData=[
    'channelMerchantId'=>777,
    'platformOrderNo'=> Tools::getPlatformOrderNo('S'),
    'orderAmount'=>1.00,
    'tradeSummary' => '我是代付摘要',
    'bankCode' => 'ABC',
    'bankName' => '某某分行',
    'bankAccountNo' => '1212121212121212',
    'bankAccountName' => '陈小春1',
    'province' => '广东省',
    'city' => '阳春市',
    'orderReason' => '测试代付',
    'requestIp' => '127.0.0.1',
    // 'backNoticeUrl' => 'http://mock.dodang.com/merchant/succss',
    'backNoticeUrl' => 'http://mockmerchant.wldev01.com/merchant/success',
    'merchantParam' => 'abc=1',
];
//$data = (new ChannelProxy)->setForceChannelMerchantId(777)->getSettlementOrder($orderData);
//$data = (new ChannelProxy)->queryBalance(62);
//print_r($data);
// echo PHP_EOL;
//$num=Tools::getPlatformOrderNo('S');
 $data = (new ChannelProxy)->setForceChannelMerchantId(62)->querySettlementOrder(['channelMerchantId'=>62,'platformOrderNo'=>'S20191203121758120895']);
 print_r($data);
// $data = (new ChannelProxy)->setForceChannelMerchantId(2)->queryPayOrder('S20190410174824421308');
// print_r($data);