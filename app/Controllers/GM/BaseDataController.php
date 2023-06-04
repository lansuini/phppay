<?php

namespace App\Controllers\GM;

// use App\Controllers\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\PlatformNotify;

class BaseDataController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        $result = [];
        $items = $request->getParam('requireItems');
        $items = explode(',', $items);
        foreach ($items ?? [] as $item) {
            if (isset($this->c->code[$item])) {
                $result[$item] = [];
                if ($item == 'channel') {
                    foreach ($this->c->code[$item] as $k => $v) {
                        if (!$v['open']) {
                            continue;
                        }
                        $result[$item][] = ['key' => $k, 'value' => $v['name']];
                    }
                } else {
                    foreach ($this->c->code[$item] as $k => $v) {
                        if(!is_array($v)){
                            $result[$item][] = ['key' => $k, 'value' => $v];
                        }else{
                            $arr=[];
                            foreach ($v as $kvalue=>$itemvalue) {
                                $arr[]=['key' => $kvalue, 'value' =>$itemvalue];
                            }
                            $result[$item][] = ['key' => $k, 'value' => $arr];
                        }

                    }
                }

            } else {
                return $response->withJson([
                    'result' => $item,
                    'success' => 0,
                ]);
            }
        }
//        if (isset($result['bankCode'])) {
//            foreach ($result['bankCode'] as $k => $bank) {
//                if ($bank['key'] == 'ALIPAY') {
//                    unset($result['bankCode'][$k]);
//                }
//            }
//        }

        return $response->withJson([
            'result' => $result,
            'success' => 1,
        ]);
    }

    public function rechargeOrder(Request $request, Response $response, $args)
    {
        $result['rechargeOrderStatus'] = [
            ['key'=> "Transfered", 'value'=> "待支付"],
            ['key'=> "Success", 'value'=> "充值成功"],
            ['key'=> "Fail", 'value'=> "充值失败"],
            ['key'=> "Exception", 'value'=>"异常"]
        ];

        $result['rechargeOrderType'] = [
            ['key'=> "insideRecharge", 'value'=> "快捷充值"],
            ['key'=> "outsideRecharge", 'value'=> "网银充值"],

        ];

        $result['rechargeOrderPayType'] = [

            ['key'=> "EnterpriseEBank", 'value'=> "企业网银"],
            ['key'=> "PersonalEBank", 'value'=> "个人网银"],
            ['key'=> "PersonalEBankDNA", 'value'=> "个人网银DNA"],
            ['key'=> "EnterpriseAlipay", 'value'=> "企业支付宝"],
            ['key'=> "AlipayEBank", 'value'=> "支付宝网银"],

        ];


        return $response->withJson([
            'result' => $result,
            'success' => 1,
        ]);
    }

    //快捷自定义，用于较少字段定义，减少新建文件
    public function quickDefined(Request $request, Response $response, $args)
    {
        //代付黑名单用户类型
        $result['blackUserSettlementType'] = [
            ['key'=> "ALIPAY", 'value'=> "支付宝"],
            ['key'=> "EBANK", 'value'=> "银行卡"],
        ];

        $result['blackUserSettlementStatus'] = [
            ['key'=> "enable", 'value'=> "启用"],
            ['key'=> "disable", 'value'=> "禁用"],
        ];

        $result['merchantNoticeType'] = [
            ['key'=> "default", 'value'=> "全部"],
            ['key'=> "optional", 'value'=> "局部"],
        ];



        return $response->withJson([
            'result' => $result,
            'success' => 1,
        ]);
    }

    //弹窗未读消息列表
    public function notify(Request $request, Response $response, $args){
        $total = PlatformNotify::where('status','UNREAD')->where('platform','gm')->count();
        $notice = PlatformNotify::where('status','UNREAD')
            ->where('platform','gm')->orderBy('id', 'desc')
            ->first(['id','title','content']);
        if(!empty($notice)){
            $notice = $notice->toArray();
            $notice['title'] = mb_substr($notice['title'], 0, 80);
            $notice['content'] = mb_substr($notice['content'], 0, 120);
        }
        return $response->withStatus(200)->withJson([
            'data' => $notice,
            'attributes' => ['total'=>$total],
            'success' => 1,
        ]);
    }
}
