<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\ChannelMerchantRate;
use App\Models\ChannelPayConfig;
use App\Models\ChannelSettlementConfig;
use App\Models\MerchantChannelSettlement;
use App\Models\PlatformSettlementOrder;
use App\Models\SystemAccountActionLog;
use App\Channels\ChannelProxy;
use function MongoDB\BSON\toJSON;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChannelController extends GMController
{
    public function merchant(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/channel/merchant.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function merchantSearch(Request $request, Response $response, $args)
    {
        // $merchant = new ChannelMerchant();
        $model = new ChannelMerchant();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $channel = $request->getParam('channel');
        $status = $request->getParam('status');
        $offset = $request->getParam('offset');
        $merchantId = 0;

        $merchantNo && $model = $model->where('channelMerchantNo', $merchantNo);
        $channel && $model = $model->where('channel', $channel);
        $status && $model = $model->where('status', $status);
        $model = $model->where('status','!=' ,'Deleted');
        $total = $model->count();
        $data = $model->offset($offset)->limit($limit)->get();
        $rows = [];
        $settlement = new MerchantChannelSettlement();
        foreach ($data ?? [] as $k => $v) {
            if(isset($v->param)) {
                $tmp = json_decode($v->param, true);
                if (!is_array($tmp)) {
                    $tmp = json_decode(Tools::decrypt($v->param), true);
                }
            }
            if(!isset($tmp['appAccount']) && isset($tmp['company'])) {
                $tmp['appAccount'] = $tmp['company'];
            }
            $nv = [
                "channel" => $v->channel,
                "channelDesc" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['name'] : '',
                "insTime" => Tools::getJSDatetime($v->endTime),
                "merchantId" => $v->channelMerchantId,
                "channelMerchantId" => Tools::getHashId($v->channelMerchantId),
                "merchantNo" => $v->channelMerchantNo,
                // "openEntrustSettlement" => $v->openEntrustSettlement,
                "openPay" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['openPay'] : false,
                "openQuery" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['openQuery'] : false,
                "openSettlement" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['openSettlement'] : false,
                'status' => $v->status,
                "channelAccount" => $tmp['appAccount'] ?? '未填写',
                'statusDesc' => $code['commonStatus'][$v->status] ?? '',
                "param" => self::getParams($v->param),
            ];
            $nv['balance'] = $settlement->getCacheByChannelMerchantNo($v->channelMerchantNo)['accountBalance'] ?? 0;
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    //批量更新状态
    public function batchUpdate(Request $request, Response $response, $args){
        $ids = $request->getParam('ids');
        $status = $request->getParam('status');

        if (empty($ids) || empty($status) || !in_array($status, ['Normal', 'Close', 'Exception'])) {
            return $response->withJson([
                'result' => '没有操作数据',
                'success' => 0,
            ]);
        } else {
            $model = new ChannelMerchant();
            $id_arr = explode(",", $ids);
            foreach ($id_arr as $id){
                $merchantId = Tools::getIdByHash($id);
                $data = $model->where('channelMerchantId', $merchantId)->first();
                $actionBeforeData = $data->toJson();
                $data->status = $status;
                $data->save();
                SystemAccountActionLog::insert([
                    'action' => 'UPDATE_CHANNEL_MERCHANT',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => $data->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);
                $model->refreshCache(['channelMerchantNo' => $data->channelMerchantNo]);
            }
            return $response->withJson([
                'result' => '修改成功',
                'success' => 1,
            ]);
        }
    }

    public function getParams($param){
        $params=Tools::decrypt($param);
        $paramsArr=json_decode($params,true);
        $newArr=[];

        /**
         * cId 商户号
         * ipWhite ip白名单
         * settlementMerchantNo  代付商户号
         * appAccount 支付宝账号
         */
        $arr = ['cId','cid', 'ipWhite', 'settlementMerchantNo', 'appAccount','appId','company'];
        foreach ($paramsArr ?? [] as $key=>$item) {
            if(in_array($key,$arr)){
                $newArr[$key]=$item;
            }else{
                $newArr[$key]='';
            }
        }
        $newArr=json_encode($newArr,JSON_UNESCAPED_UNICODE);
        return $newArr;
    }

    public function getDetail(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $model = new ChannelMerchant();
        $data = $model->where('channelMerchantNo', $merchantNo)->first();
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            $code = $this->c->code;
            $data = $data->toArray();
            $data['insTime'] = Tools::getJSDatetime($data['created_at']);
            $data['merchantNo'] = $data['channelMerchantNo'];
            $data['merchantId'] = Tools::getHashId($data['channelMerchantId']);
//            unset($data['created_at'], $data['updated_at'], $data['channelMerchantNo'], $data['channelMerchantId']);
            $data['param'] = self::getParams($data['param']);
//            $data['param'] = json_decode(Tools::decrypt($data['param']));
            $desc = isset($code['channel'][$data['channel']]['paramDesc']) ? $code['channel'][$data['channel']]['paramDesc'] : '';
            return $response->withJson([
                'result' => $data,
                'success' => 1,
                'desc' => $desc,
            ]);
        }
    }


    public function uploadCheckFile(Request $request, Response $response, $args){

        $merchantNo = $request->getParam('merchantNo');
        $date = $request->getParam('date');
        $channelMerchantModel = new ChannelMerchant();
        $data = $channelMerchantModel->getCacheByChannelMerchantNo($merchantNo);
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $res = (new ChannelProxy)->uploadCheckFile($data['channelMerchantId'], date('Ymd', strtotime($date)));
        return $response->withJson([
            'result' => $res['msg'] == 'SUCCESS' ? $res['url'] : $res['msg'],
            'success' => $res['success'],
        ]);
    }

    public function resetset(Request $request, Response $response, $args)
    {
        $openPay = $request->getParam('openPay');
        $merchantNo = $request->getParam('merchantNo');
        $openSettlement = $request->getParam('openSettlement');
        $oneSettlementMaxAmount = $request->getParam('oneSettlementMaxAmount');
        $openEntrustSettlement = $request->getParam('openEntrustSettlement');
        $openWorkdaySettlement = $request->getParam('openWorkdaySettlement');
        $workdaySettlementType = $request->getParam('workdaySettlementType');
        $workdaySettlementRate = $request->getParam('workdaySettlementRate');
        $workdaySettlementRate = $workdaySettlementRate > 1 ? 1 : $workdaySettlementRate;
        $workdaySettlementMaxAmount = $request->getParam('workdaySettlementMaxAmount');
        $openHolidaySettlement = $request->getParam('openHolidaySettlement');
        $holidaySettlementType = $request->getParam('holidaySettlementType');
        $holidaySettlementRate = $request->getParam('holidaySettlementRate');
        $holidaySettlementRate = $holidaySettlementRate > 1 ? 1 : $holidaySettlementRate;
        $holidaySettlementMaxAmount = $request->getParam('holidaySettlementMaxAmount');
        $settlementTime = $request->getParam('settlementTime');
        $settlementTime = $settlementTime > 23 ? 0 : $settlementTime;
        $model = new ChannelMerchant();
        $data = $model->where('channelMerchantNo', $merchantNo)->first();
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            $actionBeforeData = $data->toJson();
            $data->openPay = $openPay;
            $data->openSettlement = $openSettlement;
            $data->oneSettlementMaxAmount = $oneSettlementMaxAmount;
            $data->openEntrustSettlement = $openEntrustSettlement;
            $data->openWorkdaySettlement = $openWorkdaySettlement;
            $data->workdaySettlementType = $workdaySettlementType;
            $data->workdaySettlementRate = $workdaySettlementRate;
            $data->workdaySettlementMaxAmount = $workdaySettlementMaxAmount;
            $data->openHolidaySettlement = $openHolidaySettlement;
            $data->holidaySettlementType = $holidaySettlementType;
            $data->holidaySettlementRate = $holidaySettlementRate;
            $data->holidaySettlementMaxAmount = $holidaySettlementMaxAmount;
            $data->settlementTime = $settlementTime;
            $data->save();
            $model->refreshCache(['channelMerchantNo' => $merchantNo]);
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_CHANNEL_MERCHANT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $data->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            return $response->withJson([
                'result' => '修改成功',
                'success' => 1,
            ]);
        }
    }

    public function insert(Request $request, Response $response, $args)
    {
        $code = $this->c->code;
        $channel = $request->getParam('channel');
        $merchantNo = $request->getParam('merchantNo');
        $delegateDomain = $request->getParam('delegateDomain', '');
        $param = $request->getParam('param', '');
        $channel_param =  $code['channel'][$channel]['param'];
        $diff = $this->array_diff($channel_param, $param);
        $param = Tools::encrypt(json_encode($diff));
        $model = new ChannelMerchant();
        $data = $model->where('channelMerchantNo', $merchantNo)->first();

        if (empty($merchantNo)) {
            return $response->withJson([
                'result' => '商户号不能为空',
                'success' => 0,
            ]);
        }
        if (!empty($data)) {
            return $response->withJson([
                'result' => '数据已存在',
                'success' => 0,
            ]);
        } else {
            $model->channelMerchantNo = $merchantNo;
            $model->param = $param;
            $model->delegateDomain = $delegateDomain;
            $model->platformNo = $merchantNo;
            $model->channel = $channel;
            $model->save();
            $model->refreshCache(['channelMerchantNo' => $merchantNo]);
            SystemAccountActionLog::insert([
                'action' => 'CREATE_CHANNEL_MERCHANT',
                'actionBeforeData' => '',
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            return $response->withJson([
                'result' => '添加成功',
                'success' => 1,
            ]);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        $code = $this->c->code;
        $channel = $request->getParam('channel');
        $merchantNo = $request->getParam('merchantNo');
        $merchantId = $request->getParam('merchantId');
        $delegateDomain = $request->getParam('delegateDomain', '');
        $param = $request->getParam('param', '');
        $channel_param =  $code['channel'][$channel]['param'];
        $diff = $this->array_diff($channel_param, $param);
        $diff=array_filter($diff);
        $merchantId = Tools::getIdByHash($merchantId);
        $status = $request->getParam('status');
        $model = new ChannelMerchant();
        $data = $model->where('channelMerchantId', $merchantId)->first();
        $arr = json_decode(Tools::decrypt($data->param), true) ?? [];
        $newArr=array_merge($arr,$diff);
        $param = Tools::encrypt(json_encode($newArr));
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            if($status != 'Normal' && $this->c->redis->get('cacheAlipayBalance') == $merchantId) {
                $this->c->redis->setex('cacheAlipayBalance', 7*60*60*24, 0);
            }
            // $model->channelMerchantNo = $merchantNo;
            $actionBeforeData = $data->toJson();
            $data->param = $param;
            $data->delegateDomain = $delegateDomain;
            $data->platformNo = $merchantNo;
            $data->channel = $channel;
            $data->status = $status;
            $data->save();
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_CHANNEL_MERCHANT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $data->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            $model->refreshCache(['channelMerchantNo' => $merchantNo]);
            return $response->withJson([
                'result' => '修改成功',
                'success' => 1,
            ]);
        }
    }

    public function rate(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/channel/rate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/channelMerchantRateTmpl.csv',
        ]);
    }

    public function rateSearch(Request $request, Response $response, $args)
    {
        $merchant = new ChannelMerchant();
        $model = new ChannelMerchantRate();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $merchantFlag = $request->getParam('merchantFlag');
        $productType = $request->getParam('productType');
        $payType = $request->getParam('payType');
        $rateType = $request->getParam('rateType');
        $status = $request->getParam('status');
        $offset = $request->getParam('offset');
        $merchantId = 0;

        $merchantNo && $merchantData = $merchant->getCacheByChannelMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['channelMerchantId'];
        $merchantNo && $model = $model->where('channelMerchantId', $merchantId);
        $payType && $model = $model->where('payType', $payType);
        $rateType && $model = $model->where('rateType', $rateType);
        $productType && $model = $model->where('productType', $productType);
        $status && $model = $model->where('status', $status);

        if ($merchantFlag) {
            $channel = '';
            foreach ($code['channel'] as $k => $v) {
                if ($merchantFlag == $v['name']) {
                    $channel = $k;
                    break;
                }
            }
            $model = $model->where('channel', $channel);
        }
        $total = $model->count();
        $data = $model->offset($offset)->limit($limit)->orderBy('rateId', 'desc')->get();
        $rows = [];
        // $merchantData = [];
        foreach ($data ?? [] as $k => $v) {
            // $merchantData[$v->channelMerchantId] = isset($merchantData[$v->channelMerchantId]) ? $merchantData[$v->channelMerchantId]
            //  : $merchant->getCacheByChannelMerchantId($v->channelMerchantId);
            // $channel = !empty($merchantData[$v->channelMerchantId]) ? $merchantData[$v->channelMerchantId]['channel'] : null;
            $nv = [
                'bankCode' => $v->bankCode,
                "bankCodeDesc" => $code['bankCode'][$v->bankCode] ?? '',
                "beginTime" => Tools::getJSDatetime($v->beginTime),
                "cardType" => $v->cardType,
                "cardTypeDesc" => $code['cardType'][$v->cardType] ?? '',
                "channel" => $v->channel,
                "channelDesc" => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['name'] : '',
                "endTime" => Tools::getJSDatetime($v->endTime),
                "maxServiceCharge" => $v->maxServiceCharge,
                "merchantNo" => $v->channelMerchantNo,
                "minServiceCharge" => $v->minServiceCharge,
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType] ?? '',
                "productType" => $v->productType,
                "productTypeDesc" => $code['productType'][$v->productType] ?? '',
                'rate' => $v->rate,
                'fixed' => $v->fixed,
                'rateId' => Tools::getHashId($v->rateId),
                'rateType' => $v->rateType,
                'rateTypeDesc' => $code['rateType'][$v->rateType] ?? '',
                'shortName' => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['name'] : '',
                'status' => $v->status,
                'statusDesc' => $code['commonStatus'][$v->status] ?? '',
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

    public function paychannel(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/channel/paychannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/ChannelPayTmpl.csv',
        ]);
    }

    public function paychannelSearch(Request $request, Response $response, $args)
    {
        $model = new ChannelPayConfig();
        $merchantData = [];
        $code = $this->c->code;
        $limit = (int) $request->getParam('limit', 20);
        $offset = (int) $request->getParam('offset', 0);
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $channel = $request->getParam('channel');

        $wheres = [];
        $value = [];
        $where[] = '1=1';

        $channel && $where[] = "channel=?";
        $channelMerchantNo && $where[] = "channelMerchantNo=?";
        $channel && $value[] = $channel;
        $channelMerchantNo && $value[] = $channelMerchantNo;
        $whereStr = implode(' and ', $where);

        $total = \Illuminate\Database\Capsule\Manager::select("select count(*) from (select GROUP_CONCAT(payType) as payTypes from channel_pay_config where {$whereStr} group by channelMerchantId) a", $value);

        $value[] = $limit;
        $value[] = $offset;
        $total = current(current($total));

        $data = \Illuminate\Database\Capsule\Manager::select("select channel,channelMerchantNo,GROUP_CONCAT(payType) as payTypes from channel_pay_config where {$whereStr} group by channelMerchantId limit ? offset ?", $value);

        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $payTypes = explode(',', $v->payTypes);
            $payTypeDescs = [];
            foreach ($payTypes ?? [] as $payType) {
                $payTypeDescs[] = $code['payType'][$payType];
            }
            $nv = [
                'channelMerchantNo' => $v->channelMerchantNo,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                "payTypeDescs" => $payTypeDescs,
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

    public function paychannelImport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $channelMerchant = new ChannelMerchant;

        $channelData = $channelMerchant->getCacheByChannelMerchantNo($merchantNo);

        if (empty($channelData)) {
            return $response->withJson([
                'result' => '渠道号不存在',
                'success' => 0,
            ]);
        }

        $file = $request->getUploadedFiles();
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '文件不能为空',
                'success' => 0,
            ]);
        }

        $csv = new \ParseCsv\Csv();
        $csv->fields = ['channelMerchantNo', 'channel',
            'payChannelStatus', 'payType', 'bankCode', 'cardType',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit',
            'openTimeLimit', 'beginTime', 'endTime', 'status'];
        $csv->auto($file['file']->file);
        $data = $csv->data;

        foreach ($data ?? [] as $k => $v) {
            foreach ($v as $a => $b) {
                $v[$a] = str_replace(["'", ' '], '', $b);
            }

            if ($v['channelMerchantNo'] != $channelData['channelMerchantNo']) {
                return $response->withJson([
                    'result' => '渠道商户号' . $v['channelMerchantNo'] . '与' . $channelData['channelMerchantNo'] . "不同",
                    'success' => 0,
                ]);
            }
            if ($v['channel'] != $channelData['channel']) {
                return $response->withJson([
                    'result' => '渠道商户名' . $v['channel'] . '与' . $channelData['channel'] . "不同",
                    'success' => 0,
                ]);
            }
            $v['channelMerchantId'] = $channelData['channelMerchantId'];

            $data[$k] = $v;
        }

        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model = new ChannelPayConfig();

                $actionBeforeData = $model->getCacheByChannelMerchantNo($channelData['channelMerchantNo']);
                // var_dump($actionBeforeData);exit;
                if (empty($actionBeforeData)) {
                    $actionBeforeData = '';
                } else {
                    $actionBeforeData = json_encode($actionBeforeData,JSON_UNESCAPED_UNICODE);

                }
                $model->where('channelMerchantNo', $channelData['channelMerchantNo'])->delete();
                $model->insert($data);

                SystemAccountActionLog::insert([
                        'action' => 'IMPORT_CHANNEL_PAY_CONFIG',
                        'actionBeforeData' => $actionBeforeData,
                        'actionAfterData' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'status' => 'Success',
                        'accountId' => $_SESSION['accountId'],
                        'ip' => Tools::getIp(),
                        'ipDesc' => Tools::getIpDesc(),
                    ]);

                $model->refreshCache(['channelMerchantNo'=>$channelData['channelMerchantNo']]);

                $db->getConnection()->commit();

                return $response->withJson([
                    'result' => '上传成功',
                    'success' => 1,
                ]);
            } catch (\Exception $e) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => '上传失败:' . $e->getMessage(),
                    'success' => 0,
                ]);
            }
        } else {
            return $response->withJson([
                'result' => '上传失败,内容解析失败',
                'success' => 0,
            ]);
        }
    }

    public function paychannelExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('channelNo');
        $model = new ChannelPayConfig();
        $merchantData = [];
        $model = $model->where('channelMerchantNo', $merchantNo);
        $total = $model->count();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                "bankCode" => $v->bankCode,
                "beginTime" => $v->beginTime,
                "channel" => $v->channel,
                "channelMerchantId" => $v->channelMerchantId > 0 ? Tools::getHashId($v->channelMerchantId) : '',
                "channelMerchantNo" => $v->channelMerchantNo,
                "dayAmountLimit" => $v->dayAmountLimit,
                "dayNumLimit" => $v->dayNumLimit,
                "endTime" => $v->endTime,
                "merchantNo" => $v->merchantNo,
                "oneMaxAmount" => $v->oneMaxAmount,
                "oneMinAmount" => $v->oneMinAmount,
                "openDayAmountLimit" => $v->openDayAmountLimit,
                "openDayNumLimit" => $v->openDayNumLimit,
                "openOneAmountLimit" => $v->openOneAmountLimit,
                "openTimeLimit" => $v->openTimeLimit,
                "payChannelStatus" => $v->payChannelStatus,
                "payType" => $v->payType,
                "cardType" => $v->cardType,
                "setId" => Tools::getHashId($v->setId),
                "status" => $v->status,
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

    public function settlementchannel(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/channel/settlementchannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/ChannelSettlementTmpl.csv',
        ]);
    }
    
    public function settlementchannelSearch(Request $request, Response $response, $args)
    {
        $model = new ChannelSettlementConfig();
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $channel = $request->getParam('channel');
        $channelMerchantNo && $model = $model->where('channelMerchantNo', $channelMerchantNo);
        $channel && $model = $model->where('channel', $channel);

        $total = $model->count();
        $data = $model->offset($offset)
            ->limit($limit)
            ->orderBy('setId', 'desc')
            ->get();
        $rows = [];

        foreach ($data ?? [] as $k => $v) {

            // $channelBalance[$v->channelMerchantNo] = isset($channelBalance[$v->channelMerchantNo]) ? $channelBalance[$v->channelMerchantNo]
            // : $channel->getBalance($v->channelMerchantNo);

            $nv = [
                // 'accountBalance' => $channelBalance[$v->channelMerchantNo],
                'accountBalance' => $v->accountBalance,
                'channel' => $v->channel,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                // 'channelMerchantId' => Tools::getHashId($v->channelMerchantId),
                'channelMerchantNo' => $v->channelMerchantNo,
                // "setId" => Tools::getHashId($v->setId),
                "settlementAccountType" => $v->settlementAccountType,
                "settlementAccountTypeDesc" => $code['settlementAccountType'][$v->settlementAccountType] ?? "",
                "shortName" => $merchantData[$v->merchantId]['shortName'],
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
    
    public function settlementchannelImport(Request $request, Response $response, $args)
    {
        $channelNo = $request->getParam('merchantNo');
        $channelMerchant = new ChannelMerchant;

        $channelData = $channelMerchant->getCacheByChannelMerchantNo($channelNo);
        if (empty($channelData)) {
            return $response->withJson([
                'result' => '渠道号不存在',
                'success' => 0,
            ]);
        }

        $model = new ChannelSettlementConfig;
        $channelMerchantData = [];

        $file = $request->getUploadedFiles();
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '文件不能为空',
                'success' => 0,
            ]);
        }

        $actionBeforeData = $model->where('channelMerchantNo', $channelNo)->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();

        }
        $csv = new \ParseCsv\Csv();
        $csv->fields = ['channelMerchantNo', 'channel',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit','openCardDayNumLimit','cardDayNumLimit',
            'openOneSettlementMaxAmountLimit','oneSettlementMaxAmount','openTimeLimit', 'beginTime', 'endTime', 'status'];
        $csv->auto($file['file']->file);
        $data = $csv->data;
        foreach ($data ?? [] as $k => $v) {
            foreach ($v as $a => $b) {
                $v[$a] = str_replace(["'", ' '], '', $b);
            }

            
            if ($v['channelMerchantNo'] != $channelData['channelMerchantNo']) {
                return $response->withJson([
                    'result' => '渠道商户号' . $v['channelMerchantNo'] . '与' . $channelData['channelMerchantNo'] . "不同",
                    'success' => 0,
                ]);
            }
            if ($v['channel'] != $channelData['channel']) {
                return $response->withJson([
                    'result' => '渠道商户名' . $v['channel'] . '与' . $channelData['channel'] . "不同",
                    'success' => 0,
                ]);
            }

            // $channelMerchantData[$v['channelMerchantNo']] = isset($channelMerchantData[$v['channelMerchantNo']]) ? $channelMerchantData[$v['channelMerchantNo']]
            // : $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
            $v['channelMerchantId'] = intval($channelData['channelMerchantId']);
            $v['settlementAccountType'] = 'UsableAccount';
            $v['accountBalance'] = 0;
            $v['accountReservedBalance'] = 0;
            if ($v['channelMerchantId'] == 0) {
                return $response->withJson([
                    'result' => '渠道商户号不存在:' . $v['channelMerchantNo'] . ':' . $v['channel'],
                    'success' => 0,
                ]);
            }
            $data[$k] = $v;
        }

        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model->where('channelMerchantId', $channelData['channelMerchantId'])->delete();
                $model->insert($data);

                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_CHANNEL_SETTLEMENT_CONFIG',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByMerchantId($merchantData['merchantId']), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);

                $model->refreshCache(['channelMerchantNo' => $channelNo]);
                // (new Amount)->init($merchantData['merchantId'], $merchantData['merchantNo']);
                $db->getConnection()->commit();

                return $response->withJson([
                    'result' => '上传成功',
                    'success' => 1,
                ]);
            } catch (\Exception $e) {
                // $logger->debug("create失败" . $e->getMessage());
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => '上传失败:' . $e->getMessage(),
                    'success' => 0,
                ]);
            }
        } else {
            return $response->withJson([
                'result' => '上传失败,内容解析失败',
                'success' => 0,
            ]);
        }
    }

    public function settlementchannelExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('channelNo');
        $model = new ChannelSettlementConfig;
        $merchantData = [];
        $model = $model->where('channelMerchantNo', $merchantNo);
        $total = $model->count();
        // $data = $model->offset($offset)->limit($limit)->get();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            // $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
            // : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [
                // "accountBalance" => $v->accountBalance,
                // "accountReservedBalance" => $v->accountReservedBalance,
                "beginTime" => $v->beginTime,
                "channel" => $v->channel,
                "channelMerchantId" => $v->channelMerchantId > 0 ? Tools::getHashId($v->channelMerchantId) : '',
                "channelMerchantNo" => $v->channelMerchantNo,
                "dayAmountLimit" => $v->dayAmountLimit,
                "dayNumLimit" => $v->dayNumLimit,
                "endTime" => $v->endTime,
                "merchantNo" => $v->merchantNo,
                "oneMaxAmount" => $v->oneMaxAmount,
                "oneMinAmount" => $v->oneMinAmount,
                "openDayAmountLimit" => $v->openDayAmountLimit,
                "openDayNumLimit" => $v->openDayNumLimit,
                "openOneAmountLimit" => $v->openOneAmountLimit,
                "openTimeLimit" => $v->openTimeLimit,

                
                "openCardDayNumLimit" => $v->openCardDayNumLimit,
                "cardDayNumLimit" => $v->cardDayNumLimit,
                "openOneSettlementMaxAmountLimit" => $v->openOneSettlementMaxAmountLimit,
                "oneSettlementMaxAmount" => $v->oneSettlementMaxAmount,

                "setId" => Tools::getHashId($v->setId),
                // "settlementAccountType" => $v->settlementAccountType,
                // "settlementChannelStatus" => $v->settlementChannelStatus,
                "status" => $v->status,
            ];
            $rows[] = $nv;
        }
        // var_dump($rows);exit;
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    public function rateImport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $merchant = new ChannelMerchant;
        $model = new ChannelMerchantRate;
        $merchantData = $merchant->getCacheByChannelMerchantNo($merchantNo);
        if (empty($merchantData)) {
            return $response->withJson([
                'result' => '商户号不存在',
                'success' => 0,
            ]);
        }

        $file = $request->getUploadedFiles();
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '文件不能为空',
                'success' => 0,
            ]);
        }
        // $rateData = $model->getCacheByChannelMerchantId($merchantData['channelMerchantId']);
        // if (!empty($rateData)) {
        //     return $response->withJson([
        //         'result' => '商户配置已存在',
        //         'success' => 0,
        //     ]);
        // }
        $actionBeforeData = $model->where('channelMerchantId', $merchantData['channelMerchantId'])->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();
        }
        $csv = new \ParseCsv\Csv();
        $csv->fields = ['channelMerchantNo', 'productType', 'payType',
            'bankCode', 'cardType','minAmount','maxAmount', 'rateType', 'rate','fixed', 'minServiceCharge',
            'maxServiceCharge', 'beginTime', 'endTime', 'status'];
        $csv->auto($file['file']->file);
        $data = $csv->data;

        foreach ($data ?? [] as $k => $v) {
            foreach ($v as $a => $b) {
                $v[$a] = str_replace(["'", ' '], '', $b);
            }

            if (empty($v['channelMerchantNo'])) {
                unset($data[$k]);
                continue;
            }

            if ($v['channelMerchantNo'] != $merchantData['channelMerchantNo']) {
                return $response->withJson([
                    'result' => '配置渠道商户号不正确',
                    'success' => 0,
                ]);
            }
            $v['channelMerchantId'] = $merchantData['channelMerchantId'];
            $v['channelMerchantNo'] = $merchantData['channelMerchantNo'];
            $v['channel'] = $merchantData['channel'];
            $v['endTime'] = $v['endTime'] ? $v['endTime'] : null;
            $v['minAmount'] = (float) $v['minAmount'];
            $v['maxAmount'] = (float) $v['maxAmount'];
            $v['minServiceCharge'] = (float) $v['minServiceCharge'];
            $v['maxServiceCharge'] = (float) $v['maxServiceCharge'];
            $v['cardType'] = empty($v['cardType']) ? 'DEBIT' : $v['cardType'];
            if ($v['maxServiceCharge'] > 0 && $v['minServiceCharge'] > 0 && $v['maxServiceCharge'] < $v['minServiceCharge']) {
                return $response->withJson([
                    'result' => '最大费率不能少于最小费率',
                    'success' => 0,
                ]);
            }
            // $v = array_filter($v);
            $data[$k] = $v;
        }

        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model->where('channelMerchantId', $merchantData['channelMerchantId'])->delete();
                $model->insert($data);
                $model->refreshCache(['channelMerchantId' => $merchantData['channelMerchantId']]);
                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_CHANNEL_MERCHANT_RATE',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByChannelMerchantId($merchantData['channelMerchantId']), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);
                $db->getConnection()->commit();
                return $response->withJson([
                    'result' => '上传成功',
                    'success' => 1,
                ]);
            } catch (\Exception $e) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => '上传失败:' . $e->getMessage(),
                    'success' => 0,
                ]);
            }
        } else {
            return $response->withJson([
                'result' => '上传失败',
                'success' => 0,
            ]);
        }

    }

    public function rateExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $order = $request->getParam('order');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $merchant = new ChannelMerchant;
        $model = new ChannelMerchantRate;
        $merchantData = [];
        $model = $model->where('channelMerchantNo', $merchantNo);
        $total = $model->count();
        // $data = $model->offset($offset)->limit($limit)->get();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                'bankCode' => $v->bankCode,
                'bankCodeDesc' => $code['bankCode'][$v->bankCode] ?? '',
                'beginTime' => Tools::getJSDatetime($v->beginTime),
                'cardType' => $v->cardType,
                'cardTypeDesc' => $code['payType'][$v->payType] ?? '',
                "channel" => $v->channel,
                "channelDesc" => $code['channel'][$v->channel]['name'] ?? '',
                "endTime" => Tools::getJSDatetime($v->endTime),
                "maxServiceCharge" => $v->maxServiceCharge,
                "merchantNo" => $v->channelMerchantNo,
                "minServiceCharge" => $v->minServiceCharge,
                "payType" => $v->payType,
                "payTypeDesc" => $code['payType'][$v->payType] ?? '',
                "productType" => $v->productType,
                "productTypeDesc" => $code['productType'][$v->productType] ?? '',
                "rate" => $v->rate,
                "fixed" => $v->fixed,
                "rateId" => Tools::getHashId($v->rateId),
                "rateType" => $v->rateType,
                "rateTypeDesc" => $code['rateType'][$v->rateType] ?? '',
                'shortName' => isset($code['channel'][$v->channel]) ? $code['channel'][$v->channel]['name'] : '',
                'status' => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
                "minAmount" => $v->minAmount,
                "maxAmount" => $v->maxAmount,
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

    public function getChannelParameter(Request $request, Response $response, $args)
    {
        $code = $this->c->code;
        $name = $request->getParam('name');
        $param =  $code['channel'][$name];
        $res = isset($param['param']) ? $param['param'] : '';
        $desc = isset($param['paramDesc']) ? $param['paramDesc'] : '';
        return $response->withJson([
            'result' => $res,
            'success' => 1,
            'desc' => $desc,
        ]);
    }

    public function array_diff($arr, $arr2){
        if (!is_array($arr2)) {
            $arr2 = array();
        }

        foreach ($arr as $key => $record ) {
            if (!isset($arr2[$key]) && $key != 'gateway') {
                $arr2[$key] = '';
            }
        }

        return $arr2;
    }

    /**
     * 手动充值上游的余额
     */
    public function addBalance(Request $request, Response $response, $args){
        $params=$request->getParams();

        $balance=$params['balance'];
        $merchantNo=$params['merchantNo'];

        $notifyOrderNumber=$params['notifyOrderNumber'];
        $account=$params['channelAccount'];
        $desc=[
            '订单流水号：'=>$notifyOrderNumber,
            '充值账号：'=>$account
        ];

        $arr=['balance'=>'金额','merchantNo'=>'商户号','channelAccount'=>'支付宝账号'];
        foreach ($arr as $key=>$item) {
            if($params[$key]==''){
                return $response->withJson([
                    'result' => $item.'不能为空！',
                    'success' => 0,
                ]);
            }
        }

        if(!is_numeric($balance)){
            return $response->withJson([
                'result' => '金额只能是数字',
                'success' => 0,
            ]);
        }
        $merChannelSet=new MerchantChannelSettlement();
        $model = new ChannelMerchant();

        $data=$merChannelSet->where('channelMerchantNo',$merchantNo)->first(['accountBalance','channelMerchantNo','channelMerchantId','setId','merchantId','merchantNo']);
//        $model->refreshCache(['channelMerchantNo' => $merchantNo]);
//        var_dump($data);


        if(!$data){
            return $response->withJson([
                'result' => '支付通道没有调整余额功能！',
                'success' => 0,
            ]);
        }

        if($balance<0 && $data['accountBalance']-abs($balance)<0){
            return $response->withJson([
                'result' => '金额输入错误！',
                'success' => 0,
            ]);
        }
        $updateBalance = $data['accountBalance'] + $balance;
        $res=$merChannelSet->where('channelMerchantNo',$merchantNo)->update(['accountBalance'=> $updateBalance]);

//        var_dump($data[0]['accountBalance'] + $balance,$res);die;
        if($res!==false){
            $actionAfterData=$merChannelSet->where('channelMerchantNo',$merchantNo)->first(['accountBalance','channelMerchantNo','channelMerchantId','setId','merchantId','merchantNo']);
            SystemAccountActionLog::insert([
                [
                    'action' => 'UPDATE_CHANNEL_MERCHANT',
                    'actionBeforeData' => json_encode($data,JSON_UNESCAPED_UNICODE),
                    'actionAfterData' => json_encode($actionAfterData,JSON_UNESCAPED_UNICODE) .'|'.json_encode($desc,JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
            ]);
            $merChannelSet->refreshCache();
            $model->refreshCache();
            if($updateBalance > PlatformSettlementOrder::$voiceBalance && $data['channelMerchantId'] == $this->c->redis->get('cacheAlipayBalance')) {
                $this->c->redis->setex('cacheAlipayBalance', 7*60*60*24, 0);;
            }
            return $response->withJson([
                'result' => '调整余额成功',
                'success' => 1,
            ]);
        }
        return $response->withJson([
            'result' => '调整余额失败！',
            'success' => 0,
        ]);

    }

    public function queryBalance(Request $request, Response $response, $args){

        global $app;
        $redis = $app->getContainer()->redis;
        $merchantId = $request->getParam('merchantId');

        try{
            $res = (new ChannelProxy)->queryBalance($merchantId);
            if($res['status'] == 'Success'){
                $balance = (float) $res['balance'];
                $oldBalance = (float) $redis->get('cmq:' . $merchantId);
                if ($oldBalance != $balance) {

                    $merchantChantSettlements = MerchantChannelSettlement::where('channelMerchantId', $merchantId)->get()->toArray();
                    if($merchantChantSettlements){

                        ChannelSettlementConfig::where('channelMerchantId', $merchantId)->update([
                            'accountBalance' => $balance,
                        ]);
                        (new ChannelSettlementConfig)->refreshCache(['channelMerchantId' => $merchantId]);
                        foreach ($merchantChantSettlements as $merchantChantSettlement){
                            (new MerchantChannelSettlement)->refreshCache(['merchantId'=>$merchantChantSettlement->merchantId]);
                        }

                        $redis->setex('cmq:' . $merchantId, 86400, $balance);

                    }

                }

            }
        }catch (\Exception $e){
            $res['status'] = 'Fail';
            $res['balance'] = 0;
            $res['failReason'] = $e->getMessage();
        }


        return $response->withJson($res);
    }
}
