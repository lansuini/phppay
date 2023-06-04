<?php

namespace App\Controllers\Agent;

use App\Controllers\AgentController;
use App\Helpers\Tools;
use App\Models\AgentLog;
use App\Models\AgentMerchantRelation;
use App\Models\AgentRate;
use App\Models\MerchantRate;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MerchantRateController extends AgentController
{
    public function index(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'agent/merchantRate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? '',
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args)
    {
        $limit = (int)$request->getParam('limit', 20);
        $offset = (int)$request->getParam('offset', 0);
        $proType = $request->getParam('selProType');
        $payType = $request->getParam('selPayType');
        $rateType = $request->getParam('selRateType');
        $merchantNo = $request->getParam('merchantNo');
        $merchantIds = AgentMerchantRelation::where('agentId',$_SESSION['userId'])->pluck('merchantId')->toArray();
        $model = new MerchantRate();
        $proType && ($model = $model->where('productType', '=', $proType));
        $payType && ($model = $model->where('payType', '=', $payType));
        $rateType && ($model = $model->where('rateType', '=', $rateType));
        $merchantNo && ($model = $model->where('merchantNo', '=', $merchantNo));
        $model = $model->whereIn('merchantId', $merchantIds);
        $total = $model->count();
        $res = $model->offset($offset)->limit($limit)->get([
            'rateId',
            'merchantNo',
            'productType',
            'payType',
            'bankCode',
            'cardType',
            'rateType',
            'rate',
            'minServiceCharge',
            'maxServiceCharge',
            'fixed',
            'beginTime',
            'endTime',
        ]);
        foreach ($res as &$val) {
            $val['productTypeDesc'] = $this->code['productType'][$val['productType']];
            $val['payTypeDesc'] = $this->code['payType'][$val['payType']];
            $val['rateTypeDesc'] = $this->code['rateType'][$val['rateType']];
            $val['cardTypeDesc'] = $this->code['cardType'][$val['cardType']];
            $val['bankCodeDesc'] = $this->code['bankCode'][$val['bankCode']];
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
