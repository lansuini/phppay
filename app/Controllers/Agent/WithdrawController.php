<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Helpers\Tools;
use App\Models\Agent;
use App\Models\AgentBankCard;
use App\Models\AgentFinance;
use App\Models\AgentWithdrawOrder;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WithdrawController extends AgentController
{
    public function Apply(Request $request, Response $response, $args){
        $redis = $this->c->redis;
        $bankId = Tools::getIdByHash($request->getParam("withdrawBankId"));
        $withdrawMoney = (int)$request->getParam("withdrawMoney");
        $withdrawDesc = (int)$request->getParam("withdrawDesc");

        if(!$bankId || $withdrawMoney <= 0) {
            return $response->withJson([
                'success' => 0,
                'result' => '请输入正确的金额',
            ]);
        }

        $card = new AgentBankCard();
        $bankData = $card->find($bankId);
        if(!$bankData || $bankData && $bankData['agentId'] != $_SESSION['userId'] || $bankData && $bankData['status'] != 'Normal') {
            return $response->withJson([
                'success' => 0,
                'result' => '银行卡信息不存在',
            ]);
        }

        $newPwd = Tools::getHashPassword($request->getParam('withdrawPwd'));
        $model = new Agent();
        $pwd = $model->where('id',$_SESSION['userId'])->value('securePwd');
        $i = $redis->get("agent:withdrawApplyPwd:" . $_SESSION['userId']) ?? 0;
        if($i >= 5) {
            return $response->withJson([
                'success' => 0,
                'result' => '提现密码错误5次，请24小时之后重试',
            ]);
        }
        if($newPwd != $pwd) {
            $redis->setex("agent:withdrawApplyPwd:" . $_SESSION['userId'], 60*60*24, ++$i);
            return $response->withJson([
                'success' => 0,
                'result' => '提现密码出错'.$i  .'次',
            ]);
        }

        global $app;
        $db = $app->getContainer()->database;
        try {
            $db->getConnection()->beginTransaction();
            $agent = new Agent();
            $a = $agent->where('id', $_SESSION['userId'])->lockForUpdate()->first();
            if(!$a) {
                return $response->withJson([
                    'success' => 0,
                    'result' => '账户异常请联系商务人员',
                ]);
            }
            if($a['balance'] < $withdrawMoney) {
                return $response->withJson([
                    'success' => 0,
                    'result' => '可提余额不足：' .$a['balance'] .'元',
                ]);
            }
            $feeCon = $this->code['agentType']['withdrawFee'] ?? ['WAY' => 'FixedValue','VALUE' => 10];
            $fee = 0;
            switch ($feeCon['WAY']) {
                case 'FixedValue' : $fee = $feeCon['VALUE'];break;
                case 'Rate' : $fee = $feeCon['VALUE'] * $withdrawMoney;break;
                default : $fee = $feeCon['VALUE'] + $feeCon['VALUE2'] * $withdrawMoney;break;
            }
            //减少余额，即可提余额
            $b = $a->balance;
            $a->balance = $a->balance - $withdrawMoney - $fee;
            $a->save();
            $pOrder = Tools::getPlatformOrderNo('W');
            //写流水
            $f = new AgentFinance();
            $f->agentId = $_SESSION['userId'];
            $f->agentName = $_SESSION['loginName'];
            $f->agentId = $_SESSION['userId'];
            $f->platformOrderNo = $pOrder;
            $f->dealMoney = $withdrawMoney;
            $f->balance = $b - $withdrawMoney;
            $f->freezeBalance = $a->freezeBalance;
            $f->freezeBalance = $a->freezeBalance;
            $f->bailBalance = $a->bailBalance;
            $f->dealType = 'extract';
            $f->status = 'Normal';
            $f->desc = '提款';
            $f->save();
            //写流水 手续费
            $f = new AgentFinance();
            $f->agentId = $_SESSION['userId'];
            $f->agentName = $_SESSION['loginName'];
            $f->agentId = $_SESSION['userId'];
            $f->platformOrderNo = $pOrder;
            $f->dealMoney = $fee;
            $f->balance = $b - $withdrawMoney - $fee;
            $f->freezeBalance = $a->freezeBalance;
            $f->freezeBalance = $a->freezeBalance;
            $f->bailBalance = $a->bailBalance;
            $f->dealType = 'extractFee';
            $f->status = 'Normal';
            $f->desc = '提款手续费';
            $f->save();
            //写订单
            $model = new AgentWithdrawOrder();
            $model->agentId = $_SESSION['userId'];
            $model->agentName = $_SESSION['loginName'];
            $model->bankId = $bankId;
            $model->platformOrderNo = $pOrder;
            $model->dealMoney = $withdrawMoney;
            $model->realMoney = $withdrawMoney;
            $model->fee = $fee;
            $model->appIP = Tools::getIp();
            $model->appDesc = $withdrawDesc;
            $model->save();
            $agent->refreshCache(['id'=>$_SESSION['userId']]);
            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            return $response->withJson([
                'result' => '提现申请失败，稍后重试',
                'success' => 0,
            ]);
            $this->c->logger->error('Exception:' . $e->getMessage());
        }
        return $response->withJson([
            'result' => '提现申请成功',
            'success' => 1,
        ]);
    }

    public function search(Request $request, Response $response, $args){
        $limit = (int) $request->getParam('limit', 20);
        $offset = (int) $request->getParam('offset', 0);
        $model = new AgentWithdrawOrder();
        $model = $model->leftJoin('agent_bank_card','agent_bank_card.id','=','agent_withdraw_order.bankId');
        $model->where('agent_withdraw_order.agentId',$_SESSION['userId']);
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->get([
            'agent_withdraw_order.created_at as appDate',
            'agent_withdraw_order.updated_at as prosDate',
            'agent_withdraw_order.dealMoney',
            'agent_withdraw_order.realMoney',
            'agent_bank_card.accountNo',
            'agent_withdraw_order.status',
            'agent_withdraw_order.optDesc',
        ]);
        foreach ($res as &$val){
            $val['accountNo'] = Tools::decrypt($val['accountNo']);
        }
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => $total,
        ]);
    }

}
