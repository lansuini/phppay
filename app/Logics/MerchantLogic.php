<?php
namespace App\Logics;
use App\Models\AmountPay;
use App\Models\AmountSettlement;
use App\Models\PlatformRechargeOrder;
use App\Models\PlatformPayOrder;
use App\Models\PlatformSettlementOrder;
use App\Models\Merchant;
use App\Models\MerchantDailyStats;
use App\Models\ChannelMerchant;
use App\Models\ChannelBalanceQuery;
use App\Models\MerchantChannelSettlement;
use App\Models\SystemAccountActionLog;
use App\Channels\ChannelProxy;
use App\Helpers\Tools;
use Illuminate\Support\Facades\DB;

/**
 * 下游商户模块
 */
class MerchantLogic extends Logic {

    public function getStats(){
        $today = date('Y-m-d');
        $yday = date('Y-m-d', strtotime("-1 day"));//昨天
        //支付最早日期
        $payData = AmountPay::orderBy('id', 'asc')->first();
        if(empty($payData)){
            $payDay = $today;
        }else{
            $payData = $payData->toArray();
            $payDay = $payData['accountDate'];
        }
        //代付最早日期
        $setlementData = AmountSettlement::orderBy('id', 'asc')->first();
        if(empty($setlementData)){
            $setlementDay = $today;
        }else{
            $setlementData = $setlementData->toArray();
            $setlementDay = $setlementData['accountDate'];
        }
        //充值最早日期
        $chargeData = PlatformRechargeOrder::orderBy('updated_at', 'asc')->first();
        if(empty($chargeData)){
            $chargeDay = $today;
        }else{
            $chargeData = $chargeData->toArray();
            $chargeDay = date('Y-m-d', strtotime($chargeData['updated_at']));
        }
        $tmp = $payDay < $setlementDay ? $payDay : $setlementDay;
        $startDay = $tmp < $chargeDay ? $tmp : $chargeDay;
        if($startDay == $today){
            return false;
        }
        while($startDay){
            $this->dayStats($startDay);
            $eDay = date('Y-m-d', strtotime("+1 day", strtotime($startDay)));//明天
            if($eDay >= $yday){
                break;
            }else{
                $startDay = $eDay;
            }
        }
    }

    //根据日期获取商户的支付，代付，充值
    public function dayStats($startDay){
        $merchant = Merchant::get(['merchantId', 'merchantNo'])->toArray();

        foreach ($merchant as $k=>$mer){
            $where = [
                ['merchantNo', $mer['merchantNo']],
                ['orderStatus', 'Success'],
                ['updated_at', '>=', $startDay.' 00:00:00'],
                ['updated_at', '<=', $startDay.' 23:59:59'],
            ];
            //支付
            $payData = PlatformPayOrder::where($where)->selectRaw('count(orderId) as pcount, sum(realOrderAmount) as pamount, sum(serviceCharge) as pcharge, sum(channelServiceCharge) as pservice, sum(agentFee) as agentPayFees')->first();
            if(empty($payData)){
                $payData = ['pcount'=>0, 'pamount'=>'0.00', 'pcharge'=>'0.00', 'pservice'=>'0.00', 'agentPayFees'=>'0.00'];
            }else{
                $payData = $payData->toArray();
            }
            //代付
            $setlementData = PlatformSettlementOrder::where($where)->selectRaw('count(orderId) as scount, sum(realOrderAmount) as samount, sum(serviceCharge) as scharge, sum(channelServiceCharge) as sservice, sum(agentFee) as agentsettlementFees')->first();
            if(empty($setlementData)){
                $setlementData = ['scount'=>0, 'samount'=>'0.00', 'scharge'=>'0.00', 'sservice'=>'0.00', 'agentsettlementFees'=>'0.00'];
            }else{
                $setlementData = $setlementData->toArray();
            }
            //充值
            $chargeData = PlatformRechargeOrder::where($where)->selectRaw('count(id) as ccount, sum(realOrderAmount) as camount, sum(serviceCharge) as ccharge, sum(channelServiceCharge) as cservice, sum(agentFee) as agentchargeFees')->first();
            if(empty($chargeData)){
                $chargeData = ['ccount'=>0, 'camount'=>'0.00', 'ccharge'=>'0.00', 'cservice'=>'0.00', 'agentchargeFees'=>'0.00'];
            }else{
                $chargeData = $chargeData->toArray();
            }
            if((empty($payData['pamount']) || $payData['pamount']=='0.00') && (empty($setlementData['samount']) || $setlementData['samount']=='0.00') && (empty($chargeData['camount']) || $chargeData['camount']=='0.00')){
                continue;
            }else{
                MerchantDailyStats::insert([
                    'merchantId'=>$mer['merchantId'],
                    'merchantNo'=>$mer['merchantNo'],
                    'payCount'=>$payData['pcount'] ? $payData['pcount'] : 0,
                    'payAmount'=>$payData['pamount'] ? $payData['pamount'] : 0,
                    'payServiceFees'=>$payData['pcharge'] ? $payData['pcharge'] : 0,
                    'payChannelServiceFees'=>$payData['pservice'] ? $payData['pservice'] : 0,
                    'agentPayFees'=>$payData['agentPayFees'] ? $payData['agentPayFees'] : 0,

                    'settlementCount'=>$setlementData['scount'] ? $setlementData['scount'] : 0,
                    'settlementAmount'=>$setlementData['samount'] ? $setlementData['samount'] : 0,
                    'settlementServiceFees'=>$setlementData['scharge'] ? $setlementData['scharge'] : 0,
                    'settlementChannelServiceFees'=>$setlementData['sservice'] ? $setlementData['sservice'] : 0,
                    'agentsettlementFees'=>$setlementData['agentsettlementFees'] ? $setlementData['agentsettlementFees'] : 0,

                    'chargeCount'=>$chargeData['ccount'] ? $chargeData['ccount'] : 0,
                    'chargeAmount'=>$chargeData['camount'] ? $chargeData['camount'] : 0,
                    'chargeServiceFees'=>$chargeData['ccharge'] ? $chargeData['ccharge'] : 0,
                    'chargeChannelServiceFees'=>$chargeData['cservice'] ? $chargeData['cservice'] : 0,
                    'agentchargeFees'=>$chargeData['agentchargeFees'] ? $chargeData['agentchargeFees'] : 0,

                    'accountDate'=>$startDay
                ]);
            }
        }
    }

    //获取上游渠道
    public function getChannels(){
        $channels = $this->code['channel'];
        $data = ChannelMerchant::where([['status', 'Normal'],['channel', '<>', 'alipay']])->orderby('channelMerchantId', 'asc')->get();
//        $data = ChannelMerchant::where('status', 'Normal')->orderby('channelMerchantId', 'desc')->get();
        if(empty($data)){
            $this->logger->info("channel merchant is empty");
            return;
        }
        $data = $data->toArray();
        foreach ($data ?? [] as $v) {
            try {
                $ch = isset($channels[$v['channel']]) ? $channels[$v['channel']] : [];
                if (empty($ch)) {
                    continue;
                }

                if (!$ch['openSettlement'] || !$ch['openQuery']) {
                    continue;
                }
                $this->channelBalance($v);
            } catch (\Exception $e) {
                $this->logger->error("queryBalance error:" . $e->getMessage());
            }
        }
        $this->logger->info("channel merchant query balance finish!");
    }

    //上游渠道余额查询
    public function channelBalance($channelData){
        $query['channelId'] = $channelData['channelMerchantId'];
        $query['channelNo'] = $channelData['channelMerchantNo'];
        $query['channel'] = $channelData['channel'];
        //查询上游对应的下游商户数
        $merCount = MerchantChannelSettlement::where(['channelMerchantId'=>$channelData['channelMerchantId']])->count();
        if(empty($merCount)){
            $query['merchantCount'] = 0;
            $query['merchantBalance'] = 0;
        }else{
            $query['merchantCount'] = $merCount;
            //查询上游对应的下游商户的余额
            $merBalance = \Illuminate\Database\Capsule\Manager::select("SELECT SUM(settlementAmount) as balance FROM merchant_amount where merchantNo in (SELECT merchantNo FROM merchant_channel_settlement where channelMerchantId = {$channelData['channelMerchantId']})");
            if(empty($merBalance)){
                $query['merchantBalance'] = 0;
            }else{
                $merBalance = current($merBalance);
                $query['merchantBalance'] = $merBalance->balance;
            }
        }

        $proxy = new ChannelProxy();
        $balance = $proxy->queryBalance($channelData['channelMerchantId']);
        $query['channelBalance'] = (float)$balance['balance'];
        $query['diffValue'] = bcsub($query['channelBalance'], $query['merchantBalance'], 2);
        ChannelBalanceQuery::insert($query);
    }

    //上游渠道转账请求
    public function channelSettlement($params){
        if(empty($params)){
            return false;
        }
        $proxy = new ChannelProxy();
        $orderData = [
            'channelMerchantId'=>$params['channelId'],
            'platformOrderNo'=>$params['issueOrderNo'],
            'merchantOrderNo'=>rand(1000, 9999).date('YmdHis'),
            'bankCode'=>$params['bankCode'],
            'bankAccountNo'=>Tools::encrypt($params['cardNo']),
            'bankName'=>$this->code['bankCode'][$params['bankCode']],
            'bankAccountName'=>$params['userName'],
            'province'=>'未知',
            'orderAmount'=>$params['issueAmount'],
        ];
        $this->logger->debug('向上游发起余额下发请求：', ['params'=>$orderData]);
        $channelOrder = $proxy->getSettlementOrder($orderData);
//        $channelOrder = ["status"=>"Fail", "orderNo"=>$params['issueOrderNo'], "failReason"=>"订单提交失败：TRANS_STATE:0094PAY_STATE:", "orderAmount"=>0];
        //{"result":{"status":"Fail","orderNo":"","failReason":"订单提交失败：TRANS_STATE:0094PAY_STATE:","orderAmount":0}}
        $this->logger->debug('向上游发起余额下发返回：', ['result'=>$channelOrder]);
        return $channelOrder;
    }

    //上游渠道转账状态查询请求
    public function channelSettlementQuery($params){
        if(empty($params)){
            return false;
        }

        $orderData = [
            'channelMerchantId'=>$params['channelId'],
            'platformOrderNo'=>$params['issueOrderNo'],
        ];
        $this->logger->debug('向上游发起订单状态查询请求：', ['params'=>$orderData]);
        $proxy = new ChannelProxy();
        $channelOrder = $proxy->querySettlementOrder($orderData);
        //{"result":{"status":"Success","orderNo":"I20190822104632871240","failReason":"","orderAmount":0}}
        $this->logger->debug('向上游发起订单状态查询请求返回：', ['result'=>$channelOrder]);
        //{"class":"\\App\\Logics\\AgentLogic","func":"withdrawCallback"}
        if(isset($params['classOpt'])){  //需要进行后继作业
            $t = json_decode($params['classOpt'],true);
            $class = $t['class'] ?? '';
            $func = $t['func'] ?? '';
            if($class && $func && class_exists($class) ) {
                $opt = new $class($this->ci);
                $opt->$func($params,$channelOrder);
            }
        }
        return $channelOrder;
    }

    public static function getBankCodeByCardNo($cardNo){
        $a = [
            'PSBC' => '邮储银行',
            'ICBC' => '工商银行',
            'ABC' => '农业银行',
            'BOC' => '中国银行',
            'CCB' => '建设银行',
            'CCB2' => '建行厦门分行',
            'BCOM' => '交通银行',
            'CITIC' => '中信银行',
            'CEB' => '光大银行',
            'HXB' => '华夏银行',
            'CMBC' => '民生银行',
            'GDB' => '广发银行股份有限公司',
            'PAB' => '平安银行',
            'CMB' => '招商银行',
            'CIB' => '兴业银行',
            'SPDB' => '浦东发展银行',
            'CZB' => '浙商银行',
            'CBHB' => '渤海银行',
            'BEA' => '东亚银行',
            'SHB' => '上海银行',
            'BOB' => '北京银行',
            'NBCB' => '宁波银行',
            'DLB' => '大连银行',
            'NJCB' => '南京银行',
            'HSB' => '徽商银行',
            'JSB' => '江苏银行',
            'SRCB' => '上海农商银行',
            'BJRCB' => '北京农村商业银行',
            'GZCB' => '广州银行',
        ];
        $name =  BankList::bandCardInfo($cardNo);
        foreach ($a as $key => $val){
            if(mb_strpos($name,$val) !== false){
                return $key;
            }
        }
        return 'no';
    }
}

