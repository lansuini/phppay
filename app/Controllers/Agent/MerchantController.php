<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Helpers\Tools;
use App\Models\AgentLog;
use App\Models\AgentMerchantRelation;
use App\Models\AgentRate;
use App\Models\Merchant;
use App\Models\MerchantRate;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MerchantController extends AgentController
{
    public function index(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'agent/merchant.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args)
    {
        $limit = (int)$request->getParam('limit', 20);
        $offset = (int)$request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $shortName = $request->getParam('shortName');
        $model = Merchant::leftJoin('agent_merchant_relation','agent_merchant_relation.merchantId','=','merchant.merchantId');
        $model = $model->where('agent_merchant_relation.agentId',$_SESSION['userId']);
        $merchantNo && ($model = $model->where('merchant.merchantNo', '=', $merchantNo));
        $shortName && ($model = $model->where('merchant.shortName', '=', $shortName));
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->get([
            'merchant.merchantNo',
            'merchant.shortName',
            'merchant.fullName',
            'merchant.status',
            'merchant.created_at',
            'agent_merchant_relation.created_at as relation_created',
        ]);
        foreach ($res as &$val) {
            $val['statusDesc'] = $val['status'] == 'Normal' ? '正常' : '关闭';
            $val['created_at'] = date('Y-m-d H:i:s',strtotime($val['created_at']));
            $val['relation_created'] = date('Y-m-d H:i:s',strtotime($val['relation_created']));
        }
        return $response->withJson([
            'result' => [],
            'rows' => $res,
            'success' => 1,
            'total' => $total,
        ]);
    }

    public function change(Request $request, Response $response, $args) {
        $model = new MerchantRate();
        $agent = new AgentRate();
        $params = $request->getParams();
        $mRate = $model->find($params['rateId']);
        $before = $mRate->toArray();
        $aRate = $agent->where('productType',$params['productType'])->where('payType',$params['payType'])->where('status','Normal')->first();

        if(!$aRate || !$mRate) {
            return $response->withJson([
                'success' => 0,
                'result' => '不存在该费率类型或已关闭'
            ]);
        }
        if($mRate['rateType'] != $params['rateType']) {
            return $response->withJson([
                'success' => 0,
                'result' => '无权更改费率类型'
            ]);
        }
        switch ($params['rateType']) {
            case 'Rate' :
                if($params['rate'] < $aRate['rate']) {
                    return $response->withJson([
                        'success' => 0,
                        'result' => '费率值不能低于自身'
                    ]);
                };
            case 'FixedValue' :
                if($params['fixed'] < $aRate['fixed']) {
                    return $response->withJson([
                        'success' => 0,
                        'result' => '固定值不能低于自身'
                    ]);
                };
            default:
                if($params['fixed'] < $aRate['fixed'] || $params['rate'] < $aRate['rate']) {
                    return $response->withJson([
                        'success' => 0,
                        'result' => '设定不能低于自身'
                    ]);
                }
        }
        if($params['minServiceCharge'] < $aRate['minServiceCharge']) {
            return $response->withJson([
                'success' => 0,
                'result' => '最低手续费不能低于自身'
            ]);
        }
        if($params['maxServiceCharge'] < $aRate['maxServiceCharge']) {
            return $response->withJson([
                'success' => 0,
                'result' => '最大手续费不能低于自身'
            ]);
        }
        $mRate->minServiceCharge = $params['maxServiceCharge'];
        $mRate->maxServiceCharge = $params['maxServiceCharge'];
        $mRate->fixed = $params['fixed'];
        $mRate->rate = $params['rate'];
        $mRate->save();
        $log = new AgentLog();
        $log->action = 'MERCHANT_RATE_MODIFY';
        $log->actionBeforeData = json_encode($before);
        $log->actionAfterData = json_encode($mRate->toArray());
        $log->optId = $_SESSION['userId'];
        $log->optName = $_SESSION['userName'];
        $log->status = 'Success';
        $log->desc = '代理修改商户费率信息';
        $log->ip = Tools::getIp();
        $log->ipDesc = Tools::getIpDesc(Tools::getIp());
        $log->save();
        return $response->withJson([
            'result' => 'Success',
            'success' => 1,
        ]);
    }
}
