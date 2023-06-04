<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentIncomeLog extends Model
{
    protected $table = 'agent_income_log';

    protected $primaryKey = 'id';

    protected $fillable = [
        'agentId',
        'platformOrderNo',
        'orderMoney',
        'fee',
        'ways',
        'type',
        'typeSub',
        'bankCode',
        'isSettle',
        'updated_at',
        'created_at',
    ];

    /*
     * 计算手续费值
     * $agent_id  代理ID
     * $order_money  订单金额
     * $product_type  产品类型
     * $product_type  支付方式
     * $agent_id  代理ID
     */
    public function getFee($agent_id ,$merchant_id,$platformOrderNo, $order_money , $product_type , $pay_type,$bank_code = null ,$id = 0){
        $rule = AgentRate::where('agentId',$agent_id)
                    ->where('productType',$product_type)
                    ->where('payType',$pay_type)
                    ->where('status','Normal');

        $merRate = MerchantRate::where('merchantId',$merchant_id)
            ->where('productType',$product_type)
            ->where('payType',$pay_type)
            ->where('status','Normal');
        if($bank_code) {
            $rule = $rule->where('bankCode',$bank_code);
            $merRate = $merRate->where('bankCode',$bank_code);
        }
        $rule = $rule->first();
        $merRate = $merRate->first();
        $fee = 0;
        if(!$rule || !$merRate) return $fee;
        switch ($rule['rateType']){
            case 'Rate' : $fee = ($merRate['rate'] - $rule['rate']) * $order_money ; break;
            case 'FixedValue' : $fee = $merRate['fixed'] - $rule['fixed'] ; break;
            default : $fee = ($merRate['rate'] - $rule['rate']) * $order_money + $merRate['fixed'] - $rule['fixed'];
        }
        if ($rule['minServiceCharge'] > 0 && $fee < $rule['minServiceCharge']) {
            $fee = $rule['minServiceCharge'];
        }

        if ($rule['maxServiceCharge'] > 0 && $fee > $rule['maxServiceCharge']) {
            $fee = $rule['maxServiceCharge'];
        }
        $tmp = [
            'rateType' =>$rule['rateType'],
            'rate' =>strval($rule['rate']),
            'fixed' =>strval($rule['fixed']),
            'mer_rateType' =>$merRate['rateType'],
            'mer_rate' =>strval($merRate['rate']),
            'mer_fixed' =>strval($merRate['fixed']),
        ];
        $fee = $fee > 0 ? $fee : 0;
        $fee = round($fee, 2);
        try {
            $data['agentId'] = $agent_id;
            $data['platformOrderNo'] = $platformOrderNo;
            $data['orderMoney'] = $order_money;
            $data['fee'] = $fee;
            $data['ways'] = json_encode($tmp);
            $data['type'] = $product_type;
            $data['typeSub'] = $pay_type;
            $data['bankCode'] = $bank_code ?? '';
            $data['isSettle'] = -1;  //  默认订单未完成
            if($id){
                AgentIncomeLog::where('id',$id)->update($data);
            }else {
                AgentIncomeLog::create($data);
            }
        }catch (\Exception $e){
            print_r($e->getMessage());die();
        }
        return $fee;
    }
    /*
     * 结算手续费值  必须加入事物
     * $agent_id  代理ID
     * $money  订单金额
     */
    public function settleFee($agent_id,$money,$platformOrderNo = null){
        $agent = new Agent();
        $data = $agent->where('id', $agent_id)->lockForUpdate()->first();
        if(empty($data)) return false;
        $accRatio = $data->settleAccRatio > 1 ? 1 : $data->settleAccRatio;
        $accRatio = $accRatio < 0 ? 0 : $accRatio;
        $balance = $accRatio * $money;  //  可提余额
        $freezeBalance = $money - $balance;  //冻结余额
        $data->balance = $balance + $data->balance;
        $data->freezeBalance = $freezeBalance + $data->freezeBalance;
        $data->save();
        //写入代理流水
        $finance = new AgentFinance();
        $finance->agentId = $agent_id;
        $finance->agentName = $data['loginName'];
        $finance->platformOrderNo = $platformOrderNo ?? 'NO';
        $finance->dealMoney = $money;
        $finance->balance = $data->balance;
        $finance->freezeBalance = $data->freezeBalance;
        $finance->bailBalance = $data->bailBalance;
        $finance->dealType = 'commission';
        $finance->status = 'Normal';
        $finance->desc = '结算百分比：' . $data->settleAccRatio;
        $finance->save();
    }
    /*
     * 更新手续费值
     * $platformOrderNo  订单金额
     * $product_type  产品类型
     */
    public function updateIncomeLog($merchant_id,$platformOrderNo,$realMoney ,$product_type){
        $income = self::where('platformOrderNo',$platformOrderNo)->where('isSettle',-1)
            ->where('type',$product_type)->first();
        if(empty($income)) return 0;
        if($realMoney > 0 && abs($realMoney - $income['orderMoney']) > 10) {  //实际支付金额 10块左右不重新计算手续费了 容错10块
            $income['fee'] = $this->getFee($income['agentId'],$merchant_id,$platformOrderNo,$realMoney,$product_type,$income['typeSub'],$income['bankCode'],$income['id']);
        }

        $agent = new Agent();
        $agent->refreshCache();
        $config = $agent->getCacheByAgentId($income['agentId']);
        if($config && $config['settleAccWay'] == 'D0') {  //代理D0结算  及时结算
            $re = $this->settleFee($income['agentId'],$income['fee'],$platformOrderNo);
            $income->isSettle = $re === false ? -2 : 1; //其它原因结算失败，异常
        }else{
            $income->isSettle = 0;
        }
        $income->save();
        return $income['fee'];
    }
}
