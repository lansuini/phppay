<?php

namespace App\Controllers\Mock;

use App\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\AmountPay;
use App\Helpers\Tools;
class TestController extends Controller
{
    public function index(Request $request, Response $response, $args)
    {
        // $data = AmountPay::updateOrCreate(['merchantId' => 1,
        //     'merchantNo' => '888888',
        //     'accountDate' => date('Ymd')]);
         echo round(900.126,2);
         exit;
        $json = '{"orderId":477366,"platformOrderNo":"S20221112212913108275","merchantId":2,"merchantNo":"10000011","merchantOrderNo":"1112092913963132455","merchantParam":"","merchantReqTime":"2022-11-12 21:29:13","orderAmount":"900.77","realOrderAmount":"900.77","serviceCharge":"13.51","channelServiceCharge":11.710000000000001,"channelSetId":0,"failReason":"自动处理-第三方请求失败：[http_code]:202, [resp_body]:{\"status\":\"403\",\"message\":\"sign error\",\"data\":null}","channel":"loropay","channelMerchantId":1,"channelMerchantNo":"100010","channelOrderNo":"","channelNoticeTime":"20221112212914","orderReason":"","orderStatus":"Fail","orderType":"SettlementOrder","pushChannelTime":null,"backNoticeUrl":"https:\/\/api-admin.lodibetadmin.com\/thirdAdvance\/callback\/luckypay","bankLineNo":"","bankCode":"Globe Gcash","bankName":"Manila","bankAccountName":"Arra Joy M.chavez","bankAccountNo":"pgcMMxT33TcNp0EjyxxJbA==","city":"Manila","province":"Metro Manila","userIp":"103.49.246.109","applyPerson":"API接口发起","applyIp":"18.138.128.232","accountDate":"20221112","auditPerson":"","auditIp":"","auditTime":null,"tradeSummary":"trans","processType":"Success","callbackLimit":0,"callbackSuccess":0,"created_at":"2022-11-12 21:29:13","updated_at":"2022-11-12 21:29:14","agentFee":"0.00","agentName":null,"isLock":0,"lockUser":null}
';
        global $app;
        $redis = $app->getContainer()->redis;

        $data = json_decode($json,true);
        $redis->setex("settlementorder:" . 'S20221112212913108275', 7 * 86400, json_encode($data, JSON_UNESCAPED_UNICODE));


        print_r(json_decode($redis->get("settlementorder:" . 'S20221112212913108275'),true));
        echo '<pre>';

        // echo Tools::encrypt('02ae4d138ebff07cb6bb0efcca8c4546a105f520');
        echo Tools::getHashPassword('caiwu-666');
        // return $response->withStatus(200)->wirte('SUCCESS');
    }

}
