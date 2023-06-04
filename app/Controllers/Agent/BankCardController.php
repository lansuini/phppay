<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Helpers\Tools;
use App\Models\AgentBankCard;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BankCardController extends AgentController
{
    public function setBank(Request $request, Response $response, $args){
        $bankCode = $request->getParam("bankCode");
        $accountNo = Tools::encrypt($request->getParam("accountNo"));
        $accountName = $request->getParam("accountName");
        $province = $request->getParam("province");
        $city = $request->getParam("city");
        $district = $request->getParam("district");
        $id = $request->getParam("cardId");
        $logger = $this->c->logger;

        $card = new AgentBankCard();
        if($id){
            $id = Tools::getIdByHash($id);
            $card = $card->find($id);
        }elseif ($card->where('agentId', $_SESSION['userId'])->where("status", "Normal")->where('accountNo', $accountNo)->value('id')) {
            return $response->withJson([
                'result' => '已添加此卡',
                'success' => 0,
            ]);
        }
        try {
            $card->bankName = $this->c->code['bankCode'][$bankCode];
            $card->agentId = $_SESSION['userId'];
            $card->bankCode = $bankCode;
            $card->accountNo = $accountNo;
            $card->accountName = $accountName;
            $card->province = $province;
            $card->city = $city;
            $card->district = $district;
            $card->save();
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
        }
        return $response->withJson([
            'result' => $id ? '更新成功' : '新增成功',
            'success' => 1,
        ]);
    }

    public function search(Request $request, Response $response, $args){
        $model = new AgentBankCard();
        $res = $model->where('agentId',$_SESSION['userId'])->get();
        foreach ($res as &$val){
            $val['cardId'] = Tools::getHashId($val['id']);
            $val['accountNo'] = Tools::decrypt($val['accountNo']);
            $val['account'] = $val['bankName'].":".$val['accountName'].'('.$val['accountNo'].')';
            unset($val['id']);
            unset($val['agentId']);
        }
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => count($res),
        ]);
    }

    public function delete(Request $request, Response $response, $args){
        $id = Tools::getIdByHash($request->getParam("cardId"));
        $model = new AgentBankCard();
        $res = $model->where('agentId',$_SESSION['userId'])->where('id',$id)->delete();
        return $response->withJson([
            'result' => $res ? '删除成功' : '删除失败',
            'success' => 1,
        ]);
    }
}
