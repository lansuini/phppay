<?php
namespace App\Logics;
use App\Models\PlatformSettlementOrder;
use App\Models\PlatformNotify;
use App\Helpers\Tools;
use Illuminate\Support\Facades\DB;
use Slim\Container;

//风控预警模块
class WarningLogic extends Logic{

    public function merchant_notify($params){
        //同一商户5分钟内出款总额判断
        $merchant_return = $this->merchant_minutes($params);
        //十分钟内所有商户的出款总金额超过10万
        $this->system_minutes($params);
        //同一支付宝账号当天出款超过5a还需出款
        $account_return = $this->account_total($params);
        $code = 'SUCCESS';
        $msg = [];
        if($merchant_return['code'] != 'SUCCESS'){
            $code = 'error';
            $msg[] = $merchant_return['msg'];
        }
        if($account_return['code'] != 'SUCCESS'){
            $code = 'error';
            $msg[] = $account_return['msg'];
        }
        return ['code'=>$code, 'msg'=>implode("和", $msg)];
    }

    //同一商户5分钟内出款总额超过3万
    public function merchant_minutes($params){
        $return_msg = ['code'=>'SUCCESS', 'msg'=>'', 'total_amount'=> 0, 'type'=>$params['settlement_type']];
        $time = time();
        $start = date('Y-m-d H:i:s', $time - 5*60);
        $end = date('Y-m-d H:i:s', $time);
        $where = [
            ['merchantId', '=', $params['merchantId']],
            ['orderStatus', '<>', 'Fail'],
            ['created_at', '>=', $start],
            ['created_at', '<=', $end],
        ];
        $settlement_data = PlatformSettlementOrder::where($where)->selectRaw('sum(realOrderAmount) as amount')->first();
        $amount = $settlement_data->amount ? $settlement_data->amount : 0;
        $return_msg['total_amount'] = $amount;
        $sameUserSettleLimit = getenv('SAME_USER_SETTLEMENT_LIMIT',150000);
        if($amount > $sameUserSettleLimit){
            $return_msg['code'] = 'E1002';
            $return_msg['msg'] = '当前商户5分钟内出款总额超过'. $sameUserSettleLimit . '万';
            $title = "风险提示：{$params['settlement_type']} 同一个商户{$params['merchantNo']}在5分钟内出款总额超过{$sameUserSettleLimit}万";
            $content = "当前出款商户：{$params['merchantNo']}，出款姓名：{$params['accountName']}，出款账户：{$params['accountNo']}，出款金额：{$params['orderAmount']}元，5分钟内出款总额：{$amount}元，已超过{$sameUserSettleLimit}万，请注意商户行为并审查清楚后通知商户谨慎操作！";
//            $this->add_notify($title, $content);
        }
        $this->ci->logger->info("商户{$params['merchantNo']}在5分钟内出款总额校验", $return_msg);
        return $return_msg;
    }

    //10分钟内所有商户的出款总金额超过10万
    public function system_minutes($params){
        $return_msg = ['code'=>'SUCCESS', 'msg'=>'', 'total_amount'=> 0, 'type'=>$params['settlement_type']];
        $time = time();
        $start = date('Y-m-d H:i:s', $time - 10*60);
        $end = date('Y-m-d H:i:s', $time);
        $where = [
            ['orderStatus', '<>', 'Fail'],
            ['created_at', '>=', $start],
            ['created_at', '<=', $end],
        ];
        $settlement_data = PlatformSettlementOrder::where($where)->selectRaw('sum(realOrderAmount) as amount')->first();
        $amount = $settlement_data->amount ? $settlement_data->amount : 0;
        $return_msg['total_amount'] = $amount;
        if($amount > 10*10000){
            $return_msg['code'] = 'E1002';
            $return_msg['msg'] = '10分钟内所有商户的出款总金额超过10万';
            $title = "风险提示：{$params['settlement_type']} 10分钟内所有商户的出款总金额超过10a";
            $content = "当前出款商户：{$params['merchantNo']}，当前出款姓名：{$params['accountName']}，出款账户：{$params['accountNo']}，出款金额：{$params['orderAmount']}元，10分钟内所有商户出款总额：{$amount}元，已超过10万，请注意商户行为并审查清楚后通知商户谨慎操作！";
            $this->add_notify($title, $content);
        }
        $this->ci->logger->info("商户{$params['merchantNo']}触发10分钟内所有商户出款总额校验", $return_msg);
        return $return_msg;
    }

    //同一支付宝账号当天出款超过5万
    public function account_total($params){
        $return_msg = ['code'=>'SUCCESS', 'msg'=>'', 'total_amount'=> 0, 'type'=>$params['settlement_type']];
        $time = time();
        $date = date('Y-m-d', $time);
        $start = $date.' 00:00:00';
        $end = date('Y-m-d H:i:s', $time);
        $where = [
            ['bankAccountNo', '=', Tools::encrypt($params['accountNo'])],
            ['orderStatus', '<>', 'Fail'],
            ['created_at', '>=', $start],
            ['created_at', '<=', $end],
        ];
        $settlement_data = PlatformSettlementOrder::where($where)->selectRaw('sum(realOrderAmount) as amount')->first();
        $amount = $settlement_data->amount ? $settlement_data->amount : 0;
        $return_msg['total_amount'] = $amount;
        if($amount > 5*10000){
            $return_msg['code'] = 'E1002';
            $return_msg['msg'] = "当前支付宝账号{$params['accountNo']}当天出款总额超过5万";
            $title = "风险提示：{$params['settlement_type']} 商户{$params['merchantNo']}的支付宝账户{$params['accountNo']} 在{$date}当天的出款总额超过5a";
            $content = "当前出款商户：{$params['merchantNo']}，当前出款姓名：{$params['accountName']}，出款账户：{$params['accountNo']}，出款金额：{$params['orderAmount']}元，在{$date}当天的出款总额：{$amount}，已超过5万，请注意商户行为并审查清楚后通知商户谨慎操作！";
            $this->add_notify($title, $content);
        }
        $this->ci->logger->info("商户{$params['merchantNo']}的支付宝账户{$params['accountNo']}在{$date}当天的出款总额校验", $return_msg);
        return $return_msg;
    }

    //新增提示信息
    protected function add_notify($title, $content){
        PlatformNotify::insert([
            'accountId'=>0,
            'title'=>$title,
            'content'=>$content,
            'type'=>'risk',
        ]);
    }
}

