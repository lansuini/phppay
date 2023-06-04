<?php
namespace App\Logics;

use App\Models\Agent;
use App\Models\AgentFinance;
use App\Models\AgentIncomeLog;
use App\Models\AgentReport;
use App\Models\AgentWithdrawOrder;
use App\Models\ChannelBalanceIssue;
use App\Models\MerchantAccount;

/**
 *
 */
class AgentLogic extends Logic {

    public function settleFees($agent,$day){
        global $app;
        $logger = $app->getContainer()->logger;
        $db = $app->getContainer()->database;
        if(!$agent) {
            return false;
        }
        $data['agentId'] =  $agent['id'];
        $data['agentName'] =  $agent['loginName'];
        $data['commWays'] =  $agent['settleAccWay'];
        $data['accountDate'] =  $day;
        $data['addMerchant'] = MerchantAccount::leftJoin('agent_merchant_relation','agent_merchant_relation.merchantId','=','merchant_account.merchantId')
                        ->where('merchant_account.created_at','>=',$day)
                        ->where('merchant_account.created_at','<=',$day . ' 23:59:59')
                        ->where('agent_merchant_relation.agentId','=',$agent['id'])
                        ->count() ?? 0;

        //金额下发金额
        $income = AgentIncomeLog::where('agentId',$agent['id'])
                    ->where('isSettle',0);

        switch($agent['settleAccWay']){
            case 'D7' : $d = date('Y-m-d',strtotime("-6 day",strtotime($day))); break;
            case 'D30' : $d = date('Y-m-d',strtotime("-29 day",strtotime($day))); break;
            default : $d = $day; break;//结算前一天的 代理佣金
        }

        $income->where('created_at','>=',$d)
            ->where('created_at','<=',$d.' 23:59:59');
        $update = clone $income;
        $fees = $income->sum('fee') ?? 0;
        if($fees){//结算
            $ids = $update->pluck('id')->toArray();
            try {
                $db->getConnection()->beginTransaction();
                (new AgentIncomeLog())->settleFee($agent['id'], $fees);
                AgentIncomeLog::whereIn('id',$ids)->update(['isSettle'=>1]);
                $db->getConnection()->commit();
            }catch (\Exception $e){
                $db->getConnection()->rollback();
                $logger->error('Exception:AgentSettle' . $d . ':' .$e->getMessage());
            }
        }
        //佣金数据
        $tmp = AgentIncomeLog::where('agentId',$agent['id'])
                    ->where('created_at','>=',$day)
                    ->where('created_at','<=',$day.' 23:59:59')
                    ->groupBy('isSettle')
                    ->get([
                        'isSettle',
                        $db::raw("count(1) as count"),
                        $db::raw("sum(fee) as money"),
                    ]);
        $data['commCount'] = 0;
        $data['commMoney'] = 0;
        $data['settCommCount'] = 0;
        $data['settCommMoney'] = 0;
        foreach ($tmp as $key=>$val) {
            if($val['isSettle'] == 1) {
                $data['settCommCount'] = $val['count'];
                $data['settCommMoney'] = $val['money'];
            }
            if(in_array($val['isSettle'],[0,1])) {
                $data['commCount'] += $val['count'];
                $data['commMoney'] += $val['money'];
            }
        }
        //提款信息
        $withdraw = AgentWithdrawOrder::where('agentId',$agent['id'])
            ->where('status','=','Complete')
            ->where('created_at','>=',$day)
            ->where('created_at','<=',$day.' 23:59:59')
            ->first([
                $db::raw("count(1) as count"),
                $db::raw("sum(dealMoney) as dealMoney"),
                $db::raw("sum(fee) as fee"),
            ]);
        $data['withdrewMoney'] = $withdraw['dealMoney'] ?? 0;
        $data['withdrewFee'] = $withdraw['fee'] ?? 0;
        $data['withdrewCount'] = $withdraw['count'] ?? 0;
        $t = [
            'agentId' => $data['agentId'],
            'accountDate' => $day,
        ];
        AgentReport::updateOrInsert($t,$data);
    }

    public function withdrawCallback($order , $result){
        print_r($result);
        $o = AgentWithdrawOrder::find($order['foreign_id']);
        if($o->status != 'Adopt') return;
        if($result['status'] == 'Fail'){  //代付失败  只需要更新状态
            $o->status = 'Apply';
            $o->optDesc .= '|'.$order['channelNo'].':'.$result['failReason'];
            $o->save();
            ChannelBalanceIssue::where('issueId',$order['issueId'])->update(['orderStatus'=>'Fail']);
        }elseif($result['status'] == 'Success'){  //代付成功，也只需要更新状态 Success
            $o->status = 'Complete';
            $o->optDesc .= '|'.$order['channelNo'].':'.$result['failReason'];
            $o->save();
            //写入流水信息
            $agent = Agent::find($o->agentId);
            AgentFinance::insert(['agentId' => $o->agentId,
                'agentName' => $o->agentName,
                'platformOrderNo' => $o->platformOrderNo,
                'dealMoney' => $o->dealMoney,
                'balance' => $agent->balance,
                'freezeBalance' => $agent->freezeBalance,
                'bailBalance' => $agent->bailBalance,
                'dealType' => 'extractSuc',
                'optId' => 0,
                'optAdmin' => '',
                'optIP' => '',
                'optDesc' => "代付打款成功",]);
            ChannelBalanceIssue::where('issueId',$order['issueId'])->update(['orderStatus'=>'Success']);
        }
    }
}

