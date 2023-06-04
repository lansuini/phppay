<?php
/**
 * 商户余额转换
 * User: Taylor
 * Date: 2019/11/21
 * Time: 10:01
 */
namespace App\Controllers\GM;

use App\Models\Merchant;
use App\Models\MerchantTransformRate;
use App\Models\SystemAccountActionLog;
use App\Helpers\Tools;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class TransformController extends GMController{
    public function rate(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/transform/rate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    //商户转换费率列表搜索API
    public function rateSearch(Request $request, Response $response, $args){
        $merchant = new Merchant();
//        $model = new MerchantTransformRate();
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');//商户号
        $merchantFlag = $request->getParam('merchantFlag');//商户简称
        $rateType = $request->getParam('rateType');//费率类型
        $status = $request->getParam('status');

        $model = $merchant->leftJoin('merchant_transform_rate as ra', 'ra.merchantId', '=', 'merchant.merchantId')
            ->select(['merchant.merchantNo', 'merchant.shortName', 'merchant.status', 'ra.rate', 'ra.fixed', 'ra.rateType', 'ra.maxServiceCharge', 'ra.minServiceCharge']);

        $merchantFlag && $model = $model = $model->where('shortName', $merchantFlag)->orWhere('fullName', $merchantFlag);
        $merchantNo && $model = $model->where('merchant.merchantNo', $merchantNo);
        $rateType && $model = $model->where('ra.rateType', $rateType);
        $status && $model = $model->where('status', $status);

        $total = $model->count();
        $data = $model->orderBy('merchant.merchantId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                "merchantNo" => $v->merchantNo,
                "minServiceCharge" => $v->minServiceCharge,
                "maxServiceCharge" => $v->maxServiceCharge,
                "rate" => $v->rate ?? '',
                "afixed" => $v->fixed ?? '',
                "rateId" => $v->rateId ? Tools::getHashId($v->rateId) : 0,
                "rateType" => $v->rateType ?? '',
                "rateTypeDesc" => $code['rateType'][$v->rateType] ?? '',
                "shortName" => $v->shortName,
                'status' => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
            ];
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    //商户转换费率
    public function rateChange(Request $request, Response $response, $args){
        $params = $request->getParams();
        $this->c->logger->debug("商户转换费率", $params);
        if(empty($params['txtMerchantNo'])){
            return $response->withJson(['result' => '商户号不能为空', 'success' => 0]);
        }
        $merchant = new Merchant;
        $merchantData = $merchant->getCacheByMerchantNo($params['txtMerchantNo']);
        if(empty($merchantData)){
            return $response->withJson(['result' => '商户信息不存在', 'success' => 0]);
        }

        $data['minServiceCharge'] = 0;
        $data['maxServiceCharge'] = 0;
        $data['merchantId'] = $merchantData['merchantId'];
        $data['merchantNo'] = $merchantData['merchantNo'];
        $data['rateType'] = $params['rateType'];
        $data['rate'] = 0;
        $data['fixed'] = 0;
        if($params['rateType'] == 'Rate'){//费率
            $data['rate'] = $params['txtRate'] ?? 0;
            $data['minServiceCharge'] = $params['txtMin'] ?? 0;
            $data['maxServiceCharge'] = $params['txtMax'] ?? 0;
            if($data['minServiceCharge'] > $data['maxServiceCharge']){
                return $response->withJson(['result' => '最小手续费不能大于最大手续费', 'success' => 0]);
            }
        }else if($params['rateType'] == 'Mixed'){//混合
            $data['rate'] = $params['txtRate'];//每笔费率
            $data['fixed'] = $params['txtFixed'];//固定费率
        }else if($params['rateType'] == 'FixedValue'){//固定
            $data['fixed'] = $params['txtFixed'];//固定费率
        }else{
            return $response->withJson(['result' => '费率收取方式不存在', 'success' => 0]);
        }

        $model = new MerchantTransformRate();
        $db = $this->c->database;
        try {
            $db->getConnection()->beginTransaction();
            $actionBeforeData = $model->where(['merchantNo'=>$params['txtMerchantNo']])->first();
            if(empty($actionBeforeData)){
                $actionBeforeData = '';
                $model->insert($data);
            }else {
                $actionBeforeData = $actionBeforeData->toJson();
                $model->where(['merchantNo'=>$params['txtMerchantNo']])->update($data);
            }

            $actionAfterData = json_encode($model->where(['merchantNo'=>$params['txtMerchantNo']])->get(), JSON_UNESCAPED_UNICODE);
            SystemAccountActionLog::insert([
                'action' => 'IMPORT_MERCHANT_RATE',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $actionAfterData,
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            $db->getConnection()->commit();

            $this->c->logger->debug("商户转换费率变化", ['actionBeforeData'=>$actionBeforeData, 'actionAfterData'=>$actionAfterData]);

            return $response->withJson([
                'result' => '操作成功',
                'success' => 1,
            ]);
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            return $response->withJson([
                'result' => '操作失败:' . $e->getMessage(),
                'success' => 0,
            ]);
        }
    }
}
