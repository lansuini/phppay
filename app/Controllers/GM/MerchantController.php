<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Models\Agent;
use App\Models\AgentLog;
use App\Models\AgentMerchantRelation;
use App\Models\AgentRate;
use App\Models\Amount;
use App\Models\Channel;
use App\Models\ChannelMerchant;
use App\Models\ChannelMerchantRate;
use App\Models\Merchant;
use App\Models\MerchantAccount;
use App\Models\MerchantAmount;
use App\Models\SystemAccount;
use App\Models\SystemCheckLog;
use App\Models\MerchantChannel;
use App\Models\MerchantChannelSettlement;
use App\Models\MerchantChannelRecharge;
use App\Models\MerchantRate;
use App\Models\MerchantNotice;
use App\Models\SystemAccountActionLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator;

class MerchantController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/merchant/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function getnextmerchantno(Request $request, Response $response, $args)
    {
        $merchant = Merchant::orderBy('merchantId', 'desc')->first();
        $merchantNo = $merchant ? intval($merchant->merchantNo) + 1 : 10000000;
        return $response->withJson([
            'result' => [
                "merchantNo" => $merchantNo,
                "loginNameCode" => ucfirst(Tools::getRandStr("qwertyuiopasdfghjklzxcvbnm", 4)),
            ],
            'success' => 1,
        ]);
    }

    public function insert(Request $request, Response $response, $args)
    {
        $shortName = $request->getParam('shortName');
        $merchantNo = $request->getParam('merchantNo');
        $fullName = $request->getParam('fullName');
        $description = $request->getParam('description');
        $loginName = $request->getParam('loginName');
        $loginPwd = $request->getParam('loginPwd');
        // $userName = $request->getParam('userName');
        $securePwd = $request->getParam('securePwd');
        $settlementType = $request->getParam('settlementType');


        $settlementTypeCode = \array_keys($this->code['settlementType']);
        $validator = $this->c->validator->validate($request, [
            'shortName' => Validator::noWhitespace()->notBlank(),
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'fullName' => Validator::noWhitespace()->notBlank(),
            'description' => Validator::noWhitespace()->notBlank(),
            'loginName' => Validator::noWhitespace()->notBlank(),
            'loginPwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            // 'userName' => Validator::noWhitespace()->notBlank(),
            'securePwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            'settlementType' => Validator::in($settlementTypeCode)->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        //代理账参数处理
        $agentLoginName = $request->getParam('agentLoginName');
        if ($agentLoginName) {
            $agent = new Agent();
            $agentData = $agent->getCacheByLoginName($agentLoginName);
            if (!$agentData) {
                return $response->withJson([
                    'result' => '此代理账号不存在',
                    'success' => 0,
                ]);
            }
        }

        $merchant = new Merchant;
        $merchantAccount = new MerchantAccount;
        $data = $merchant->where('merchantNo', $merchantNo)->first();
        if (!empty($data)) {
            return $response->withJson([
                'result' => '数据已存在',
                'success' => 0,
            ]);
        }

//        $account = $merchantAccount->where('loginName', $loginName)->first();
        if (!empty($data)) {
            return $response->withJson([
                'result' => '登录名称已存在',
                'success' => 0,
            ]);
        }
        $db = $this->c->database;
        try {
            $db->getConnection()->beginTransaction();
            $merchant->shortName = $shortName;
            $merchant->merchantNo = $merchantNo;
            $merchant->fullName = $fullName;
            $merchant->description = $description;
            $merchant->platformNo = $merchantNo;
            $merchant->signKey = Tools::encrypt(md5(time()));
            $merchant->openPay = false;
            $merchant->openSettlement = false;
            $merchant->settlementType = $settlementType;
            $res = $merchant->save();
            $merchantAccount->merchantId = $merchant->merchantId;
            $merchantAccount->merchantNo = $merchant->merchantNo;
            $merchantAccount->platformNo = $merchant->platformNo;
            $merchantAccount->loginName = $loginName;
            $merchantAccount->loginPwd = Tools::getHashPassword($loginPwd);
            $merchantAccount->userName = $loginName;
            $merchantAccount->securePwd = Tools::getHashPassword($securePwd);

            $merchantAccount->save();

            //新增商户成功后，代理账号不为空，则绑定代理号和商户
            if ($res && $agentLoginName) {
                $rale = new AgentMerchantRelation();
                $rale->agentId = $agentData['id'];
                $rale->merchantId = $merchant->merchantId;
                $rale->save();
                //操作者信息
                $user = new SystemAccount();
                $userData = $user->where('id', '=', $_SESSION['accountId'])->first();

                //更新代理表里面的下级人数
                $agent = $agent->where('id', $agentData['id'])->first();
                $agent->inferisorNum =$agent->inferisorNum + 1;
                $agent->save();


                AgentLog::insert([
                    'action' => 'AGENT_RELATION_MERCH',
                    'actionBeforeData' => '',
                    'actionAfterData' => $merchant->merchantNo . '绑定到' . $agentData['loginName'],
                    'optId' => $_SESSION['accountId'],
                    'optName' => $userData->userName,
                    'status' => 'Success',
                    'desc' => '代理号和商户绑定',
                    'ipDesc' => Tools::getIpDesc(),
                    'ip' => Tools::getIp()
                ]);
            }

            SystemAccountActionLog::insert([
                [
                    'action' => 'CREATE_MERCHANT',
                    'actionBeforeData' => '',
                    'actionAfterData' => $merchant->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
                [
                    'action' => 'CREATE_MERCHANT_ACCOUNT',
                    'actionBeforeData' => '',
                    'actionAfterData' => $merchantAccount->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
            ]);
            (new Amount)->init($merchant->merchantId, $merchant->merchantNo);
            $db->getConnection()->commit();
            $merchant->refreshCache(['merchantId' => $merchant->merchantId]);
            $merchantAccount->refreshCache(['merchantId' => $merchant->merchantId]);
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            return $response->withJson([
                'result' => '添加失败:' . $e->getMessage(),
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '添加成功',
            'success' => 1,
        ]);
    }

    public function update(Request $request, Response $response, $args)
    {
        $shortName = $request->getParam('shortName');
        $merchantNo = $request->getParam('merchantNo');
        $fullName = $request->getParam('fullName');
        $status = $request->getParam('status');
        $merchantId = $request->getParam('merchantId');

        $validator = $this->c->validator->validate($request, [
            'shortName' => Validator::noWhitespace()->notBlank(),
            'merchantNo' => Validator::noWhitespace()->notBlank(),
            'fullName' => Validator::noWhitespace()->notBlank(),
            // 'description' => Validator::noWhitespace()->notBlank(),
            'status' => Validator::noWhitespace()->notBlank(),
            'merchantId' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        //代理账参数处理
        $agentLoginName = $request->getParam('agentLoginName');
        $agent = new Agent();
        $agentData = [];
        if ($agentLoginName) {
            $agentData = $agent->getCacheByLoginName($agentLoginName);
            if (!$agentData) {
                return $response->withJson([
                    'result' => '此代理账号不存在',
                    'success' => 0,
                ]);
            }
        }

        $logger = $this->c->logger;
        global $app;
        $db = $app->getContainer()->database;
        try {
            $db->getConnection()->beginTransaction();
            $merchantId = Tools::getIdByHash($merchantId);
            $merchant = new Merchant;
            $merchantAccount = new MerchantAccount;

            $model = $merchant->where('merchantId', $merchantId)->first();
            if (empty($model)) {
                return $response->withJson([
                    'result' => '数据不存在',
                    'success' => 0,
                ]);
            }
            $actionBeforeData = $model->toJson();
            $model->shortName = $shortName;
            $model->fullName = $fullName;
            $model->status = $status;
            $model->save();

            //代理相关操作
            $relation = new AgentMerchantRelation();
            $agentRate = new AgentRate();
            $log = '';
            if ($agentData) {
                //先删除旧的代理关系
                $relationData = $relation->where('merchantId', $model->merchantId)->first();
                if($relationData){
                    $relationData->merchantId=$model->merchantId;
                    $relationData->delete();

                    //减去旧代理的下级人数
                    $agent = $agent->where('id', $relationData->agentId)->first();
                    if($agent->inferisorNum >0){
                        $agent->inferisorNum = $agent->inferisorNum - 1;
                        $agent->save();
                    }
                }


                //判断当前需要绑定的代理的费率信息是否与当前商户相匹配
                $agentRateData = $agentRate->getCacheByAgentId($agentData['id']);
                if (!$agentRateData) {
                    return $response->withJson([
                        'result' => '此商户对应的代理费率不存在，请先核实',
                        'success' => 0,
                    ]);
                }
                $merRate = new MerchantRate;
                $merRateData = $merRate->getCacheByMerchantId($model->merchantId);
                foreach ($merRateData as $v) {
                    $arrProduct = [];
                    $arrType = [];
                    foreach ($agentRateData as $rateKey => $agentRateDatum) {
                        foreach ($agentRateDatum as $keys => $items) {
                            if ($keys == 'productType') {
                                $arrProduct[] = $items;
                            }
                            if ($keys == 'payType') {
                                $arrType[] = $items;
                            }
                        }
                    }
                    if (!in_array($v['productType'], $arrProduct)) {
                        return $response->withJson([
                            'result' => '请先核实代理费率是否存在' . $v['productType'] . '产品类型',
                            'success' => 0,
                        ]);
                    }
                    if (!in_array($v['payType'], $arrType)) {
                        return $response->withJson([
                            'result' => '请先核实代理费率是否存在' . $v['payType'] . '支付方式',
                            'success' => 0,
                        ]);
                    }
                    foreach ($agentRateData as $key => $item) {
                        if ($item['productType'] == $v['productType'] && $item['payType'] == $v['payType'] && $item['bankCode'] == $v['bankCode']) {
                            if ($item['rateType'] != $v['rateType']) {
//                            var_dump('请选择相同的费率类型');
                                return $response->withJson([
                                    'result' => '请选择相同的费率类型',
                                    'success' => 0,
                                ]);
                            }

                            if ($item['rate'] > $v['rate'] || $item['maxServiceCharge'] > $v['maxServiceCharge'] || $item['minServiceCharge'] > $v['minServiceCharge']) {
//                            var_dump('代理费率不能高于商户费率'.$item['rate'].'    '.$v['rate']);
                                return $response->withJson([
                                    'result' => $item['productType'] . '通道商户费率不能低于代理费率',
                                    'success' => 0,
                                ]);
                            }

                        }

                    }
                }

                //添加新的代理关系
                $relation->agentId = $agentData['id'];
                $relation->merchantId = $model->merchantId;
                $relation->save();

                //添加新代理的下级人数
                $agent = $agent->where('id', $agentData['id'])->first();
                $agent->inferisorNum = $agent->inferisorNum + 1;
                $agent->save();

                $log = '代理绑定由' . $agentRateData['agentLoginName'] . '绑定到' . $agentLoginName;
            } else {
                $data = $relation->where('merchantId', $model->merchantId)->get()->first();
                $agentRateData = $agent->getCacheByAgentId($data->agentId);
                if ($data) {
                    $relationData = $relation->where('merchantId', $model->merchantId)->first();
                    $relationData->merchantId=$model->merchantId;
                    $relationData->delete();
//                    $relation->where('merchantId', $model->merchantId)->delete();

                    //减去旧代理的下级人数
                    $agent = $agent->where('id', $relationData->agentId)->first();
                    if( $agent->inferisorNum>0){
                        $agent->inferisorNum = $agent->inferisorNum - 1;
                        $agent->save();
                    }
                    $log = '代理绑定从' . $agentRateData['loginName'] . '解除';
                }
            }
            //操作者信息
            if ($log) {
                $user = new SystemAccount();
                $userData = $user->where('id', '=', $_SESSION['accountId'])->first();
                AgentLog::insert([
                    'action' => 'AGENT_RELATION_MERCH',
                    'actionBeforeData' => '',
                    'actionAfterData' => $model->merchantNo . $log,
                    'optId' => $_SESSION['accountId'],
                    'optName' => $userData->userName,
                    'status' => 'Success',
                    'desc' => '代理号和商户绑定',
                    'ipDesc' => Tools::getIpDesc(),
                    'ip' => Tools::getIp()
                ]);
            }

            $merchant->refreshCache(['merchantId' => $model->merchantId]);
            SystemAccountActionLog::insert([
                [
                    'action' => 'UPDATE_MERCHANT',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => $model->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
            ]);
            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            $logger->error('Exception:' . $e->getMessage());
            return $response->withJson([
                'result' => '修改失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);

    }

    public function resetset(Request $request, Response $response, $args)
    {
        $openPay = $request->getParam('openPay');
        $merchantNo = $request->getParam('merchantNo');
        $openSettlement = $request->getParam('openSettlement');
        $openAutoSettlement = $request->getParam('openAutoSettlement');
        $openAliSettlement = $request->getParam('openAliSettlement');
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
        $D0SettlementRate = $request->getParam('D0SettlementRate');
        $D0SettlementRate = $D0SettlementRate > 23 ? 0 : $D0SettlementRate;
        $settlementType = $request->getParam('settlementType');

        $model = new Merchant();
        $data = $model->where('merchantNo', $merchantNo)->first();
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            $actionBeforeData = $data->toJson();
            $data->openPay = $openPay;
            $data->openSettlement = $openSettlement;
            $data->openAutoSettlement = $openAutoSettlement;
            $data->openAliSettlement = $openAliSettlement;
            $data->oneSettlementMaxAmount = $oneSettlementMaxAmount;
            // $data->openEntrustSettlement = $openEntrustSettlement;
            // $data->openWorkdaySettlement = $openWorkdaySettlement;
            // $data->workdaySettlementType = $workdaySettlementType;
            // $data->workdaySettlementRate = $workdaySettlementRate;
            // $data->workdaySettlementMaxAmount = $workdaySettlementMaxAmount;
            // $data->openHolidaySettlement = $openHolidaySettlement;
            // $data->holidaySettlementType = $holidaySettlementType;
            // $data->holidaySettlementRate = $holidaySettlementRate;
            // $data->holidaySettlementMaxAmount = $holidaySettlementMaxAmount;
            $data->settlementTime = $settlementTime;
            $data->D0SettlementRate = $D0SettlementRate;
            $data->settlementType = $settlementType;
            $data->save();
            $model->refreshCache(['merchantId' => $data->merchantId]);
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_MERCHANT',
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

    public function getDetail(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $model = new Merchant();
        $data = $model->where('merchantNo', $merchantNo)->first();
        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        } else {
            $data = $data->toArray();
            $data['insTime'] = Tools::getJSDatetime($data['created_at']);
            unset($data['created_at'], $data['updated_at']);
            return $response->withJson([
                'result' => $data,
                'success' => 1,
            ]);
        }
    }

    public function search(Request $request, Response $response, $args)
    {

//        $model = new Merchant();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $shortName = $request->getParam('shortName');
        $fullName = $request->getParam('fullName');
        $platformNo = $request->getParam('platformNo');
        $status = $request->getParam('status');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $agentName = $request->getParam('agentName');
        $sort = empty($request->getParam('sort')) ?  'merchantId': $request->getParam('sort');
        $order = empty($request->getParam('order')) ? 'desc' : $request->getParam('order');
        $table = 'm.';
        if ($sort == 'settlementAmount')
            $table = 'ma.';

        $model = Merchant::from('merchant as m')
            ->leftJoin('merchant_amount as ma', 'm.merchantId', '=', 'ma.merchantId')
            ->leftJoin("agent_merchant_relation as ar", 'm.merchantId', '=', 'ar.merchantId')
            ->leftJoin("agent as a", 'a.id', '=', 'ar.agentId')
            ->select(['a.loginName', 'm.*', 'ma.*']);


        $merchantNo && $model = $model->where('m.merchantNo', $merchantNo);
        $shortName && $model = $model->where('m.shortName', $shortName);
        $fullName && $model = $model->where('m.fullName', $fullName);
        $platformNo && $model = $model->where('m.platformNo', $platformNo);
        $status && $model = $model->where('m.status', $status);
        $beginTime && $model = $model->where('m.created_at', ">=", $beginTime);
        $endTime && $model = $model->where('m.created_at', "<=", $endTime);
        $agentName && $model = $model->where('a.loginName', $agentName);

        $total = $model->count();
        $stat['currentAmount'] = $model->sum('ma.settlementAmount');
        $stat['totalAmount'] = MerchantAmount::sum('settlementAmount');

        $data = $model->orderBy($table . $sort, $order)->offset($offset)->limit($limit)->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {

            $nv = [
                // 'accountId' => $v->accountId,
                'fullName' => $v->fullName,
                'holidaySettlementMaxAmount' => $v->holidaySettlementMaxAmount,
                'holidaySettlementRate' => $v->holidaySettlementRate,
                'holidaySettlementType' => $v->holidaySettlementType,
                "insTime" => Tools::getJSDatetime($v->created_at),
                "merchantId" => Tools::getHashId($v->merchantId),
                "merchantNo" => $v->merchantNo,
                "oneSettlementMaxAmount" => $v->oneSettlementMaxAmount,
                "openEntrustSettlement" => $v->openEntrustSettlement,
                "openHolidaySettlement" => $v->openHolidaySettlement,
                "openPay" => $v->openPay,
                "openQuery" => $v->openSettlement,
                "openSettlement" => $v->openSettlement,
                "openWorkdaySettlement" => $v->openWorkdaySettlement,
                // "platformId" => $v->platformId,
                "platformNo" => $v->platformNo,
                "settlementTime" => $v->settlementTime,
                "shortName" => $v->shortName,
                "status" => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
                "workdaySettlementMaxAmount" => $v->workdaySettlementMaxAmount,
                "workdaySettlementRate" => $v->workdaySettlementRate,
                "workdaySettlementType" => $v->workdaySettlementType,
                "settlementAmount" => $v->settlementAmount,
                "loginName" => $v->loginName
            ];
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
            'stat' => $stat,
        ]);
    }

    public function user(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/merchant/user.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function googleAuthSecretKey(Request $request, Response $response, $args)
    {

        $userId = $request->getParam('userId');

        $validator = $this->c->validator->validate($request, [
            'userId' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }
        $userId = Tools::getIdByHash($userId);
        $merchantAccount = new MerchantAccount;
        $model = $merchantAccount->where('accountId', $userId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $model->toJson();
        $model->googleAuthSecretKey = '';
        $model->save();
        $merchantAccount->refreshCache(['accountId' => $userId]);
        SystemAccountActionLog::insert([
            [
                'action' => 'UPDATE_MERCHANT_ACCOUNT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ],
        ]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);

    }

    public function userUpdate(Request $request, Response $response, $args)
    {

        $loginName = $request->getParam('loginName');
        $userId = $request->getParam('UserId');
        $userName = $request->getParam('userName');
        $status = $request->getParam('status');
        $userLevel = $request->getParam('userLevel');

        $validator = $this->c->validator->validate($request, [
            'loginName' => Validator::noWhitespace()->notBlank(),
            'UserId' => Validator::noWhitespace()->notBlank(),
            'userName' => Validator::noWhitespace()->notBlank(),
            'status' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }
        $userId = Tools::getIdByHash($userId);
        $merchantAccount = new MerchantAccount;
        $model = $merchantAccount->where('accountId', $userId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $model->toJson();
        $oldLoginName = $model->loginName;
        if ($model->loginName != $loginName) {
            $model->loginName = $loginName;
            $new = $merchantAccount->where('loginName', $loginName)->first();
            if (!empty($new)) {
                return $response->withJson([
                    'result' => '用户名称已存在',
                    'success' => 0,
                ]);
            }
        }
        $model->loginFailNum = 0;
        $model->userName = $userName;
        $model->status = $status;
        $model->userLevel = $userLevel;
        $model->save();
        $merchantAccount->delCacheByLoginName($oldLoginName);
        $merchantAccount->refreshCache(['accountId' => $userId]);
        SystemAccountActionLog::insert([
            [
                'action' => 'UPDATE_MERCHANT_ACCOUNT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ],
        ]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);

    }

    public function resetloginpwd(Request $request, Response $response, $args)
    {

        $userId = $request->getParam('userId');
        $validator = $this->c->validator->validate($request, [
            'userId' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }
        $userId = Tools::getIdByHash($userId);
        $merchantAccount = new MerchantAccount;
        $model = $merchantAccount->where('accountId', $userId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }

        $systemLog = new SystemCheckLog();
        $auditInfo = $systemLog->where('status', '0')->where("type", "登录密码修改")->where('relevance', $model->merchantNo)->first();
        if ($auditInfo) {
            return $response->withJson([
                'result' => '还有待审核数据',
                'success' => 0,
            ]);
        }

//        $actionBeforeData = $model->toJson();
        $loginPwd = Tools::getRandStr('0123456789', 6);
        $content = ['accountId' => $model->accountId, "password" => $loginPwd];
        SystemCheckLog::insert([
            [
                'admin_id' => 0,
                'commiter_id' => $_SESSION['accountId'],
                'status' => '0',
                'content' => json_encode($content),
                'relevance' => $model->merchantNo,
                'desc' => '',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'type' => '登录密码修改',
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time()),
            ],
        ]);

//        $model->loginPwd = Tools::getHashPassword($loginPwd);
//        $model->loginFailNum = 0;
//        $model->save();
//        $merchantAccount->refreshCache(['accountId' => $userId]);
//        SystemAccountActionLog::insert([
//            [
//                'action' => 'UPDATE_MERCHANT_ACCOUNT_PASSWORD',
//                'actionBeforeData' => $actionBeforeData,
//                'actionAfterData' => $model->toJson(),
//                'status' => 'Success',
//                'accountId' => $_SESSION['accountId'],
//                'ip' => Tools::getIp(),
//                'ipDesc' => Tools::getIpDesc(),
//            ],
//        ]);

        return $response->withJson([
            'result' => [
                'newPwd' => $loginPwd,
            ],
            'success' => 1,
        ]);

    }

    public function audit(Request $request, Response $response, $args)
    {

        return $this->c->view->render($response, 'gm/merchant/audit.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function auditpassword(Request $request, Response $response, $args)
    {

        $model = new AuditPassword();
        $merchantData = [];
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $loginName = $request->getParam('loginName');
        $status = $request->getParam('status');

        isset($status) && $model = $model->where('status', $status);
        $loginName && $model = $model->where('loginName', $loginName);
        $total = $model->count();
        $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get();
        // var_dump($data);exit;
        $rows = [];
        foreach ($data as $k => $v) {
            $row = [
                'id' => $v->id,
                'loginName' => $v->loginName,
                'accountId' => $v->accountId,
                'password' => $v->password,
                'pwdType' => $v->pwdType,
                'status' => $v->status,
                'auditer' => $v->auditer,
                'created_at' => Tools::getJSDatetime($v->created_at),
                'updated_at' => Tools::getJSDatetime($v->updated_at),
            ];
            $rows[] = $row;
        }
        // var_dump($rows);exit;

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);

    }

    public function resetsecurepwd(Request $request, Response $response, $args)
    {
        $userId = $request->getParam('userId');
        $validator = $this->c->validator->validate($request, [
            'userId' => Validator::noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }
        $userId = Tools::getIdByHash($userId);
        $merchantAccount = new MerchantAccount;
        $model = $merchantAccount->where('accountId', $userId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }

        $systemLog = new SystemCheckLog();
        $auditInfo = $systemLog->where('status', '0')->where("type", "支付密码修改")->where('relevance', $model->merchantNo)->first();
        if ($auditInfo) {
            return $response->withJson([
                'result' => '还有待审核数据',
                'success' => 0,
            ]);
        }

//        $actionBeforeData = $model->toJson();
        $securePwd = Tools::getRandStr('0123456789', 6);
        // $model->securePwd = Tools::getHashPassword($securePwd);
        // $model->save();
        // $merchantAccount->refreshCache(['accountId' => $userId]);
        // SystemAccountActionLog::insert([
        //     [
        //         'action' => 'UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD',
        //         'actionBeforeData' => $actionBeforeData,
        //         'actionAfterData' => $model->toJson(),
        //         'status' => 'Success',
        //         'accountId' => $_SESSION['accountId'],
        //         'ip' => Tools::getIp(),
        //         'ipDesc' => Tools::getIpDesc(),
        //     ],
        // ]);


//        $auditPassword->accountId = $model->accountId;
        $content = ['accountId' => $model->accountId, "password" => $securePwd];
        SystemCheckLog::insert([
            [
                'admin_id' => 0,
                'commiter_id' => $_SESSION['accountId'],
                'status' => '0',
                'content' => json_encode($content),
                'relevance' => $model->merchantNo,
                'desc' => '',
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'type' => '支付密码修改',
                'created_at' => date('Y-m-d H:i:s', time()),
                'updated_at' => date('Y-m-d H:i:s', time()),
            ],
        ]);

//        $model->securePwd = Tools::getHashPassword($securePwd);
//        $model->save();
//        $merchantAccount->refreshCache(['accountId' => $userId]);
//        SystemAccountActionLog::insert([
//            [
//                'action' => 'UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD',
//                'actionBeforeData' => $actionBeforeData,
//                'actionAfterData' => $model->toJson(),
//                'status' => 'Success',
//                'accountId' => $_SESSION['accountId'],
//                'ip' => Tools::getIp(),
//                'ipDesc' => Tools::getIpDesc(),
//            ],
//        ]);
        return $response->withJson([
            'result' => [
                'newPwd' => $securePwd,
            ],
            'success' => 1,
        ]);

    }


    public function resetpassword(Request $request, Response $response, $args)
    {
        //审核密码验证
        global $app;
        $redis = $app->getContainer()->redis;
        //审核密码验证
        $tmp = $redis->get("checkPwd:check:count") ?? 0;
        $checkPwd = Tools::getHashPassword($request->getParam('checkPwd'));
        $checkPwd2 = SystemAccount::where('id',$_SESSION['accountId'])->value('check_pwd');
        if($checkPwd2 == 'error'){
            return $response->withJson([
                'success' => 0,
                'result' => "审核密码错误超过指定次数，已封审核权限，联系技术",
            ]);
        }
        if( $checkPwd2 != $checkPwd){
            $redis->setex("checkPwd:check:count", 7200, ++$tmp);
            if($tmp > 5){
                SystemAccount::where('id',$_SESSION['accountId'])->update(['check_pwd'=>'error']);
            }
            return $response->withJson([
                'success' => 0,
                'result' => "审核密码不正确",
            ]);
        }
        $redis->setex("checkPwd:check:count", 7200, 0);

        $id = $request->getParam('id');
        $passwordtype = $request->getParam('passwordtype');
        $password = $request->getParam('newpassword');
        $SystemCheckLog = new SystemCheckLog();
        $data = $SystemCheckLog->where("id", $id)->where("type", $passwordtype)->first();
        if (!$data) {
            return $response->withJson([
                'success' => 0,
                'result' => "无些数据",
            ]);
        }

        $encryptPwd = Tools::getHashPassword($password);
        try {
            $db = $this->c->database;
            $db->getConnection()->beginTransaction();
            $content = json_decode($data->content, true);
            $accountId = $content['accountId'];
            $content['password'] = $encryptPwd;
            $data->content = json_encode($content, JSON_UNESCAPED_UNICODE);
            $data->status = '1';
            $data->admin_id = $_SESSION['accountId'];
            $data->check_ip = Tools::getIp();
            $data->check_time = date("Y-m-d H:i:s", time());
            $data->update();

            $merchantAccount = new MerchantAccount;

            $merchantAcc = $merchantAccount->where('accountId', $accountId)->first();
            $actionBeforeData = $merchantAcc->toJson();
            if ($passwordtype == "支付密码修改") {
                $type = 'UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD';
                $merchantAcc->securePwd = $encryptPwd;
            }
            if ($passwordtype == "登录密码修改") {
                $type = "UPDATE_MERCHANT_ACCOUNT_PASSWORD";
                $merchantAcc->loginPwd = $encryptPwd;
            }

            $merchantAcc->loginFailNum = 0;
            $merchantAcc->save();
            $db->getConnection()->commit();

            $merchantAccount->refreshCache(['accountId' => $accountId]);
            $redis = $this->c->redis;
            $redis->del('dadong_merchant_login_userid_' . $content['accountId']);
            SystemAccountActionLog::insert([
                [
                    'action' => $type,
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => $merchantAcc->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
            ]);
            return $response->withJson([
                'result' => [
                    'newPwd' => $password,
                ],
                'success' => 1,
            ]);
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            return $response->withJson([
                'result' => '审核失败',
                'success' => 0,
            ]);
        }

    }

    public function disagreepassword(Request $request, Response $response, $args)
    {
        $id = $request->getParam('id');
        $passwordtype = $request->getParam('passwordtype');
        $password = $request->getParam('newpassword');
        $model = new SystemCheckLog();
        $data = $model->where("id", $id)->where("type", $passwordtype)->first();
        if (!$data) {
            return $response->withJson([
                'success' => 0,
                'result' => "无此数据",
            ]);
        }

        $data->status = '2';
        $data->admin_id = $_SESSION['accountId'];
        $data->check_ip = Tools::getIp();
        $data->check_time = date("Y-m-d H:i:s", time());
        $data->update();

        $type = $passwordtype == "登录密码修改" ? "UPDATE_MERCHANT_ACCOUNT_PASSWORD" : "UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD";
        try {
            $content = json_decode($data->content, true);
            $accountId = $content['accountId'];
            $merchantAccount = new MerchantAccount;
            $merchantAcc = $merchantAccount->where('accountId', $accountId)->first();
            $actionBeforeData = $merchantAcc->toJson();
            SystemAccountActionLog::insert([
                [
                    'action' => $type,
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => $actionBeforeData,
                    'status' => 'Fail',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ],
            ]);
            return $response->withJson([
                'result' => [
                    'newPwd' => $password,
                ],
                'success' => 1,
            ]);
        } catch (\Exception $e) {
            return $response->withJson([
                'result' => '审核失败',
                'success' => 0,
            ]);
        }

    }

    public function userSearch(Request $request, Response $response, $args)
    {
        $model = new MerchantAccount();
        $merchantData = [];
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $loginName = $request->getParam('loginName');
        $platformNo = $request->getParam('platformNo');
        $status = $request->getParam('status');
        $level = $request->getParam('level');

        $merchantNo && $model = $model->where('merchantNo', $merchantNo);
        $platformNo && $model = $model->where('platformNo', $platformNo);
        $level && $model = $model->where('userLevel', $level);
        $status && $model = $model->where('status', $status);
        $loginName && $model = $model->where('loginName', $loginName);
        $total = $model->count();
        $data = $model->orderBy('accountId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];

        foreach ($data ?? [] as $k => $v) {
            $nv = [
                'insTime' => Tools::getJSDatetime($v->created_at),
                'latestLoginTime' => Tools::getJSDatetime($v->latestLoginTime),
                'loginFailNum' => $v->loginFailNum,
                'loginName' => $v->loginName,
                'loginPwdAlterTime' => Tools::getJSDatetime($v->loginPwdAlterTime),
                "merchantNo" => $v->merchantNo,
                "platformNo" => $v->platformNo,
                "merchantNo" => $v->merchantNo,
                "googleAuthSecretKey" => $v->googleAuthSecretKey,
                "platformType" => $v->platformType,
                "platformTypeDesc" => $code['platformType'][$v->status] ?? '',
                "status" => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
                "userId" => Tools::getHashId($v->accountId),
                "userName" => $v->userName,
                "userLevel" => $v->userLevel,
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

    public function platform(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/merchant/platform.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function platformSearch(Request $request, Response $response, $args)
    {
        $model = new Merchant();
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $platformNo = $request->getParam('platformNo');
        $description = $request->getParam('description');
        $type = $request->getParam('type');
        $status = $request->getParam('status');

        $type && $model = $model->where('platformType', $type);
        $description && $model = $model->where('fullName', $description);
        $platformNo && $model = $model->where('platformNo', $platformNo);
        $status && $model = $model->where('status', $status);

        $total = $model->count();
        $data = $model->orderBy('merchantId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                'backNoticeMaxNum' => 5,
                'description' => $v->fullName,
                'domains' => explode(',', $v->domain),
                'icp' => '',
                "insTime" => Tools::getJSDatetime($v->created_at),
                "openBackNotice" => $v->openBackNotice,
                "openRepayNotice" => $v->openRepayNotice,
                "openCheckAccount" => $v->openCheckAccount,
                "openManualSettlement"=>$v->openManualSettlement,
                "openCheckDomain" => $v->openCheckDomain,
                "platformId" => $v->platformId,
                "platformNo" => $v->platformNo,
                "signKey" => $v->signKey,
                "status" => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
                "type" => $v->platformType,
                "typeDesc" => $code['platformType'][$v->platformType] ?? '',
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

    public function platformDetail(Request $request, Response $response, $args)
    {
        $model = new Merchant();
        $code = $this->c->code;
        $platformNo = $request->getParam('platformNo');
        $data = Merchant::where('platformNo', $platformNo)->first();

        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }

        return $response->withJson([
            'result' => [
                "backNoticeMaxNum" => $data->backNoticeMaxNum,
                "description" => $data->description,
                "domains" => explode(',', $data->domain),
                "icp" => "",
                "insTime" => "",
                "openBackNotice" => $data->openBackNotice,
                "openRepayNotice" => $data->openRepayNotice,
                "openCheckAccount" => $data->openCheckAccount,
                'openManualSettlement'=>$data->openManualSettlement,
                "openCheckDomain" => $data->openCheckDomain,
                "openFrontNotice" => $data->openFrontNotice,
                // "platformId" => $data->platformId,
                "platformNo" => $data->platformNo,
                "signKey" => $data->signKey,
                "status" => $data->status,
                "statusDesc" => $code['commonStatus'][$data->status] ?? "",
                "type" => $data->type ?? 'Normal',
                "typeDesc" => $code['platformType'][$data->type] ?? "一般",
                'ipWhite' => $data->ipWhite,
                'loginIpWhite' => $data->loginIpWhite,
            ],
            'success' => 1,
        ]);
    }

    public function platformUpdate(Request $request, Response $response, $args)
    {
        $domains = $request->getParam('domains', '');
        $platformNo = $request->getParam('platformNo');
        $description = $request->getParam('description');
        $status = $request->getParam('status');
        $openCheckAccount = $request->getParam('openCheckAccount');
        $openCheckDomain = $request->getParam('openCheckDomain');
        $openFrontNotice = $request->getParam('openFrontNotice');
        $openBackNotice = $request->getParam('openBackNotice');
        $openRepayNotice = $request->getParam('openRepayNotice');
        $ipWhite = $request->getParam('ipWhite', '');
        $loginIpWhite = $request->getParam('loginIpWhite', '');
        $openManualSettlement=$request->getParam('openManualSettlement');

        $validator = $this->c->validator->validate($request, [
            'domains' => Validator::noWhitespace(),
            'platformNo' => Validator::noWhitespace()->notBlank(),
            'description' => Validator::noWhitespace()->notBlank(),
            'status' => Validator::noWhitespace()->notBlank(),
            'openCheckAccount' => Validator::in(["0", "1"]),
            'openCheckDomain' => Validator::in(["0", "1"]),
            'openFrontNotice' => Validator::in(["0", "1"]),
            'openBackNotice' => Validator::in(["0", "1"]),
            'openRepayNotice' => Validator::in(["0", "1"]),
            'openManualSettlement' => Validator::in(["0", "1"]),
        ]);

        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        $ipWhite = trim($ipWhite);
        $arrIpWhite = array();
        if ($ipWhite != '') {
            $arr = explode(",", $ipWhite);
            foreach ($arr as $ip) {
                $ip = trim($ip);
                $validator = $this->c->validator->validate(array('ip' => $ip), [
                    'ip' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
                ]);

                if (!$validator->isValid()) {
                    return $response->withJson([
                        'result' => '回调ip白名单验证不通过',
                        'success' => 0,
                    ]);
                }

                $arrIpWhite[] = $ip;
            }
        }

        $ipWhite = implode(",", $arrIpWhite);

        $loginIpWhite = trim($loginIpWhite);
        $arrLoginIpWhite = array();
        if ($loginIpWhite != '') {
            $arr = explode(",", $loginIpWhite);
            foreach ($arr as $ip) {
                $ip = trim($ip);
                $validator = $this->c->validator->validate(array('ip' => $ip), [
                    'ip' => Validator::ip(FILTER_FLAG_NO_PRIV_RANGE)->noWhitespace()->notBlank(),
                ]);

                if (!$validator->isValid()) {
                    return $response->withJson([
                        'result' => '验证不通过',
                        'success' => 0,
                    ]);
                }

                $arrLoginIpWhite[] = $ip;
            }
        }

        $LoginIpWhite = implode(",", $arrLoginIpWhite);


        $merchant = new Merchant;
        $model = $merchant->where('platformNo', $platformNo)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $model->toJson();
        $model->description = $description;
        $model->openCheckAccount = $openCheckAccount;
        $model->domain = $domains;
        $model->openCheckDomain = $openCheckDomain;
        $model->openFrontNotice = $openFrontNotice;
        $model->openBackNotice = $openBackNotice;
        $model->openRepayNotice = $openRepayNotice;
        $model->status = $status;
        $model->ipWhite = $ipWhite;
        $model->loginIpWhite = $LoginIpWhite;
        $model->openManualSettlement = $openManualSettlement;
        $model->save();
        $merchant->refreshCache(['merchantId' => $model->merchantId]);
        SystemAccountActionLog::insert([
            [
                'action' => 'UPDATE_MERCHANT',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $model->toJson(),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ],
        ]);
        return $response->withJson([
            'result' => '修改成功',
            'success' => 1,
        ]);

    }

    public function getsignkey(Request $request, Response $response, $args)
    {
        $model = new Merchant();
        $code = $this->c->code;
        $platformNo = $request->getParam('platformNo');
        $data = Merchant::where('platformNo', $platformNo)->first();

        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }

        return $response->withJson([
            'result' => [
                "signKey" => Tools::decrypt($data->signKey),
            ],
            'success' => 1,
        ]);
    }

    public function resetsignkey(Request $request, Response $response, $args)
    {
        $model = new Merchant();
        $code = $this->c->code;
        $platformNo = $request->getParam('platformNo');
        $data = Merchant::where('platformNo', $platformNo)->first();

        if (empty($data)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $data->signKey;
        $data->signKey = md5(time());
        $data->save();
        $model->refreshCache(['merchantId' => $data->merchantId]);
        SystemAccountActionLog::insert([
            [
                'action' => 'UPDATE_MERCHANT_SIGNKEY',
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $data->signKey,
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ],
        ]);
        return $response->withJson([
            'result' => [
                'signKey' => $data->signKey,
            ],
            'success' => 1,
        ]);
    }

    public function rate(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/merchant/rate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/merchantRateTmpl.csv',
        ]);
    }

    public function rateSearch(Request $request, Response $response, $args)
    {
        $model = new MerchantRate();
        $merchant = new Merchant();
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformNo = $request->getParam('platformNo');
        $merchantFlag = $request->getParam('merchantFlag');
        $productType = $request->getParam('productType');
        $payType = $request->getParam('payType');
        $rateType = $request->getParam('rateType');
        $status = $request->getParam('status');

        $merchantId = 0;
        if ($merchantFlag) {
            $m = $merchant->where('shortName', $merchantFlag)
                ->orWhere('fullName', $merchantFlag)->first();
            $merchantId = $m ? $m->merchantId : 0;
        }
        $model = $model->leftJoin('agent_merchant_relation as amr', 'amr.merchantId', '=', 'merchant_rate.merchantId')
            ->leftJoin('agent', 'agent.id', '=', 'amr.agentId')
            ->select(['merchant_rate.*', 'agent.loginName', 'amr.agentId']);

        $merchantFlag && $model = $model->where('merchantId', $merchantId);
        $merchantNo && $model = $model->where('merchantNo', $merchantNo);
        $productType && $model = $model->where('productType', $productType);
        $payType && $model = $model->where('payType', $payType);
        $rateType && $model = $model->where('rateType', $rateType);
        $platformNo && $model = $model->where('platformNo', $platformNo);
        $status && $model = $model->where('status', $status);

        $total = $model->count();
        $data = $model->orderBy('rateId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        $merchantData = [];
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);
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
                "merchantNo" => $v->merchantNo,
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
                "shortName" => $merchantData[$v->merchantId]['shortName'],
                'status' => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
                "loginName" => $v->loginName,
                "agentId" => $v->agentId,
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

    public function rateImport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
//        $loginName = $request->getParam('loginName');
        $merchant = new Merchant;
        $model = new MerchantRate;
        $relation = new AgentMerchantRelation();
        $agentRate = new AgentRate();
        $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
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

        //根据merchantId查找对应的代理id，看看
        $agentRateData = [];
        $relaData = $relation->where('merchantId', $merchantData['merchantId'])->first();
        if ($relaData) {
            $agentRateData = $agentRate->getCacheByAgentId($relaData->agentId);
            if (!$agentRateData) {
                return $response->withJson([
                    'result' => '此商户对应的代理费率不存在，请先核实',
                    'success' => 0,
                ]);
            }
        }

        // $rateData = $model->getCacheByMerchantId($merchantData['merchantId']);
        // if (!empty($rateData)) {
        //     return $response->withJson([
        //         'result' => '商户配置已存在',
        //         'success' => 0,
        //     ]);
        // }
        $actionBeforeData = $model->where('merchantId', $merchantData['merchantId'])->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();

        }
        $csv = new \ParseCsv\Csv();
        $csv->fields = ['merchantNo', 'productType', 'payType',
            'bankCode', 'cardType','minAmount','maxAmount', 'rateType', 'rate', 'fixed', 'minServiceCharge',
            'maxServiceCharge', 'beginTime', 'endTime', 'status'];

        $csv->auto($file['file']->file);
        $data = $csv->data;
        foreach ($data ?? [] as $k => $v) {
            foreach ($v as $a => $b) {
                $v[$a] = str_replace(["'", ' '], '', $b);
            }
            if (empty($v['merchantNo'])) {
                unset($data[$k]);
                continue;
            }
            $v['merchantId'] = $merchantData['merchantId'];
            $v['merchantNo'] = $merchantData['merchantNo'];
            $v['beginTime'] = $v['beginTime'] ? $v['beginTime'] : null;
            $v['endTime'] = $v['endTime'] ? $v['endTime'] : null;
            $v['minServiceCharge'] = (float)$v['minServiceCharge'];
            $v['maxServiceCharge'] = (float)$v['maxServiceCharge'];
            $v['cardType'] = empty($v['cardType']) ? 'DEBIT' : $v['cardType'];


            if ($v['maxServiceCharge'] > 0 && $v['minServiceCharge'] > 0 && $v['maxServiceCharge'] < $v['minServiceCharge']) {
                return $response->withJson([
                    'result' => '最大费率不能少于最小费率',
                    'success' => 0,
                ]);
            }

            //如果代理费率不为空
            if ($agentRateData) {
                $arrProduct = [];
                $arrType = [];
                foreach ($agentRateData as $rateKey => $agentRateDatum) {
                    foreach ($agentRateDatum as $keys => $items) {
                        if ($keys == 'productType') {
                            $arrProduct[] = $items;
                        }
                        if ($keys == 'payType') {
                            $arrType[] = $items;
                        }
                    }
                }
                if (!in_array($v['productType'], $arrProduct)) {
                    return $response->withJson([
                        'result' => '请先核实代理是否存在' . $v['productType'] . '产品类型',
                        'success' => 0,
                    ]);
                }
                if (!in_array($v['payType'], $arrType)) {
                    return $response->withJson([
                        'result' => '请先核实代理是否存在' . $v['payType'] . '支付方式',
                        'success' => 0,
                    ]);
                }
                foreach ($agentRateData as $key => $item) {
                    if ($item['productType'] == $v['productType'] && $item['payType'] == $v['payType'] && $item['bankCode'] == $v['bankCode']) {
                        if ($item['rateType'] != $v['rateType']) {
//                            var_dump('请选择相同的费率类型');
                            return $response->withJson([
                                'result' => '请选择相同的费率类型',
                                'success' => 0,
                            ]);
                        }

                        if ($item['rate'] > $v['rate'] || $item['maxServiceCharge'] > $v['maxServiceCharge'] || $item['minServiceCharge'] > $v['minServiceCharge']) {
//                            var_dump('代理费率不能高于商户费率'.$item['rate'].'    '.$v['rate']);
                            return $response->withJson([
                                'result' => $item['productType'] . '通道商户费率不能低于代理费率',
                                'success' => 0,
                            ]);
                        }

                    }

                }
            }

            $data[$k] = $v;
        }
        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model->where('merchantId', $merchantData['merchantId'])->delete();
                $model->insert($data);

                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_MERCHANT_RATE',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByMerchantId($merchantData['merchantId']), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);
                $db->getConnection()->commit();
                $model->refreshCache(['merchantId' => $merchantData['merchantId']]);
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

    public function rateExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $order = $request->getParam('order');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $merchant = new Merchant;
        $model = new MerchantRate;
        $merchantData = [];
        $model = $model->where('merchantNo', $merchantNo)->leftJoin('agent_merchant_relation as amr', 'amr.merchantId', '=', 'merchant_rate.merchantId')->leftJoin('agent', 'amr.agentId', '=', 'agent.id')->select(['merchant_rate.*', 'agent.loginName']);
        $total = $model->count();
        // $data = $model->offset($offset)->limit($limit)->get();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [
                'bankCode' => $v->bankCode,
                'bankCodeDesc' => $code['bankCode'][$v->bankCode] ?? '',
                'beginTime' => Tools::getJSDatetime($v->beginTime),
                'cardType' => $v->cardType ?? 'DEBIT',
                'cardTypeDesc' => $code['payType'][$v->payType] ?? '',
                "channel" => $v->channel,
                "channelDesc" => $code['channel'][$v->channel]['name'] ?? '',
                "endTime" => Tools::getJSDatetime($v->endTime),
                "maxServiceCharge" => $v->maxServiceCharge,
                "merchantNo" => $v->merchantNo,
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
                "shortName" => $merchantData[$v->merchantId]['shortName'],
                'status' => $v->status,
                "statusDesc" => $code['commonStatus'][$v->status] ?? '',
                'loginName' => $v->loginName,
                'minAmount' => $v->minAmount,
                'maxAmount' => $v->maxAmount,
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
        return $this->c->view->render($response, 'gm/merchant/paychannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/merchantPayTmpl.csv',
        ]);
    }

    public function paychannelSearch(Request $request, Response $response, $args)
    {
        $model = new MerchantChannel();
        $merchant = new Merchant;
        $merchantData = [];
        $code = $this->c->code;
        $limit = (int)$request->getParam('limit', 20);
        $offset = (int)$request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $channel = $request->getParam('channel');
        $shortName = $request->getParam('shortName');

        /*  $merchantNo && $model = $model->where('merchant_channel.merchantNo', $merchantNo);
         // $shortName && $model->where('shortName', $shortName);
         if (!empty($shortName)) {
             $data = $merchant->where('shortName', $shortName)->first();
             $data && $model = $model->where('merchant_channel.merchantId', $data->merchantId);
         }
         $channel && $model = $model->where('merchant_channel.channel', $channel);
         $channelMerchantNo && $model = $model->where('merchant_channel.channelMerchantNo', $channelMerchantNo);

         $total = $model::all()->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])->count();
         $data = MerchantChannel::selectRaw('merchant_channel.channel,merchant_channel.channelMerchantId,merchant_channel.channelMerchantNo,
         merchant_channel.merchantId,merchant_channel.merchantNo,GROUP_CONCAT(merchant_channel.payType) as payTypes,amount_pay.amount')
             ->leftjoin('amount_pay', function ($join) {
                 $join->on('amount_pay.channelMerchantId', '=', 'merchant_channel.channelMerchantId')
                     ->on('amount_pay.merchantId', '=', 'merchant_channel.merchantId');
                 // ->on('amount_pay.accountDate', '=', date('Ymd'));
             })
             ->where('amount_pay.accountDate', date('Ymd'))
             ->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])
             ->offset($offset)
             ->limit($limit)
             ->get();

         $data = $model->selectRaw('*,GROUP_CONCAT(merchant_channel.payType) as payTypes,(select amount from amount_pay
         where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
         and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = "' . date('Ymd') . '"
         ) as amount')
             ->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])
             ->offset($offset)
             ->limit($limit)
             ->get(); */
        $wheres = [];
        $value = [];
        $where[] = '1=1';
        $merchantNo && $where[] = "merchant_channel.merchantNo=?";
        $merchantNo && $value[] = $merchantNo;

        $shortName && $where[] = "merchant_channel.$shortName=?";
        $shortName && $value[] = $shortName;

        $channelMerchantNo && $where[] = "merchant_channel.channelMerchantNo=?";
        $channelMerchantNo && $value[] = $channelMerchantNo;

        $channel && $where[] = "merchant_channel.channel=?";
        $channel && $value[] = $channel;

        if (!empty($shortName)) {
            $data = $merchant->where('shortName', $shortName)->first();
            $shortName && $where[] = "merchant_channel.merchantId=?";
            $shortName && $value[] = $data->merchantId;
        }

        $whereStr = implode(' and ', $where);
        $total = \Illuminate\Database\Capsule\Manager::select("select count(*) from (select *,
        (select SUM(amount) from amount_pay
        where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
        and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = '" . date('Ymd') . "'
        ) as amount
        from merchant_channel
        WHERE {$whereStr}
        GROUP BY merchant_channel.merchantId, merchant_channel.channelMerchantId) a", $value);

        $value[] = $limit;
        $value[] = $offset;
        $data = \Illuminate\Database\Capsule\Manager::select("select * from (select *,
        (select SUM(amount) from amount_pay
        where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
        and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = '" . date('Ymd') . "'
        ) as amount,GROUP_CONCAT(merchant_channel.payType) as payTypes
        from merchant_channel
        WHERE {$whereStr}
        GROUP BY merchant_channel.merchantId, merchant_channel.channelMerchantId) a limit ? offset ?", $value);

        $rows = [];
        $total = current(current($total));
        // exit;
        foreach ($data ?? [] as $k => $v) {
            // $total = $v->count;
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);

            $payTypes = explode(',', $v->payTypes);
            $payTypeDescs = [];
            foreach ($payTypes ?? [] as $payType) {
                $payTypeDescs[] = $code['payType'][$payType];
            }
            $nv = [
                // 'count' => $v->count,
                'channel' => $v->channel,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                'channelMerchantId' => Tools::getHashId($v->channelMerchantId),
                // 'channelMerchantId' => $v->channelMerchantId,
                'channelMerchantNo' => $v->channelMerchantNo,
                'dayAmountCount' => $v->amount,
                "merchantNo" => $v->merchantNo,
                "setId" => Tools::getHashId($v->setId),
                "payTypeDescs" => $payTypeDescs,
                "payTypes" => $payTypes,
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

    public function paychannelImport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $merchant = new Merchant;
        $model = new MerchantChannel;
        $channelMerchant = new ChannelMerchant;

        $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $channelMerchantData = [];
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

        $actionBeforeData = $model->where('merchantId', $merchantData['merchantId'])->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();

        }

        $csv = new \ParseCsv\Csv();
        $csv->fields = ['merchantNo', 'channel', 'channelMerchantNo',
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
            if (empty($v['merchantNo'])) {
                unset($data[$k]);
                continue;
            }
            $channelMerchantData[$v['channelMerchantNo']] = isset($channelMerchantData[$v['channelMerchantNo']]) ? $channelMerchantData[$v['channelMerchantNo']]
                : $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
            $v['merchantId'] = $merchantData['merchantId'];
            $v['merchantNo'] = $merchantData['merchantNo'];
            $v['channelMerchantId'] = isset($channelMerchantData[$v['channelMerchantNo']]) &&
            isset($channelMerchantData[$v['channelMerchantNo']]['channelMerchantId']) &&
            $channelMerchantData[$v['channelMerchantNo']]['channel'] == $v['channel'] ?
                $channelMerchantData[$v['channelMerchantNo']]['channelMerchantId'] : 0;
            $v['channelMerchantId'] = intval($v['channelMerchantId']);

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
                $model->where('merchantId', $merchantData['merchantId'])->delete();
                $model->insert($data);

                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_MERCHANT_CHANNEL_PAY',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByMerchantId($merchantData['merchantId']), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);
                $model->refreshCache(['merchantId' => $merchantData['merchantId']]);
                (new Amount)->init($merchantData['merchantId'], $merchantData['merchantNo']);
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

    public function paychannelExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $order = $request->getParam('order');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $merchant = new Merchant;
        $model = new MerchantChannel;
        $merchantData = [];
        $model = $model->where('merchantNo', $merchantNo);
        $total = $model->count();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);
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

    public function rechargechannel(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/merchant/rechargechannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/merchantRechargeTmpl.csv',
        ]);
    }

    public function rechargechannelSearch(Request $request, Response $response, $args)
    {
        $model = new MerchantChannel();
        $merchant = new Merchant;
        $merchantData = [];
        $code = $this->c->code;
        $limit = (int)$request->getParam('limit', 20);
        $offset = (int)$request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $channel = $request->getParam('channel');
        $shortName = $request->getParam('shortName');

        /*  $merchantNo && $model = $model->where('merchant_channel.merchantNo', $merchantNo);
         // $shortName && $model->where('shortName', $shortName);
         if (!empty($shortName)) {
             $data = $merchant->where('shortName', $shortName)->first();
             $data && $model = $model->where('merchant_channel.merchantId', $data->merchantId);
         }
         $channel && $model = $model->where('merchant_channel.channel', $channel);
         $channelMerchantNo && $model = $model->where('merchant_channel.channelMerchantNo', $channelMerchantNo);

         $total = $model::all()->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])->count();
         $data = MerchantChannel::selectRaw('merchant_channel.channel,merchant_channel.channelMerchantId,merchant_channel.channelMerchantNo,
         merchant_channel.merchantId,merchant_channel.merchantNo,GROUP_CONCAT(merchant_channel.payType) as payTypes,amount_pay.amount')
             ->leftjoin('amount_pay', function ($join) {
                 $join->on('amount_pay.channelMerchantId', '=', 'merchant_channel.channelMerchantId')
                     ->on('amount_pay.merchantId', '=', 'merchant_channel.merchantId');
                 // ->on('amount_pay.accountDate', '=', date('Ymd'));
             })
             ->where('amount_pay.accountDate', date('Ymd'))
             ->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])
             ->offset($offset)
             ->limit($limit)
             ->get();

         $data = $model->selectRaw('*,GROUP_CONCAT(merchant_channel.payType) as payTypes,(select amount from amount_pay
         where amount_pay.channelMerchantId = merchant_channel.channelMerchantId
         and amount_pay.merchantId = merchant_channel.merchantId and amount_pay.accountDate = "' . date('Ymd') . '"
         ) as amount')
             ->groupBy(['merchant_channel.merchantId', 'merchant_channel.channelMerchantId'])
             ->offset($offset)
             ->limit($limit)
             ->get(); */
        $wheres = [];
        $value = [];
        $where[] = '1=1';
        $merchantNo && $where[] = "merchant_channel_recharge.merchantNo=?";
        $merchantNo && $value[] = $merchantNo;

        $shortName && $where[] = "merchant_channel_recharge.$shortName=?";
        $shortName && $value[] = $shortName;

        $channelMerchantNo && $where[] = "merchant_channel_recharge.channelMerchantNo=?";
        $channelMerchantNo && $value[] = $channelMerchantNo;

        $channel && $where[] = "merchant_channel_recharge.channel=?";
        $channel && $value[] = $channel;

        if (!empty($shortName)) {
            $data = $merchant->where('shortName', $shortName)->first();
            $shortName && $where[] = "merchant_channel_recharge.merchantId=?";
            $shortName && $value[] = $data->merchantId;
        }

        $whereStr = implode(' and ', $where);
        $total = \Illuminate\Database\Capsule\Manager::select("select count(*) from merchant_channel_recharge WHERE {$whereStr}
        GROUP BY merchant_channel_recharge.merchantId, merchant_channel_recharge.channelMerchantId", $value);

        $value[] = $limit;
        $value[] = $offset;
        $data = \Illuminate\Database\Capsule\Manager::select("select * ,GROUP_CONCAT(merchant_channel_recharge.payType) as payTypes from merchant_channel_recharge WHERE {$whereStr} GROUP BY merchant_channel_recharge.merchantId, merchant_channel_recharge.channelMerchantId limit ? offset ?", $value);

        $rows = [];
        $total = count($total);

        // exit;
        foreach ($data ?? [] as $k => $v) {
            // $total = $v->count;
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);

            $payTypes = explode(',', $v->payTypes);
            $payTypeDescs = [];
            foreach ($payTypes ?? [] as $payType) {
                $payTypeDescs[] = $code['payType'][$payType];
            }
            $nv = [
                // 'count' => $v->count,
                'channel' => $v->channel,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                'channelMerchantId' => Tools::getHashId($v->channelMerchantId),
                // 'channelMerchantId' => $v->channelMerchantId,
                'channelMerchantNo' => $v->channelMerchantNo,
//                'dayAmountCount' => $v->amount,
                'dayAmountCount' => $v->amount ?? 0,
                "merchantNo" => $v->merchantNo,
                "setId" => Tools::getHashId($v->setId),
                "payTypeDescs" => $payTypeDescs,
                "payTypes" => $payTypes,
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

    public function rechargechannelImport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $merchant = new Merchant;
        $model = new MerchantChannelRecharge;
        $channelMerchant = new ChannelMerchant;
        $rateModel = new ChannelMerchantRate;

        $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $channelMerchantData = [];
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

        $actionBeforeData = $model->where('merchantId', $merchantData['merchantId'])->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();

        }

        $csv = new \ParseCsv\Csv();
        $csv->fields = ['merchantNo', 'channel', 'channelMerchantNo',
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
            if (empty($v['merchantNo'])) {
                unset($data[$k]);
                continue;
            }
            $channelMerchantData[$v['channelMerchantNo']] = isset($channelMerchantData[$v['channelMerchantNo']]) ? $channelMerchantData[$v['channelMerchantNo']]
                : $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
            $v['merchantId'] = $merchantData['merchantId'];
            $v['merchantNo'] = $merchantData['merchantNo'];
            $v['channelMerchantId'] = isset($channelMerchantData[$v['channelMerchantNo']]) &&
            isset($channelMerchantData[$v['channelMerchantNo']]['channelMerchantId']) &&
            $channelMerchantData[$v['channelMerchantNo']]['channel'] == $v['channel'] ?
                $channelMerchantData[$v['channelMerchantNo']]['channelMerchantId'] : 0;
            $v['channelMerchantId'] = intval($v['channelMerchantId']);

            if ($v['channelMerchantId'] == 0) {
                return $response->withJson([
                    'result' => '渠道商户号不存在:' . $v['channelMerchantNo'] . ':' . $v['channel'],
                    'success' => 0,
                ]);
            }

            //获取渠道商户号的费率配置
            $rate = $rateModel->where('channelMerchantId', $v['channelMerchantId'])->where('productType', 'Recharge')->first();
            if (empty($rate)) {
                return $response->withJson([
                    'result' => '渠道商户充值费率未设置:' . $v['channelMerchantNo'] . ':' . $v['channel'],
                    'success' => 0,
                ]);
            }
            $data[$k] = $v;
        }

        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $actionBeforeData = $model->where('merchantId', $merchantData['merchantId'])->get();
                if (empty($actionBeforeData)) {
                    $actionBeforeData = '';
                } else {
                    $actionBeforeData = $actionBeforeData->toJson();

                }
                $model->where('merchantId', $merchantData['merchantId'])->delete();
                $model->insert($data);
                $model->refreshCache(['merchantId' => $merchantData['merchantId']]);
                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_MERCHANT_CHANNEL_RECHARGE',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByMerchantId($merchantData['merchantId']), JSON_UNESCAPED_UNICODE),
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

    public function rechargechannelBatchUpdate(Request $request, Response $response, $args){

        $rateModel = new ChannelMerchantRate;
        $merchant = new Merchant;
        $channelMerchant = new ChannelMerchant;
        $model = new MerchantChannelRecharge;
        $logger = $this->c->logger;

        $merchantNoes = $request->getParam('merchantNoes');
        $merchantNoes = explode(',',$merchantNoes);
        if (empty($merchantNoes)) {
            return $response->withJson([
                'result' => '请选择要更改的配置',
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
        $csv->fields = ['merchantNo', 'channel', 'channelMerchantNo',
            'payChannelStatus', 'payType', 'bankCode', 'cardType',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit',
            'openTimeLimit', 'beginTime', 'endTime', 'status'];
        $csv->auto($file['file']->file);
        $data = $csv->data;
        foreach ($merchantNoes as $merchantNo){
            $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
            if (empty($merchantData)) continue;
            $tmp = [];
            foreach ($data ?? [] as $k => $v) {
                foreach ($v as $a => $b) {
                    $v[$a] = str_replace(["'", ' '], '', $b);
                }
                if (empty($v['merchantNo'])) {
                    unset($data[$k]);
                    continue;
                }
                $channelMerchantData = $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
                if(!$channelMerchantData || !$channelMerchantData['channelMerchantId']){
                    $logger->debug("批量修改失败，获取渠道数据失败" . $v['channelMerchantNo'] . ':' . $v['channel']);
                    continue;
                }
                $v['channelMerchantId'] = intval($channelMerchantData['channelMerchantId']);
                $v['merchantId'] = $merchantData['merchantId'];
                $v['merchantNo'] = $merchantData['merchantNo'];
                //渠道商户号不存在
                if ($v['channelMerchantId'] == 0) {
                    $logger->debug("批量修改失败，渠道商户号不存在" . $v['channelMerchantNo'] . ':' . $v['channel']);
                    continue;
                }

                //获取渠道商户号的费率配置
                $rate = $rateModel->where('channelMerchantId', $v['channelMerchantId'])->where('productType', 'Recharge')->first();
                //渠道商户充值费率未设置
                if (empty($rate)){
                    $logger->debug("批量修改失败，渠道商户充值费率未设置" . $v['channelMerchantNo'] . ':' . $v['channel']);
                    continue;
                }
                $tmp[] = $v;
            }
            if (!empty($tmp)) {
                $db = $this->c->database;
                try {
                    $db->getConnection()->beginTransaction();
                    $model->where('merchantId', $merchantData['merchantId'])->delete();
                    $model->insert($tmp);
                    $model->refreshCache();
                    SystemAccountActionLog::insert([
                        'action' => 'BATCH_UPDATE_MERCHANT_CHANNEL_RECHARGE',
                        'actionBeforeData' => '',
                        'actionAfterData' => json_encode($tmp, JSON_UNESCAPED_UNICODE),
                        'status' => 'Success',
                        'accountId' => $_SESSION['accountId'],
                        'ip' => Tools::getIp(),
                        'ipDesc' => Tools::getIpDesc(),
                    ]);

                    $model->refreshCache();
                    $db->getConnection()->commit();

                } catch (\Exception $e) {
                    $db->getConnection()->rollback();
                    $logger->debug("create失败" . $e->getMessage());
                }
            }

        }
        return $response->withJson([
            'result' => '上传成功',
            'success' => 1,
        ]);

    }

    public function rechargechannelExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $order = $request->getParam('order');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $merchant = new Merchant;
        $model = new MerchantChannelRecharge;
        $merchantData = [];
        $model = $model->where('merchantNo', $merchantNo);
        $total = $model->count();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);
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
        return $this->c->view->render($response, 'gm/merchant/settlementchannel.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/merchantSettlementTmpl.csv',
        ]);
    }

    public function settlementchannelSearch(Request $request, Response $response, $args)
    {
        $model = new MerchantChannelSettlement();
        $merchant = new Merchant;
        $merchantData = [];
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $channelMerchantNo = $request->getParam('channelMerchantNo');
        $channel = $request->getParam('channel');
        $shortName = $request->getParam('shortName');

        $merchantNo && $model = $model->where('merchantNo', $merchantNo);
        // $shortName && $model->where('shortName', $shortName);
        if (!empty($shortName)) {
            $data = $merchant->where('shortName', $shortName)->first();
            $data && $model = $model->where('merchantId', $data->merchantId);
        }
        $channel && $model = $model->where('channel', $channel);
        $channelMerchantNo && $model = $model->where('channelMerchantNo', $channelMerchantNo);

        $total = $model->count();
        $data = $model->offset($offset)
            ->limit($limit)
            ->orderBy('setId', 'desc')
            ->get();
        $rows = [];

        $channelBalance = [];
        $channel = new Channel;
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);

            $channelBalance[$v->channelMerchantNo] = isset($channelBalance[$v->channelMerchantNo]) ? $channelBalance[$v->channelMerchantNo]
                : $channel->getBalance($v->channelMerchantNo);

            $nv = [
                // 'accountBalance' => $channelBalance[$v->channelMerchantNo],
                'accountBalance' => $v->accountBalance,
                'channel' => $v->channel,
                'channelDesc' => $code['channel'][$v->channel]['name'] ?? "",
                'channelMerchantId' => Tools::getHashId($v->channelMerchantId),
                'channelMerchantNo' => $v->channelMerchantNo,
                "merchantNo" => $v->merchantNo,
//                "setId" => Tools::getHashId($v->setId),
                "setId" => $v->setId,
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

    //删除代付渠道配置
    public function settlementchannelDel(Request $request, Response $response, $arg)
    {
        $model = new MerchantChannelSettlement();

        $setIdStr = $request->getParam('setId');

        $setId = Tools::getIdByHash($setIdStr);

        if (empty($setId)) {
            return $response->withJson([
                'result' => 'setId不能为空',
                'success' => 0,
            ]);
        }
        $actionBeforeData = $model->where('setId', $setId)->get();

        $dataArr = $actionBeforeData->first();
        if (!$dataArr) {
            $actionBeforeData = '';
            return $response->withJson([
                'result' => '数据不存在！',
                'success' => 0,
            ]);
        } else {
            $actionBeforeData = $actionBeforeData->toJson();
        }

        $res = $model->where('setId', $setId)
            ->delete();
        if ($res === false) {
            return $response->withJson([
                'result' => '删除失败',
                'success' => 0,
            ]);
        }

        SystemAccountActionLog::insert([
            'action' => 'DELETE_MERCHANT_CHANNEL_SETTLEMENT',
            'actionBeforeData' => $actionBeforeData,
            'actionAfterData' => [],
            'status' => 'Success',
            'accountId' => $_SESSION['accountId'],
            'ip' => Tools::getIp(),
            'ipDesc' => Tools::getIpDesc(),
        ]);
        $model->refreshCache(['merchantId' => $dataArr['merchantId']]);
//        $b=$model->getCacheByMerchantId($dataArr['merchantId']);
//        var_dump($b);die;
        return $response->withJson([
            'result' => '删除成功',
            'success' => 1,
        ]);

    }

    public function settlementchannelImport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $merchant = new Merchant;
        $model = new MerchantChannelSettlement;
        $channelMerchant = new ChannelMerchant;
        $rateModel = new ChannelMerchantRate;

        $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $channelMerchantData = [];
//        if (empty($merchantData)) {
//            return $response->withJson([
//                'result' => '商户号不存在',
//                'success' => 0,
//            ]);
//        }
//
        $file = $request->getUploadedFiles();
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '文件不能为空',
                'success' => 0,
            ]);
        }

        $actionBeforeData = $model->where('merchantId', $merchantData['merchantId'])->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();

        }
        $csv = new \ParseCsv\Csv();
        $csv->fields = ['merchantNo', 'channel', 'channelMerchantNo',
            'settlementChannelStatus',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit', 'openTimeLimit', 'beginTime', 'endTime', 'status'];
        $csv->auto($file['file']->file);
        $data = $csv->data;
        $i = 0;
        foreach ($data ?? [] as $k => $v) {
            //上游除支付宝外，只能配置一条上游
            if ($v['channel'] != 'alipay') {
                $i++;
            }
            if ($i > 1) {
                return $response->withJson([
                    'result' => '同一商户号配置代付渠道不能多于一条',
                    'success' => 0,
                ]);
            }
            foreach ($v as $a => $b) {
                $v[$a] = str_replace(["'", ' '], '', $b);
            }
            if (empty($v['merchantNo'])) {
                unset($data[$k]);
                continue;
            }
            $channelMerchantData[$v['channelMerchantNo']] = isset($channelMerchantData[$v['channelMerchantNo']]) ? $channelMerchantData[$v['channelMerchantNo']]
                : $channelMerchant->getCacheByChannelMerchantNo($v['channelMerchantNo']);
            $v['merchantId'] = $merchantData['merchantId'];
            $v['merchantNo'] = $merchantData['merchantNo'];
            $v['channelMerchantId'] = isset($channelMerchantData[$v['channelMerchantNo']]) &&
            isset($channelMerchantData[$v['channelMerchantNo']]['channelMerchantId']) &&
            $channelMerchantData[$v['channelMerchantNo']]['channel'] == $v['channel'] ?
                $channelMerchantData[$v['channelMerchantNo']]['channelMerchantId'] : 0;
            $v['channelMerchantId'] = intval($v['channelMerchantId']);
            $v['settlementAccountType'] = 'UsableAccount';
            $v['accountBalance'] = 0;
            $v['accountReservedBalance'] = 0;
            if ($v['channelMerchantId'] == 0) {
                return $response->withJson([
                    'result' => '渠道商户号不存在:' . $v['channelMerchantNo'] . ':' . $v['channel'],
                    'success' => 0,
                ]);
            }
            //获取渠道商户号的费率配置
            $rate = $rateModel->where('channelMerchantId', $v['channelMerchantId'])->where('productType', 'Settlement')->first();
            if (empty($rate)) {
                return $response->withJson([
                    'result' => '渠道商户号代付结算费率未设置:' . $v['channelMerchantNo'] . ':' . $v['channel'],
                    'success' => 0,
                ]);
            }
            $data[$k] = $v;
        }

        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model->where('merchantId', $merchantData['merchantId'])->delete();
                $model->insert($data);
                SystemAccountActionLog::insert([
                    'action' => 'IMPORT_MERCHANT_CHANNEL_SETTLEMENT',
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByMerchantId($merchantData['merchantId']), JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);

                $model->refreshCache(['merchantId' => $merchantData['merchantId']]);
                (new Amount)->init($merchantData['merchantId'], $merchantData['merchantNo']);
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

    public function settlementchannelBatchUpdate(Request $request, Response $response, $args){
        $ids = $request->getParam('setIds');
        $setIds = explode(',',$ids);
        if (empty($setIds)) {
            return $response->withJson([
                'result' => '请选择要更改的配置',
                'success' => 0,
            ]);
        }
//        $Merchants = MerchantChannelSettlement::whereIn('setId',$setIds)->get(['merchantNo','merchantId'])->toArray();
//        print_r($Merchants);exit;

        $file = $request->getUploadedFiles();
        if (!isset($file['file']) || empty($file['file'])) {
            return $response->withJson([
                'result' => '文件不能为空',
                'success' => 0,
            ]);
        }

        $csv = new \ParseCsv\Csv();
        $csv->fields = ['merchantNo', 'channel', 'channelMerchantNo',
            'settlementChannelStatus',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit', 'openTimeLimit', 'beginTime', 'endTime', 'status'];
        $csv->auto($file['file']->file);
        $data = $csv->data;
        $count = count($data);
        if($count != 1){
            return $response->withJson([
                'result' => '配置错误，请确保配置数据为一行',
                'success' => 0,
            ]);
        }

        $updateFields = $data[0];
        foreach ($updateFields as $a => $b) {
            $updateFields[$a] = str_replace(["'", ' '], '', $b);
        }
        unset($updateFields['merchantNo']);
        if(!$updateFields){
            return $response->withJson([
                'result' => '请填写要更改的参数',
                'success' => 0,
            ]);
        }
        $channelMerchant = new ChannelMerchant;
        $channelMerchant = $channelMerchant->getCacheByChannelMerchantNo($updateFields['channelMerchantNo']);
        $updateFields['channelMerchantId'] = intval($channelMerchant['channelMerchantId']);
        if ($updateFields['channelMerchantId'] == 0) {
            return $response->withJson([
                'result' => '渠道商户号不存在:' . $updateFields['channelMerchantNo'] . ':' . $updateFields['channel'],
                'success' => 0,
            ]);
        }
        //获取渠道商户号的费率配置
        $rateModel = new ChannelMerchantRate;
        $rate = $rateModel->where('channelMerchantId', $updateFields['channelMerchantId'])->where('productType', 'Settlement')->first();
        if (empty($rate)) {
            return $response->withJson([
                'result' => '渠道商户号代付结算费率未设置:' . $updateFields['channelMerchantNo'] . ':' . $updateFields['channel'],
                'success' => 0,
            ]);
        }
        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model = new MerchantChannelSettlement;
                $model->whereIn('setId', $setIds)->update($updateFields);
                SystemAccountActionLog::insert([
                    'action' => 'BATCH_UPDATE_MERCHANT_CHANNEL_SETTLEMENT',
                    'actionBeforeData' => '',
                    'actionAfterData' => json_encode($updateFields, JSON_UNESCAPED_UNICODE),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);

                $model->refreshCache();
                $Merchants = MerchantChannelSettlement::whereIn('setId',$setIds)->get(['merchantNo','merchantId'])->toArray();
                (new Amount)->batchUpdate($Merchants, $updateFields['channelMerchantNo'],$updateFields['channelMerchantId']);
                $db->getConnection()->commit();

                return $response->withJson([
                    'result' => '上传成功',
                    'success' => 1,
                ]);
            } catch (\Exception $e) {
//                $logger->debug("create失败" . $e->getMessage());
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => '上传失败:' . $e->getMessage(),
                    'success' => 0,
                ]);
            }
        }

    }

    public function settlementchannelExport(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $order = $request->getParam('order');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $merchant = new Merchant;
        $model = new MerchantChannelSettlement;
        $merchantData = [];
        $model = $model->where('merchantNo', $merchantNo);
        $total = $model->count();
        // $data = $model->offset($offset)->limit($limit)->get();
        $data = $model->get();
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
                : $merchant->getCacheByMerchantId($v->merchantId);
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
                "setId" => Tools::getHashId($v->setId),
                // "settlementAccountType" => $v->settlementAccountType,
                "settlementChannelStatus" => $v->settlementChannelStatus,
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

    public function notice(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/merchant/notice.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function noticeSearch(Request $request, Response $response, $args)
    {
        $limit = (int)$request->getParam('limit', 20);
        $offset = (int)$request->getParam('offset', 0);
        $title = $request->getParam('title');
        $publisher = $request->getParam('publisher');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');

        $model = MerchantNotice::from('merchant_notice as mn')
            ->leftJoin('system_account as sa', 'mn.publishedAccountId', '=', 'sa.id')
            ->select(['sa.userName','mn.*']);

        $title && $model = $model->where('mn.title', 'like',"%$title");
        $publisher && $model = $model->where('sa.userName', $publisher);
        $beginTime && $model = $model->where('m.published_at', ">=", $beginTime);
        $endTime && $model = $model->where('m.published_at', "<=", $endTime);

        $total = $model->count();

        $data = $model->orderBy('mn.id', 'desc')->offset($offset)->limit($limit)->get();

        return $response->withJson([
            'result' => [],
            'rows' => $data,
            'success' => 1,
            'total' => $total,
        ]);
    }

    //下拉框获取商户号
    public function getMerchantNo(Request $request, Response $response, $args){
        $data=Merchant::from('merchant')
            ->get(['merchantNo'])->toArray();
        $newData=[];
        foreach ($data as $key=>$value) {
            $newData[$key]=$value['merchantNo'];
        }
        return $response->withJson([
            'result' =>$newData,
            'success' => 1,
        ]);
    }

    //创建消息公告
    public function createNotice(Request $request, Response $response, $args){

        $logger = $this->c->logger;

        $validator = $this->c->validator->validate($request, [
            'title' => Validator::stringType()->length(2, 100)->notBlank(),
            'content' => Validator::stringType()->length(5, 255)->notBlank(),
            'recipient' => Validator::oneOf(
                Validator::length(5,255),
                Validator::nullType()
            ),
            'type' => Validator::in(['default','optional'])->noWhitespace()->notBlank(),
        ]);

        if (!$validator->isValid()) {
            $logger->error('valid', $validator->getErrors());
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        $type = $request->getParam('type');
        $title = $request->getParam('title');
        $content = $request->getParam('content');
        $recipient = $request->getParam('recipient');
        if($type != 'default'  && !$recipient){
            return $response->withJson([
                'result' => '请选择接收对象！',
                'success' => 0,
            ]);
        }
        $merchantNotice = new MerchantNotice();


        $merchantNotice->title = $title;
        $merchantNotice->content = $content;
        $merchantNotice->type = $type;
        if($type == 'optional'){
            $merchantNotice->recipient = $recipient;
        }
        $merchantNotice->createdAccountId = $_SESSION['accountId'];

        $res = $merchantNotice->save();

        if(!$res){
            return $response->withJson([
                'result' => '添加失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '添加成功',
            'success' => 1,
        ]);
    }
    //发布公告
    public function publishNotice(Request $request, Response $response, $args){
        $id = $request->getParam('noticeId');
        $res=MerchantNotice::where('id',$id)->update(
            ['status'=>'published','publishedAccountId'=>$_SESSION['accountId']]
        );
        if(!$res){
            return $response->withJson([
                'result' => '发布失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '发布成功',
            'success' => 1,
        ]);
    }

    //删除消息公告
    public function deleteNotice(Request $request, Response $response, $args){

        $id = $request->getParam('id');
        $res=MerchantNotice::where('id',$id)->delete();
        if(!$res){
            return $response->withJson([
                'result' => '删除失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '删除成功',
            'success' => 1,
        ]);
    }


    public function updateNotice(Request $request, Response $response, $args){
        $id = $request->getParam('id');
        $res=MerchantNotice::where('id',$id)->update(
            ['status'=>'published']
        );
        if(!$res){
            return $response->withJson([
                'result' => '发布失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '发布成功',
            'success' => 1,
        ]);
    }


}
