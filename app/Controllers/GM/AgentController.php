<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Helpers\Validator;
use App\Logics\MerchantLogic;
use App\Models\Agent;
use App\Models\AgentBankCard;
use App\Models\AgentFinance;
use App\Models\AgentLog;
use App\Models\AgentRate;
use App\Models\AgentReport;
use App\Models\AgentWithdrawOrder;
use App\Models\ChannelBalanceIssue;
use App\Models\ChannelMerchant;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * 代理模块
 * Class AgentController
 * @package App\Controllers\GM
 */
class AgentController extends GMController
{
    //代理资金记录导出的头部目录
    private function exportAgentFinanceHead()
    {
        return [
            'agentName' => '代理账号',
            'platformOrderNo' => '平台订单号',
            'dealMoney' => '操作金额',
            'takeBalance' => '余额',
            'balance' => '可提余额',
            'freezeBalance' => '冻结金额',
            'bailBalance' => '保证金',
            'dealTypeDesc' => '操作类型',
            'optAdmin' => '操作者',
            'created_at' => '交易时间',
        ];
    }

    //代理数据报表导出的头部目录
    private function exportAgentDataReportHead()
    {
        return [
            'agentName' => '代理账号',
            'addMerchant' => '新增下级',
            'commCount' => '佣金笔数',
            'commMoney' => '佣金金额',
            'settCommCount' => '下发佣金笔数',
            'settCommMoney' => '下发佣金金额',
            'withdrewCount' => '提款笔数',
            'withdrewMoney' => '提款金额',
            'withdrewFee' => '提款手续费',
//            'commWays' => '佣金结算方式D0 D1 D7 D30',
            'accountDate' => '财务日期',
        ];
    }

    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/agent/account/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    /**
     * 查询所有代理账户信息
     */
    public function search(Request $request, Response $response, $args)
    {
        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', 20);
        $status = $request->getParam('status');
        $loginName = $request->getParam('loginName');

        $code = $this->c->code;
        $agent = new Agent();

        $status && $agent = $agent->where('status', '=', $status);
        $loginName && $agent = $agent->where('loginName', '=', $loginName);

        $total = $agent->count();

        $rows = $agent->offset($offset)->limit($limit)->get()->toArray();
        foreach ($rows as $key => $item) {
            $rows[$key]['id'] = Tools::getHashId($item['id']);
            $rows[$key]['statusDesc'] = $code['commonStatus'][$item['status']];
            $rows[$key]['takeBalance'] = $item['balance'] + $item['freezeBalance'];
        }
        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);

    }

    /**
     * 对资金进行操作
     * reduceBail=减少保证金,addBail=增加保证金,freeze=解结金额,addFreeze=增加冻结金额
     * @param Request $request
     * @param Response $response
     * @param $args
     */
    public function editMoney(Request $request, Response $response, $args)
    {
        $agent = new Agent();

        $type = $request->getParam('type');
        $money = $request->getParam('money');
        $desc = $request->getParam('desc');
        $id = $request->getParam('id');

        $id = Tools::getIdByHash($id);

        $agentData = $agent->where('id', '=', $id)->lockForUpdate()->first();

        $actionBeforeData = $agentData->toJson();
        if (!$type) {
            return $response->withJson([
                'result' => '操作类型不能为空！',
                'success' => 0,
            ]);
        }
        if (!$money) {
            return $response->withJson([
                'result' => '金额不能为空！',
                'success' => 0,
            ]);
        }
        if (!is_numeric($money)) {
            return $response->withJson([
                'result' => '金额必须为数字！',
                'success' => 0,
            ]);
        }

        $logger = $this->c->logger;
        try {
            //减少保证金
            if ($type == 'reduceBail') {
                if ($agentData->bailBalance < $money) {
                    return $response->withJson([
                        'result' => '输入金额大于原有金额，不合法！',
                        'success' => 0,
                    ]);
                }
                $agentData->bailBalance = ($agentData->bailBalance - $money);
            }
            //增加保证金
            if ($type == 'addBail') {
                $agentData->bailBalance = ($agentData->bailBalance + $money);
            }
            //解结金额
            if ($type == 'freeze') {
                if ($agentData->freezeBalance < $money) {
                    return $response->withJson([
                        'result' => '输入金额大于原有金额，不合法！',
                        'success' => 0,
                    ]);
                }
                $agentData->freezeBalance = ($agentData->freezeBalance - $money);
                $agentData->balance = $agentData->balance + $money;
            }
            //增加冻结金额
            if ($type == 'addFreeze') {
                if ($agentData->balance < $money) {
                    return $response->withJson([
                        'result' => '没有这么多余额可以冻结哟！',
                        'success' => 0,
                    ]);
                }
                $agentData->freezeBalance = ($agentData->freezeBalance + $money);
                $agentData->balance = $agentData->balance - $money;
            }
            $res = $agentData->save();

            $agent->refreshCache(['id'=>$id]);

            $agentNewData = $agent->where('id', '=', $id)->first();
            $rand = rand(1, 99999);
            $orderNumber = date('Ymdhis') . str_pad(mt_rand(1, $rand), 4, '0', 0);//生成订单号

            //操作者信息
            $user = new SystemAccount();
            $userData = $user->where('id', '=', $_SESSION['accountId'])->first();
            //流水资金明细
            AgentFinance::insert([
                'agentId' => $id,
                'agentName' => $agentData->loginName,
                'platformOrderNo' => $orderNumber,
                'dealMoney' => $money,
                'balance' => $agentNewData->balance,
                'freezeBalance' => $agentNewData->freezeBalance,
                'bailBalance' => $agentData->bailBalance,
                'dealType' => $type,
                'optId' => $_SESSION['accountId'],
                'optAdmin' => $userData->userName,
                'optIP' => Tools::getIp(),
                'optDesc' => $desc ?? "未有描述信息",
            ]);
            //管理员操作代理日志
            AgentLog::insert([
                'action' => 'UPDATE_MONEY',//$agent->_get('dealType')['UPDATE_MONEY'],
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $agentNewData->toJson(),
                'optId' => $_SESSION['accountId'],
                'optName' => $userData->userName,
                'status' => 'Success',
                'desc' => $desc ?? "未有描述信息",
                'ipDesc' => Tools::getIpDesc(),
                'ip' => Tools::getIp()
            ]);
        } catch (\Exception $e) {
            $logger->error('Exception:' . $e->getMessage());
            return $response->withJson([
                'result' => '失败',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '成功',
            'success' => 1,
        ]);

    }

    //重置登录密码
    public function updatePwd(Request $request, Response $response, $args)
    {

        $userId = $request->getParam('userId');
        $type = $request->getParam('type', 'resetloginpwd');
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
        $agent = new Agent();
        $model = $agent->where('id', $userId)->first();
        if (empty($model)) {
            return $response->withJson([
                'result' => '数据不存在',
                'success' => 0,
            ]);
        }

        $actionBeforeData = $model->toJson();
        $pwd = Tools::getRandStr('0123456789', 6);
        $content = ['id' => $model->id, "password" => $pwd, 'loginName' => $model->loginName];

        $logType = '';
        if ($type == 'resetloginpwd') {
            $model->loginPwd = Tools::getHashPassword($pwd);
            $logType = 'UPDATE_LOGINPWD';
        }
        if ($type == 'resetsecurepwd') {
            $model->securePwd = Tools::getHashPassword($pwd);
            $logType = 'UPDATE_SECUREPWD';
        }
        $res = $model->save();

        if ($res !== false) {
            $actionAfterData = $model->toJson();
            $agent->refreshCache(['id'=>$userId]);
            //操作者信息
            $user = new SystemAccount();
            $userData = $user->where('id', '=', $_SESSION['accountId'])->first();
            AgentLog::insert([
                'action' => $logType,//$agent->_get('dealType')['UPDATE_MONEY'],
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $actionAfterData,
                'optId' => $_SESSION['accountId'],
                'optName' => $userData->userName,
                'status' => 'Success',
                'desc' => json_encode($content, JSON_UNESCAPED_UNICODE),
                'ipDesc' => Tools::getIpDesc(),
                'ip' => Tools::getIp()
            ]);

            return $response->withJson([
                'result' => [
                    'newPwd' => $pwd
                ],
                'success' => 1,
            ]);
        }
        return $response->withJson([
            'result' => '失败',
            'success' => 0,
        ]);

    }

    //新增代理账号
    public function addAccount(Request $request, Response $response, $args)
    {
        $params = $request->getParams();

        $agentTypeCode = \array_keys($this->code['agentType']['settleDay']);
        $validator = $this->c->validator->validate($request, [
            'loginName' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            'loginPwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            'securePwd' => Validator::stringType()->length(6, 32)->noWhitespace()->notBlank(),
            'settleAccWay' => Validator::in($agentTypeCode)->noWhitespace()->notBlank(),
            'settleAccRatio' => Validator::floatVal()->noWhitespace(),
        ]);
        if (!$validator->isValid()) {
            return $response->withJson([
                'result' => '验证不通过',
                'success' => 0,
            ]);
        }

        if($params['settleAccRatio']>1 ||$params['settleAccRatio']<0 ){
            return $response->withJson([
                'result' => '结算比例百分比填写错误！',
                'success' => 0,
            ]);
        }
        $agent = new Agent();

        $vali = $agent->where('loginName', $params['loginName'])->first();
        if ($vali) {
            return $response->withJson([
                'result' => '此代理账号已存在！',
                'success' => 0,
            ]);
        }

        $agent->loginName = $params['loginName'];
        $agent->loginPwd = Tools::getHashPassword($params['loginPwd']);
        $agent->nickName = $params['nickName'];
        $agent->securePwd = Tools::getHashPassword($params['securePwd']);
        $agent->settleAccWay = $params['settleAccWay'];
        $agent->bailBalance = $params['bailBalance'] ?? 0;
        $agent->settleAccRatio = $params['settleAccRatio'] ?? 0;
        $res = $agent->save();


        if ($res !== false) {
            $actionAfterData = $agent->toJson();

            $agent->refreshCache();

            //操作者信息
            $user = new SystemAccount();
            $userData = $user->where('id', '=', $_SESSION['accountId'])->first();
            AgentLog::insert([
                'action' => 'AGENT_ACCOUNT',//$agent->_get('dealType')['UPDATE_MONEY'],
                'actionBeforeData' => '',
                'actionAfterData' => $actionAfterData,
                'optId' => $_SESSION['accountId'],
                'optName' => $userData->userName,
                'status' => 'Success',
                'desc' => '新增代理账号',
                'ipDesc' => Tools::getIpDesc(),
                'ip' => Tools::getIp()
            ]);

            return $response->withJson([
                'result' => '成功',
                'success' => 1,
            ]);
        }
        return $response->withJson([
            'result' => '失败',
            'success' => 0,
        ]);

    }

    //编辑代理账号信息
    public function editAccount(Request $request, Response $response, $args)
    {
        $params = $request->getParams();

        if (!$params['type'] && $params['type'] != 'updStatus') {
            $agentTypeCode = \array_keys($this->code['agentType']['settleDay']);
            $validator = $this->c->validator->validate($request, [
                'settleAccWay' => Validator::in($agentTypeCode)->noWhitespace()->notBlank(),
                'settleAccRatio' => Validator::floatVal()->noWhitespace(),
            ]);
            if (!$validator->isValid()) {
                return $response->withJson([
                    'result' => '验证不通过',
                    'success' => 0,
                ]);
            }
            if($params['settleAccRatio']>1 ||$params['settleAccRatio']<0 ){
                return $response->withJson([
                    'result' => '结算比例百分比填写错误！',
                    'success' => 0,
                ]);
            }
        } else {
            $commonStatusCode = \array_keys($this->code['commonStatus']);
            $validator = $this->c->validator->validate($request, [
                'status' => Validator::in($commonStatusCode)->noWhitespace()->notBlank(),
            ]);
            if (!$validator->isValid()) {
                return $response->withJson([
                    'result' => '状态输入错误',
                    'success' => 0,
                ]);
            }
        }

        $id = Tools::getIdByHash($params['id']);

        $model = new Agent();
        $agent = $model->where('id', $id)->first();
        $actionBeforeData = $agent->toJson();
        if (!$params['type'] && $params['type'] != 'updStatus') {
            //        $agent->loginName=$params['loginName'];
//        $agent->loginPwd=Tools::getHashPassword($params['loginPwd']);
            $agent->nickName = $params['nickName'] ?? $agent->nickName;
//        $agent->securePwd=Tools::getHashPassword($params['securePwd']);
            $agent->settleAccWay = $params['settleAccWay'] ?? $agent->settleAccWay;
            $agent->bailBalance = $params['bailBalance'] ?? $agent->bailBalance;
            $agent->settleAccRatio = $params['settleAccRatio'] ?? $agent->settleAccRatio;
            $agent->status = $params['status'] ?? $agent->status;
        } else {
            $agent->status = $params['status'] == 'Normal' ? 'Close' : 'Normal';
        }

        $res = $agent->save();

        if ($res !== false) {
            $actionAfterData = $agent->toJson();

            $model->refreshCache(['id'=>$id]);

            //操作者信息
            $user = new SystemAccount();
            $userData = $user->where('id', '=', $_SESSION['accountId'])->first();
            AgentLog::insert([
                'action' => 'AGENT_ACCOUNT',//$agent->_get('dealType')['UPDATE_MONEY'],
                'actionBeforeData' => $actionBeforeData,
                'actionAfterData' => $actionAfterData,
                'optId' => $_SESSION['accountId'],
                'optName' => $userData->userName,
                'status' => 'Success',
                'desc' => '编辑代理账号',
                'ipDesc' => Tools::getIpDesc(),
                'ip' => Tools::getIp()
            ]);

            return $response->withJson([
                'result' => '成功',
                'success' => 1,
            ]);
        }
        return $response->withJson([
            'result' => '失败',
            'success' => 0,
        ]);

    }

    //代理账号费率
    public function rate(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/agent/rate.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/AgentRateTmpl.csv',
        ]);
    }

    //代理费率查询
    public function rateSearch(Request $request, Response $response, $args)
    {
        $model = new AgentRate();
        $agent = new Agent();
        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $agentLoginName = $request->getParam('agentLoginName');
        $productType = $request->getParam('productType');
        $payType = $request->getParam('payType');
        $rateType = $request->getParam('rateType');
        $status = $request->getParam('status');

        $model = $model->leftJoin('agent', 'agentId', '=', 'agent.id')
            ->select(['agent.nickName', 'agent_rate.*']);

        $agentLoginName && $model = $model->where('agentLoginName', $agentLoginName);
        $productType && $model = $model->where('productType', $productType);
        $payType && $model = $model->where('payType', $payType);
        $rateType && $model = $model->where('rateType', $rateType);
        $status && $model = $model->where('status', $status);

        $total = $model->count();
        $data = $model->orderBy('rateId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        $agentData = [];
        foreach ($data ?? [] as $k => $v) {
            $agentData[$v->id] = isset($agentData[$v->id]) ? $agentData[$v->id]
                : $agent->getCacheByAgentId($v->id);
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
                "agentLoginName" => $v->agentLoginName,
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
                'nickName' => $v->nickName,
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

    //代理费率导入
    public function rateImport(Request $request, Response $response, $args)
    {
        $agentLoginName = $request->getParam('agentLoginName');
        $agent = new Agent();
        $model = new AgentRate();
        $agentData = $agent->getCacheByLoginName($agentLoginName);
        if (empty($agentData)) {
            return $response->withJson([
                'result' => '代理账号不存在',
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

        $actionBeforeData = $model->where('agentId', $agentData['id'])->get();
        if (empty($actionBeforeData)) {
            $actionBeforeData = '';
        } else {
            $actionBeforeData = $actionBeforeData->toJson();
        }
        $csv = new \ParseCsv\Csv();
        $csv->fields = ['agentLoginName', 'productType', 'payType',
            'bankCode', 'cardType', 'rateType', 'rate', 'fixed', 'minServiceCharge',
            'maxServiceCharge', 'beginTime', 'endTime', 'status'];

        $csv->auto($file['file']->file);
        $data = $csv->data;

        foreach ($data ?? [] as $k => $v) {
            foreach ($v as $a => $b) {
                $v[$a] = str_replace(["'", ' '], '', $b);
            }
            if (empty($v['agentLoginName'])) {
                unset($data[$k]);
                continue;
            }
            $v['agentId'] = $agentData['id'];
            $v['agentLoginName'] = $agentData['loginName'];
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

            $data[$k] = $v;
        }

        if (!empty($data)) {
            $db = $this->c->database;
            try {
                $db->getConnection()->beginTransaction();
                $model->where('agentId', $agentData['id'])->delete();
                $model->insert($data);


                //操作者信息
                $user = new SystemAccount();
                $userData = $user->where('id', '=', $_SESSION['accountId'])->first();
                AgentLog::insert([
                    'action' => 'IMPORT_AGENT_RATE',//$agent->_get('dealType')['UPDATE_MONEY'],
                    'actionBeforeData' => $actionBeforeData,
                    'actionAfterData' => json_encode($model->getCacheByAgentId($agentData['id']), JSON_UNESCAPED_UNICODE),
                    'optId' => $_SESSION['accountId'],
                    'optName' => $userData->userName,
                    'status' => 'Success',
                    'desc' => '代理费率上传',
                    'ipDesc' => Tools::getIpDesc(),
                    'ip' => Tools::getIp()
                ]);

                $db->getConnection()->commit();
                $model->refreshCache(['id' => $agentData['id']]);
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

    //代理费率导出
    public function rateExport(Request $request, Response $response, $args)
    {
        $agentLoginName = $request->getParam('agentLoginName');
        $agent = new Agent();
        $model = new AgentRate();
        $agentData = [];

        $model = $model->leftJoin('agent', 'agentId', '=', 'agent.id')
            ->select(['agent.nickName', 'agent_rate.*']);

        $model = $model->where('agentLoginName', $agentLoginName);
        $total = $model->count();
        // $data = $model->offset($offset)->limit($limit)->get();
        $data = $model->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $agentData[$v->id] = isset($agentData[$v->id]) ? $agentData[$v->id]
                : $agent->getCacheByAgentId($v->id);
            $nv = [
                'bankCode' => $v->bankCode,
                'agentLoginName' => $v->agentLoginName,
                'bankCodeDesc' => $code['bankCode'][$v->bankCode] ?? '',
                'beginTime' => Tools::getJSDatetime($v->beginTime),
                'cardType' => $v->cardType ?? 'DEBIT',
                'cardTypeDesc' => $code['payType'][$v->payType] ?? '',
                "channel" => $v->channel,
                "channelDesc" => $code['channel'][$v->channel]['name'] ?? '',
                "endTime" => Tools::getJSDatetime($v->endTime),
                "maxServiceCharge" => $v->maxServiceCharge,
                "agentId" => $v->agentId,
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
                "nickName" => $v->nickName,
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

    //代理数据报表
    public function dataReport(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/agent/dataReport.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    //代理数据报表查询
    public function dataReportSearch(Request $request, Response $response, $args)
    {
//        $loginName=$request->getParam('loginName');
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');

        $export = $request->getParam('export');//是否是导出数据操作

        if ($beginDate > $endDate) {
            return $response->withJson([
                'result' => '开始时间不能大于结束时间',
                'success' => 0
            ]);
        }

        empty($endDate) && $endDate = date('Ymd');

        $reportModel=new AgentReport();

        $beginDate && $reportModel=$reportModel->where('accountDate','>=',$beginDate);
        $endDate && $reportModel=$reportModel->where('accountDate','<=',$endDate);

        $total=$reportModel->count();

        if (!$export) {
            $data = $reportModel->orderBy('accountDate', 'desc')->offset($offset)->limit($limit)->get()->toArray();
        } else {
            $data = $reportModel->orderBy('accountDate', 'desc')->get()->toArray();
        }

        if ($export) {
            Tools::csv_export($data, $this->exportAgentDataReportHead(), 'agentDataReport');
            die();
        }

        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $data,
//            'stat' => $stat,
        ]);
    }

    //代理资金记录
    public function agentFinance(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/agent/agentFinance.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
            'downTmplUrl' => '/resource/AgentRateTmpl.csv',
        ]);
    }

    //代理资金记录查询
    public function agentFinanceSearch(Request $request, Response $response, $args)
    {
        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', 20);
        $beginTime = $request->getParam('beginTime',date('Y-m-d'));
        $endTime = $request->getParam('endTime',date('Y-m-d').' 23:59:59');
        $dealType = $request->getParam('dealType');//交易类型
        $loginName = $request->getParam('agentName');//代理账号
        $platformOrderNo = $request->getParam('platformOrderNo');

        $export = $request->getParam('export');//是否是导出数据操作

        $model = new AgentFinance();

        $dealType && $model = $model->where('dealType', $dealType);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);
        $loginName && $model = $model->where('agentName', $loginName);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);

        $code = $this->c->code;

        $total = $model->count();

//        $stat=[];
//
//        $stat=

        if (!$export) {
            $data = $model->orderBy('id', 'desc')->offset($offset)->limit($limit)->get()->toArray();
        } else {
            $data = $model->orderBy('id', 'desc')->get()->toArray();
        }

        foreach ($data as $key => $item) {
            $data[$key]['takeBalance'] = $item['balance'] + $item['freezeBalance'];
            $data[$key]['dealTypeDesc'] = $code['dealType'][$item['dealType']];
        }

        if ($export) {
            Tools::csv_export($data, $this->exportAgentFinanceHead(), 'agentFinanceList');
            die();
        }
        return $response->withJson([
            'result' => [],
            'rows' => $data,
            'success' => 1,
            'total' => $total,
//            'stat'=>[]
        ]);

    }

    //代理提款申请订单
    public function withdrawOrder(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/agent/withdrawOrder.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    //代理提款申请订单查询
    public function withdrawOrderSearch(Request $request, Response $response, $args)
    {
        $offset = $request->getParam('offset', 0);
        $limit = $request->getParam('limit', 20);
        $createTime = $request->getParam('createTime');
        $status = $request->getParam('status');
        $agentName = $request->getParam('agentName');//代理账号
        $platformOrderNo = $request->getParam('platformOrderNo');

        $order = new AgentWithdrawOrder();

        $order = $order->leftJoin('agent_bank_card as agc', 'agc.id', '=', 'agent_withdraw_order.bankId')
            ->leftJoin('agent as a', 'a.id', '=', 'agc.agentId');

        $createTime && $order = $order->where('agent_withdraw_order.created_at', $createTime);
        $status && $order = $order->where('agent_withdraw_order.status', $status);
        $agentName && $order = $order->where('agent_withdraw_order.agentName', $agentName);
        $platformOrderNo && $order = $order->where('agent_withdraw_order.platformOrderNo', $platformOrderNo);

        $stat =$order->selectRaw('sum(dealMoney) as dealMoneySum,sum(fee) as feeSum,sum(realMoney) as realMoneySum')->first();

        $order=$order->select(['agc.bankName', 'agc.accountName', 'agc.accountNo', 'agc.bankCode', 'a.nickName', 'agent_withdraw_order.*']);

        $total= $order->count();
        $data = $order->offset($offset)->limit($limit)->orderBy('created_at', 'desc')->get()->toArray();

        $code = $this->c->code;

        foreach ($data as $key => $datum) {
            $data[$key]['id'] = Tools::getHashId($datum['id']);;
            $data[$key]['accountNo'] = Tools::decrypt($datum['accountNo']);
            $data[$key]['statusDesc'] = $code['withdrawOrderType'][$datum['status']];
        }

        return $response->withJson([
            'result' => [],
            'rows' => $data,
            'success' => 1,
            'total' => $total,
            'stat'=>$stat
        ]);
    }

    public function withdrewChannel(Request $request, Response $response, $args){
        $data = ChannelMerchant::where('openSettlement',1)->where('status','Normal')->get(['channelMerchantId','channelMerchantNo','channel'])->toArray();
        foreach ($data as &$val) {
            $val['channelName'] = $val['channel'].':'.$val['channelMerchantNo'];
            $val['channelMerchantId'] = Tools::getHashId($val['channelMerchantId']);
        }
        return $response->withJson([
            'result' => $data,
            'success' => 1,
        ]);
    }

    public function submitWithdrewByChannel(Request $request, Response $response, $args){
        $orderId = Tools::getIdByHash($request->getParam('id'));
        $channelId = Tools::getIdByHash($request->getParam('channelId'));
        $withOrder = AgentWithdrawOrder::where('id', $orderId)->where('status','Apply')->first();
        $channel = ChannelMerchant::where('channelMerchantId', $channelId)->where('status','Normal')->where('openSettlement','1')->first();
        if(empty($withOrder) || empty($channel)) {
            return $response->withJson([
                'result' => '代付渠道不存在或订单已完成',
                'success' => 0,
            ]);
        }
        $bank = AgentBankCard::where('id',$withOrder['bankId'])
            ->where('agentId',$withOrder['agentId'])
            ->where('status','Normal')
            ->first();
        if(empty($bank)) {
            return $response->withJson([
                'result' => '代付卡号异常',
                'success' => 0,
            ]);
        }
        $bankCode = $bank['bankCode'];
        $cardNo = trim(Tools::decrypt($bank['accountNo']));
        $userName = trim($bank['accountName']);
        $money = $withOrder['dealMoney'];
        $logic = new MerchantLogic($this->c);
        $issueOrderNo = Tools::getPlatformOrderNo('I');//提现订单
        try {
            $db = $this->c->database;
            $db->getConnection()->beginTransaction();
            $withOrder = new AgentWithdrawOrder();
            $withOrder = $withOrder->where('id', $orderId)->where('status','Apply')->lockForUpdate()->first();

            if (!$withOrder) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => '此订单已完成或不存在',
                    'success' => 0,
                ]);
            }
            $withOrder->status = 'Adopt';  //代付中
            $withOrder->save();
            $classOpt['class'] = '\App\Logics\AgentLogic';
            $classOpt['func'] = 'withdrawCallback';
            $issueData = [
                'issueOrderNo'=>$issueOrderNo,
                'channelId'=> $channelId,
                'channelNo'=> $channel['channelMerchantNo'],
                'bankCode'=>$bankCode,
                'cardNo'=>$cardNo,
                'userName'=>$userName,
                'issueAmount'=> $money,
                'adminName'=> $_SESSION['loginName'],
                'orderStatus'=> 'Transfered',
                'foreign_id'=> $orderId,
                'type'=> 'agent',
                'classOpt'=> json_encode($classOpt),
            ];
            ChannelBalanceIssue::insert($issueData);
            //发起代付
            $res = $logic->channelSettlement(['issueOrderNo'=>$issueOrderNo, 'channelId'=>$channelId, 'channelNo'=>$channel['channelMerchantNo'], 'bankCode'=>$bankCode, 'cardNo'=>$cardNo, 'userName'=>$userName, 'issueAmount'=>$money]);
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_CHANNEL_MERCHANT',
                'actionBeforeData' => json_encode($issueData),
                'actionAfterData' => json_encode($res),
                'status' => 'Success',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);
            $db->getConnection()->commit();
            $result = "提交下发订单成功";
        } catch (\Exception $e) {
            $this->logger->error('Issue Exception:' . $e->getMessage());
            $db->getConnection()->rollback();
            $success = 0;
            $result = $e->getMessage();
        }
        return $response->withJson([
            'success' => $success ?? 1,
            'result' => $result ?? 'Success',
        ]);
    }

    //代理提款申请驳回
    public function orderStatus(Request $request, Response $response, $args)
    {
        $orderId = Tools::getIdByHash($request->getParam('id'));
        $optDesc = $request->getParam('optDesc', '');

        $type = $request->getParam('type');
        if (!in_array($type,['nopass','pass'])) {
            return $response->withJson([
                'result' => '操作类型错误！',
                'success' => 0,
            ]);
        }


        //操作者信息
        $user = new SystemAccount();
        $userData = $user->where('id', '=', $_SESSION['accountId'])->first();

        $logger = $this->c->logger;
        global $app;
        $db = $app->getContainer()->database;
        try {
            $db->getConnection()->beginTransaction();

            $withOrder = new AgentWithdrawOrder();
            $withOrder = $withOrder->where('id', $orderId)->where('status','Apply')->lockForUpdate()->first();

            if (!$withOrder) {
                $db->getConnection()->rollback();
                return $response->withJson([
                    'result' => '此订单已完成或不存在',
                    'success' => 0,
                ]);
            }
            if ($type == 'nopass') {
                $withOrder->status = 'Refute';
                $str = '驳回订单';
            } else {
                $withOrder->status = 'Complete';
                $withOrder->realMoney =$withOrder->dealMoney;
                $str = '订单通过';
            }
            $withOrder->optDesc = $optDesc;
            $withOrder->optId = $_SESSION['accountId'];
            $withOrder->optAdmin = $userData->userName;
            $withOrder->optIP = Tools::getIp();
            $withOrder->save();

            if ($type == 'nopass') {
                $agent = new Agent();
                $agent = $agent->where('loginName', $withOrder->agentName)->lockForUpdate()->first();
                $balance = $agent->balance;

                $agent->balance = $agent->balance + ($withOrder->dealMoney + $withOrder->fee);
                $agent->save();

                //写入退回余额的流水信息
                AgentFinance::insert([
                    'agentId' => $agent->id,
                    'agentName' => $agent->loginName,
                    'platformOrderNo' => $withOrder->platformOrderNo,
                    'dealMoney' => $withOrder->dealMoney,
                    'balance' => $balance + $withOrder->dealMoney,
                    'freezeBalance' => $agent->freezeBalance,
                    'bailBalance' => $agent->bailBalance,
                    'dealType' => 'extractFail',
                    'optId' => $_SESSION['accountId'],
                    'optAdmin' => $userData->userName,
                    'optIP' => Tools::getIp(),
                    'optDesc' => "退回提款金额",
                ]);

                //写入退回手续费的流水信息
                AgentFinance::insert(['agentId' => $agent->id,
                    'agentName' => $agent->loginName,
                    'platformOrderNo' => $withOrder->platformOrderNo,
                    'dealMoney' => $withOrder->fee,
                    'balance' => $agent->balance,
                    'freezeBalance' => $agent->freezeBalance,
                    'bailBalance' => $agent->bailBalance,
                    'dealType' => 'returnFee',
                    'optId' => $_SESSION['accountId'],
                    'optAdmin' => $userData->userName,
                    'optIP' => Tools::getIp(),
                    'optDesc' => "退回手续费",]);
            }else {
                $agent = new Agent();
                $agent = $agent->where('loginName', $withOrder->agentName)->lockForUpdate()->first();
                //写入流水信息
                AgentFinance::insert(['agentId' => $agent->id,
                    'agentName' => $agent->loginName,
                    'platformOrderNo' => $withOrder->platformOrderNo,
                    'dealMoney' => $withOrder->fee,
                    'balance' => $agent->balance,
                    'freezeBalance' => $agent->freezeBalance,
                    'bailBalance' => $agent->bailBalance,
                    'dealType' => 'extractSuc',
                    'optId' => $_SESSION['accountId'],
                    'optAdmin' => $userData->userName,
                    'optIP' => Tools::getIp(),
                    'optDesc' => "手动打款成功",]);
            }

            //管理员操作代理日志
            AgentLog::insert([
                'action' => 'WITHDRAW_ORDER',//$agent->_get('dealType')['UPDATE_MONEY'],
                'actionBeforeData' => '',
                'actionAfterData' => '',
                'optId' => $_SESSION['accountId'],
                'optName' => $userData->userName,
                'status' => 'Success',
                'desc' => $str,
                'ipDesc' => Tools::getIpDesc(),
                'ip' => Tools::getIp()
            ]);
            $db->getConnection()->commit();
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            $logger->error('Exception:' . $e->getMessage());
            return $response->withJson([
                'result' => '操作失败！',
                'success' => 0,
            ]);
        }
        return $response->withJson([
            'result' => '操作成功',
            'success' => 1,
        ]);

    }

}
