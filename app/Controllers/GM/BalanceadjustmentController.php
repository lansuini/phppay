<?php

namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Models\AmountPay;
use App\Models\BalanceAdjustment;
use App\Models\Finance;
use App\Models\MerchantRate;
use App\Models\SystemAccount;
use App\Models\SystemCheckLog;
use App\Models\Merchant;
use App\Models\MerchantAmount;
use App\Models\PlatformRechargeOrder;
use App\Models\Agent;
use App\Models\AgentIncomeLog;
use App\Models\AgentMerchantRelation;
use App\Models\SystemAccountActionLog;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BalanceadjustmentController extends GMController
{
    public function index(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/balanceadjustment/index.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function search(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new BalanceAdjustment();
        $merchantData = [];

        $code = $this->c->code;
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $merchantNo = $request->getParam('merchantNo');
        $platformOrderNo = $request->getParam('platformOrderNo');
        $merchantOrderNo = $request->getParam('merchantOrderNo');
        // $transactionNo = $request->getParam('transactionNo');
        $status = $request->getParam('status');
        $bankrollDirection = $request->getParam('bankrollDirection');
        $bankrollType = $request->getParam('bankrollType');
        $offset = $request->getParam('offset');
        $auditBeginTime = $request->getParam('auditBeginTime');
        $auditEndTime = $request->getParam('auditEndTime');
        $beginTime = $request->getParam('beginTime');
        $endTime = $request->getParam('endTime');
        $merchantId = 0;

        $merchantNo && $merchantData = $merchant->getCacheByMerchantNo($merchantNo);
        $merchantData && $merchantId = $merchantData['merchantId'];
        $status && $model = $model->where('status', $status);
        $merchantId && $model = $model->where('merchantId', $merchantId);
        $platformOrderNo && $model = $model->where('platformOrderNo', $platformOrderNo);
        $merchantOrderNo && $model = $model->where('merchantOrderNo', $merchantOrderNo);
        $bankrollDirection && $model = $model->where('bankrollDirection', $bankrollDirection);
        $bankrollType && $model = $model->where('bankrollType', $bankrollType);

        $auditBeginTime && $model = $model->where('auditTime', '>=', $auditBeginTime);
        $auditEndTime && $model = $model->where('auditTime', '<=', $auditEndTime);
        $beginTime && $model = $model->where('created_at', '>=', $beginTime);
        $endTime && $model = $model->where('created_at', '<=', $endTime);

        $total = $model->count();
        $data = $model->orderBy('adjustmentId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];


        $where = [];
        $where[] = '1=1';
        $value = [];
        $merchantId && $where[] = 'merchantId=?';
        $merchantId && $value[] = $merchantId;
        $status && $where[] = 'status=?';
        $status && $value[] = $status;

        $platformOrderNo && $where[] = 'platformOrderNo=?';
        $platformOrderNo && $value[] = $platformOrderNo;
        $merchantOrderNo && $where[] = 'merchantOrderNo=?';
        $merchantOrderNo && $value[] = $merchantOrderNo;

        $bankrollDirection && $where[] = 'bankrollDirection=?';
        $bankrollDirection && $value[] = $bankrollDirection;
        $bankrollType && $where[] = 'bankrollType=?';
        $bankrollType && $value[] = $bankrollType;

        $auditBeginTime && $where[] = 'auditTime>=?';
        $auditBeginTime && $value[] = $auditBeginTime;
        $auditEndTime && $where[] = 'auditTime<=?';
        $auditEndTime && $value[] = $auditEndTime;

        $beginTime && $where[] = 'created_at>=?';
        $beginTime && $value[] = $beginTime;
        $endTime && $where[] = 'created_at<=?';
        $endTime && $value[] = $endTime;

        $whereStr = implode(' and ', $where);
        $sql = "select count(adjustmentId) as adjustmentId, sum(amount) as amount from balance_adjustment where {$whereStr} order by created_at desc";
        $stat = \Illuminate\Database\Capsule\Manager::select($sql, $value);
        $stat = current($stat);
        $stat = json_decode( json_encode( $stat),true);
        $stat['amount'] = number_format($stat['amount'],2);
        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v->merchantId] = isset($merchantData[$v->merchantId]) ? $merchantData[$v->merchantId]
            : $merchant->getCacheByMerchantId($v->merchantId);
            $nv = [
                // 'adjustmentId' => $v->id,
                'amount' => $v->amount,
                'applyPerson' => $v->applyPerson,
                'applyTime' => Tools::getJSDatetime($v->created_at),
                'auditPerson' => $v->auditPerson,
                "auditTime" => Tools::getJSDatetime($v->auditTime),
                "bankrollDirection" => $v->bankrollDirection,
                "bankrollDirectionDesc" => $code['bankrollDirection'][$v->bankrollDirection] ?? '',
                "bankrollType" => $v->bankrollType,
                "bankrollTypeDesc" => $code['bankrollType'][$v->bankrollType] ?? '',
                "merchantId" => $v->merchantId,
                "merchantNo" => $v->merchantNo,
                "platformOrderNo" => $v->platformOrderNo,
                "shortName" => $merchantData[$v->merchantId]['shortName'],
                "status" => $v->status,
                "statusDesc" => $code['commonStatus2'][$v->status] ?? '',
                "summary" => $v->summary,
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

    // 商户余额调整获取随机验证码
    public function getRandom(Request $request, Response $response, $args)
    {
        global $app;
        $redis = $app->getContainer()->redis;
        $random = date('His');
        $redis->setex("balanceRandom-".$_SESSION['accountId'].'-'.$random, 5*60, true);
        return $response->withJson([
            'result' => [],
            'success' => 1,
            'random' => $random,
        ]);
    }

    // 余额审核通过
    public function balanceAudit(Request $request, Response $response, $args)
    {
        $id = $request->getParam('id');
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

        //加锁防并发  记得处理完之后要删除锁，以防产生死锁

        $lockRequest = 'balanceAudit:check:' . $id;
        $lock = $redis->setnx($lockRequest,1);
        if(!$lock){
            return $response->withJson([
                'success' => 0,
                'result' => "调整余额中，请刷新。",
            ]);
        }
        $passwordtype = $request->getParam('auditType');
        $model = new SystemCheckLog();
        $data = $model->where("id",$id)->where("type",$passwordtype)->first();
        if(!$data) {
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'success' => 0,
                'result' => "无些数据",
            ]);
        }

        $db = $this->c->database;
        $logger = $this->c->logger;
        $content = json_decode($data->content,true);
        $code = $this->c->code['bankrollDirection'];
        $BalanceAdjustment = new BalanceAdjustment();
        $where = ['platformOrderNo'=>$content['platformOrderNo'],'status'=>'Unaudit','amount'=>$content['amount'],'merchantId'=>$content['merchantId']];
        $balanceInfo = $BalanceAdjustment->where($where)->first();
        if(!$balanceInfo) {
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'result' => '此信息不存在，或已审核完成，请刷新',
                'success' => 0,
            ]);
        }
        try{
            $db->getConnection()->beginTransaction();
            $merchantAmount = new MerchantAmount;
            $merchantAmountData = $merchantAmount->where('merchantId', $content['merchantId'])->lockForUpdate()->first();
            if($content['bankrollDirection'] == 'Restore' || $content['bankrollDirection'] == 'Unfreeze') {
                $merchantAmountData->settlementAmount = $merchantAmountData->settlementAmount + $content['amount'];
                if($content['bankrollDirection'] == 'Unfreeze') {
                    $merchantAmountData->freezeAmount = $merchantAmountData->freezeAmount - $content['amount'];
                }

                AmountPay::where('merchantId', $content['merchantId'])
                    ->where('accountDate', date("Y-m-d"))
                    ->update(['balance' => $merchantAmountData->settlementAmount]);
                Finance::insert([
                    'merchantId' => $content['merchantId'],
                    'merchantNo' => $data->relevance,
                    'platformOrderNo' => $content['platformOrderNo'],
                    'amount' => $content['amount'],
                    'balance' => $merchantAmountData->settlementAmount,
                    'financeType' => 'PayIn',
                    'accountDate' => date("Y-m-d"),
                    'accountType' => $content['bankrollType'] == 'ServiceCharge' ? 'ServiceChargeAccount' : 'SettlementAccount',
                    'sourceId' => $content['sourceId'],
                    'sourceDesc' => '余额调整-' . ($code[$content['bankrollDirection']]) . '-' . ($model->bankrollType == 'ServiceCharge' ? '手续费' : '账户资金'),
                    'operateSource' => 'admin',
                    'summary' => $content['summary'],
                ]);
            }

            if($content['bankrollDirection'] == 'Recharge'){//充值订单
                $rechargeModel = new PlatformRechargeOrder();
                $orderDataLock = $rechargeModel->where('platformOrderNo', $balanceInfo->platformOrderNo)->lockForUpdate()->first();
                if(!$orderDataLock){
                    $db->getConnection()->rollback();
                    return $response->withJson([
                        'result' => '审核异常，充值订单不存在！',
                        'success' => 0,
                    ]);
                }
                if($orderDataLock['orderStatus'] != 'Transfered'){
                    $db->getConnection()->rollback();
                    return $response->withJson([
                        'result' => '审核异常，充值订单状态已改变！',
                        'success' => 0,
                    ]);
                }
                $orderDataLock->orderStatus = 'Success';//充值成功
                $orderDataLock->realOrderAmount = $balanceInfo->amount;
                $orderDataLock->chargeAmount = $balanceInfo->amount - $orderDataLock->serviceCharge;
                $orderDataLock->channelNoticeTime = date('Y-m-d H:i:s');
                $orderDataLock->save();

                //代理手续费
                $agentId = AgentMerchantRelation::where('merchantId', $orderDataLock->merchantId)->value('agentId');
                if($agentId || isset($orderDataLock->agentFee) && $orderDataLock->agentFee > 0) {
                    $agentLog = new AgentIncomeLog();
                    $agentLog->updateIncomeLog($orderDataLock['merchantId'], $orderDataLock->platformOrderNo, $orderDataLock->realOrderAmount,'recharge');
                }

                Finance::insert([
                    [
                        'merchantId' => $balanceInfo->merchantId,
                        'merchantNo' => $balanceInfo->merchantNo,
                        'platformOrderNo' => $balanceInfo->platformOrderNo,
                        'amount' => $balanceInfo->amount,
                        'balance' => $merchantAmountData->settlementAmount + $balanceInfo->amount,
                        'financeType' => 'PayIn',
                        'accountDate' => date('Y-m-d'),
                        'accountType' => 'SettledAccount',
                        'sourceId' => $orderDataLock->id,
                        'sourceDesc' => '商户余额充值',
                        'merchantOrderNo' => '',
                        'operateSource' => 'admin',
                        'summary' => '商户余额充值加钱',
                    ],[
                        'merchantId' => $balanceInfo->merchantId,
                        'merchantNo' => $balanceInfo->merchantNo,
                        'platformOrderNo' => $balanceInfo->platformOrderNo,
                        'amount' => $orderDataLock->serviceCharge,
                        'balance' => $merchantAmountData->settlementAmount + $balanceInfo->amount - $orderDataLock->serviceCharge,
                        'financeType' => 'PayOut',
                        'accountDate' => date('Y-m-d'),
                        'accountType' => 'ServiceChargeAccount',
                        'sourceId' => $orderDataLock->id,
                        'sourceDesc' => '商户余额充值手续费',
                        'merchantOrderNo' => '',
                        'operateSource' => 'admin',
                        'summary' => "商户余额充值收取手续费",
                    ]
                ]);
                $merchantAmountData->settlementAmount = $merchantAmountData->settlementAmount + $orderDataLock->chargeAmount;
            }


            $merchantAmountData->save();

            SystemAccountActionLog::insert([
                    'action' => 'CREATE_BALANCE_ADJUSTMENT',
                    'actionBeforeData' => '',
                    'actionAfterData' => $merchantAmountData->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);



            $balanceInfo->status = "Success";
            $balanceInfo->auditPerson = $_SESSION['userName'];
            $balanceInfo->auditTime = date("Y-m-d H:i:s",time());
            $balanceInfo->save();

            $data->status = '1';
            $data->admin_id = $_SESSION['accountId'];
            $data->check_ip = Tools::getIp();
            $data->check_time = date("Y-m-d H:i:s",time());
            $data->save();

            $merchantAmountData->refreshCache(['merchantId' => $content['merchantId']]);
            $db->getConnection()->commit();

        }catch (\Exception $e) {
            $db->getConnection()->rollback();
            $logger->error('余额调整审核通过-' . ($code[$content['bankrollDirection']]) . 'error' . $e->getMessage());
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'result' => '操作异常:' . $e->getMessage(),
                'success' => 0,
            ]);
        }
        $redis->del($lockRequest); //删除锁
        return $response->withJson([
            'result' => '添加成功',
            'success' => 1,
        ]);
    }

    // 余额审核不通过
    public function balanceUnaudit(Request $request, Response $response, $args)
    {
        $id = $request->getParam('id');
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
        //加锁防并发  记得处理完之后要删除锁，以防产生死锁
        $lockRequest = 'balanceUnaudit:check:' . $id;
        $lock = $redis->setnx($lockRequest,1);
        if(!$lock){
            return $response->withJson([
                'success' => 0,
                'result' => "调整余额中，请刷新。",
            ]);
        }

        $passwordtype = $request->getParam('auditType');
        $model = new SystemCheckLog();
        $data = $model->where("id",$id)->where("type",$passwordtype)->where('status','0')->first();
        if(!$data) {
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'success' => 0,
                'result' => "无此数据",
            ]);
        }

        $db = $this->c->database;
        $logger = $this->c->logger;
        $content = json_decode($data->content,true);
        $code = $this->c->code['bankrollDirection'];
        $BalanceAdjustment = new BalanceAdjustment();
        $where = ['platformOrderNo'=>$content['platformOrderNo'],'status'=>'Unaudit','amount'=>$content['amount'],'merchantId'=>$content['merchantId']];
        $balanceInfo = $BalanceAdjustment->where($where)->first();
        if(!$balanceInfo) {
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'result' => '此信息不存在',
                'success' => 0,
            ]);
        }
        try{
            $db->getConnection()->beginTransaction();
            if($content['bankrollDirection'] == 'Recharge'){//充值订单
                $rechargeModel = new PlatformRechargeOrder();
                $orderDataLock = $rechargeModel->where('platformOrderNo', $balanceInfo->platformOrderNo)->lockForUpdate()->first();
                if(!$orderDataLock){
                    $db->getConnection()->rollback();
                    return $response->withJson([
                        'result' => '审核异常，充值订单不存在！',
                        'success' => 0,
                    ]);
                }
                if($orderDataLock['orderStatus'] != 'Transfered'){
                    $db->getConnection()->rollback();
                    return $response->withJson([
                        'result' => '审核异常，充值订单状态已改变！',
                        'success' => 0,
                    ]);
                }
                $orderDataLock->orderStatus = 'Fail';//充值失败
                $orderDataLock->channelNoticeTime = date('Y-m-d H:i:s');
                $orderDataLock->save();
            }
            $merchantAmount = new MerchantAmount;
            $merchantAmountData = $merchantAmount->where('merchantId', $content['merchantId'])->lockForUpdate()->first();
            if($content['bankrollDirection'] == 'Retrieve' ) {
                $merchantAmountData->settlementAmount = $merchantAmountData->settlementAmount + $content['amount'];

                Finance::insert([
                    'merchantId' => $content['merchantId'],
                    'merchantNo' => $data->relevance,
                    'platformOrderNo' => $content['platformOrderNo'],
                    'amount' => $content['amount'],
                    'balance' => $merchantAmountData->settlementAmount,
                    'financeType' => 'PayIn',
                    'accountDate' => date("Y-m-d"),
                    'accountType' => $content['bankrollType'] == 'ServiceCharge' ? 'ServiceChargeAccount' : 'SettlementAccount',
                    'sourceId' => $content['sourceId'],
                    'sourceDesc' => '余额调整-' . ($code[$content['bankrollDirection']]) . '-' . ($model->bankrollType == 'ServiceCharge' ? '手续费' : '账户资金'),
                    'operateSource' => 'admin',
                    'summary' => $content['summary'],
                ]);

                AmountPay::where('merchantId', $content['merchantId'])
                    ->where('accountDate', date("Y-m-d"))
                    ->update(['balance' => $merchantAmountData->settlementAmount]);

            }
            $merchantAmountData->save();

            SystemAccountActionLog::insert([
                'action' => 'CREATE_BALANCE_ADJUSTMENT',
                'actionBeforeData' => '',
                'actionAfterData' => $merchantAmountData->toJson(),
                'status' => 'Fail',
                'accountId' => $_SESSION['accountId'],
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
            ]);


            $balanceInfo->status = "Fail";
            if($content['bankrollDirection'] == "Unfreeze") {
                $balanceInfo->status = "Freeze";
                $balanceInfo->bankrollDirection = "Freeze";
            }
            $balanceInfo->auditPerson = $_SESSION['userName'];
            $balanceInfo->auditTime = date("Y-m-d H:i:s",time());
            $balanceInfo->save();

            $data->status = '2';
            $data->admin_id = $_SESSION['accountId'];
            $data->check_ip = Tools::getIp();
            $data->check_time = date("Y-m-d H:i:s",time());
            $data->save();

            $merchantAmountData->refreshCache(['merchantId' => $content['merchantId']]);
            $db->getConnection()->commit();

        }catch (\Exception $e) {
            $db->getConnection()->rollback();
            $logger->error('余额调整-' . ($code[$content['bankrollDirection']]) . 'error' . $e->getMessage());
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'result' => '操作异常:' . $e->getMessage(),
                'success' => 0,
            ]);
        }
        $redis->del($lockRequest); //删除锁
        return $response->withJson([
            'result' => '添加成功',
            'success' => 1,
        ]);
    }

    public function unFreeze(Request $request, Response $response, $args)
    {
        $orderNo = $request->getParam('orderNo');
        //加锁防并发  记得处理完之后要删除锁，以防产生死锁
        global $app;
        $redis = $app->getContainer()->redis;
        $lockRequest = 'unFreeze:check:' . $orderNo;
        $lock = $redis->setnx($lockRequest,1);
        if(!$lock){
            return $response->withJson([
                'success' => 0,
                'result' => "解冻中，请刷新。",
            ]);
        }
        $BalanceAdjustment = new BalanceAdjustment();
        $where = ['platformOrderNo'=>$orderNo,'status'=>'Freeze'];
        $balanceInfo = $BalanceAdjustment->where($where)->first();
        if(!$balanceInfo) {
            $redis->del($lockRequest); //删除锁
            return $response->withJson([
                'result' => '未有此待解冻信息',
                'success' => 0,
            ]);
        }
      try{
          $db = $this->c->database;
          $logger = $this->c->logger;
          $db->getConnection()->beginTransaction();
          $content = [
              'sourceId' => 1,
              'bankrollType' => $balanceInfo->bankrollType,
              'bankrollDirection' => 'Unfreeze',
              'amount' => $balanceInfo->amount,
              'summary' => $balanceInfo->summary,
              'merchantId' => $balanceInfo->merchantId,
              'platformOrderNo' => $balanceInfo->platformOrderNo,
              'type' => '余额调整-解冻',
          ];
          SystemCheckLog::insert( [
              'admin_id' => 0,
              'commiter_id' => $_SESSION['accountId'],
              'status' => '0',
              'content' => json_encode($content),
              'relevance' => $balanceInfo->merchantNo,
              'desc' => '',
              'ip' => Tools::getIp(),
              'ipDesc' => Tools::getIpDesc(),
              'type' => '余额调整',
              'created_at' => date('Y-m-d H:i:s', time()),
              'updated_at' => date('Y-m-d H:i:s', time()),
          ]);
          $balanceInfo->status = "Unaudit";
          $balanceInfo->bankrollDirection = "Unfreeze";
          $balanceInfo->save();

          SystemAccountActionLog::insert([
              'action' => 'CREATE_BALANCE_ADJUSTMENT',
              'actionBeforeData' => '',
              'actionAfterData' => $balanceInfo->toJson(),
              'status' => 'Success',
              'accountId' => $_SESSION['accountId'],
              'ip' => Tools::getIp(),
              'ipDesc' => Tools::getIpDesc(),
          ]);

          $db->getConnection()->commit();
          $redis->del($lockRequest); //删除锁
          return $response->withJson([
              'result' => '操作成功',
              'success' => 1,
          ]);
      }catch (\Exception $e) {
          $db->getConnection()->rollback();
          $logger->error('adjustment insert error' . $e->getMessage());
          $redis->del($lockRequest); //删除锁
          return $response->withJson([
              'result' => '操作异常' . $e->getMessage(),
              'success' => 0,
          ]);
      }
    }

    // 商户余额调整新增余额审核信息
    public function insert(Request $request, Response $response, $args)
    {
        $logger = $this->c->logger;
        $logger->pushProcessor(function ($record) use ($request) {
            $record['extra']['a'] = 'balance';
            $record['extra']['i'] = Tools::getIp();
            $record['extra']['d'] = Tools::getIpDesc();
            $record['extra']['u'] = $request->getUri();
            $record['extra']['p'] = $request->getParams();
            return $record;
        });
        $logger->debug('商户余额调整'. $_SESSION['loginName']);

        global $app;
        $redis = $app->getContainer()->redis;

        $merchant = new Merchant;
        $bankrollType = $request->getParam('bankrollType', 'AccountBalance');
        $merchantNo = $request->getParam('merchantNo');
        $bankrollDirection = $request->getParam('bankrollDirection');//Restore:返还, Retrieve:追收, Freeze:冻结, Recharge:充值
        $amount = $request->getParam('amount');
        $summary = $request->getParam('summary');
        $random = $request->getParam('random');
        $balanceKey = "balanceRandom-".$_SESSION['accountId'].'-'.$random;//提交token
//        $applyPerson = $request->getParam('applyPerson');
        $applyPerson = $_SESSION['userName'];
//        $auditPerson = $request->getParam('auditPerson');

        $balanceOperation = $redis->get("balanceOperation".$merchantNo);
        if($balanceOperation) {
            return $response->withJson([
                'result' => '此商户正在申请余额调整',
                'success' => 0,
            ]);
        }
        $redis->setex("balanceOperation".$merchantNo, 30 , true);

        if(!$redis->get($balanceKey)) {
            $redis->del("balanceOperation".$merchantNo);
            $redis->del($balanceKey);
            return $response->withJson([
                'result' => '请通过正确的渠道申请',
                'success' => 0,
            ]);
        }
        $amount = intval($amount * 100) / 100;
        if ($amount <= 0) {
            $redis->del("balanceOperation".$merchantNo);
            $redis->del($balanceKey);
            return $response->withJson([
                'result' => '金额不正确',
                'success' => 0,
            ]);
        }
        $model = new BalanceAdjustment();
        if($bankrollDirection == 'Recharge'){
            $adjust_data = $model->where('merchantNo', $merchantNo)->where('bankrollDirection', 'Recharge')->orderBy('adjustmentId', 'desc')->first();
            if(!empty($adjust_data) && intval($adjust_data->amount) == $amount && (time()-strtotime($adjust_data->created_at) < 120)){
                $redis->del("balanceOperation".$merchantNo);
                $redis->del($balanceKey);
                return $response->withJson([
                    'result' => '该商户2分钟内提交了一笔相同金额的充值订单，请仔细审查！',
                    'success' => 0,
                ]);
            }
        }
        $data = $merchant->where('merchantNo', $merchantNo)->first();

        if (empty($data)) {
            $redis->del("balanceOperation".$merchantNo);
            $redis->del($balanceKey);
            return $response->withJson([
                'result' => '商户不存在',
                'success' => 0,
            ]);
        } else {
            $db = $this->c->database;
            $logger = $this->c->logger;
            try {
                $db->getConnection()->beginTransaction();
                $merchantAmount = new MerchantAmount;
                $merchantAmountData = $merchantAmount->where('merchantId', $data->merchantId)->lockForUpdate()->first();

                if($merchantAmountData->settlementAmount - $amount < 0 && $bankrollDirection != 'Restore' && $bankrollDirection != 'Recharge') {
                    $db->getConnection()->rollback();
                    $redis->del("balanceOperation".$merchantNo);
                    $redis->del($balanceKey);
                    return $response->withJson([
                        'result' => '可用金额不足',
                        'success' => 0,
                    ]);
                }

                if($bankrollDirection == 'Recharge'){
                    $platformOrderNo = Tools::getPlatformOrderNo('R');
                }else{
                    $platformOrderNo = Tools::getPlatformOrderNo('B');
                }

                $accountDate = date('Ymd');
                $model->bankrollType = $bankrollType;
                $model->bankrollDirection = $bankrollDirection;
                $model->merchantId = $data->merchantId;
                $model->merchantNo = $merchantNo;
                $model->amount = $amount;
                $model->summary = $summary;
//                $model->applyTime = date('YmdHis');
                $model->applyPerson = $applyPerson;
//                $model->auditPerson = '';
                $model->platformOrderNo = $platformOrderNo;
//                $model->auditTime = date('YmdHis');
                $model->status = 'Unaudit';
                if($bankrollDirection == "Freeze") {
                    $model->status = "Freeze";
                }
                $id = $model->save();

                SystemAccountActionLog::insert([
                    'action' => 'CREATE_BALANCE_ADJUSTMENT',
                    'actionBeforeData' => '',
                    'actionAfterData' => $model->toJson(),
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);

                if($bankrollDirection != 'Freeze') {
                    //审核
                    $content = [
                        'sourceId' => $id,
                        'bankrollType' => $bankrollType,
                        'bankrollDirection' => $bankrollDirection,
                        'amount' => $amount,
                        'summary' => $summary,
                        'merchantId' => $model->merchantId,
                        'platformOrderNo' => $model->platformOrderNo,
                        'sysFee' => $bankrollDirection == 'Recharge' ? $request->getParam('sysFee') : 0,
                        'factFee' => $bankrollDirection == 'Recharge' ? $request->getParam('factFee') : 0,
                        'type' => '余额调整-' . ($model->bankrollDirection == 'Retrieve' ? '追收' : '返还'),
                    ];
                    SystemCheckLog::insert( [
                        'admin_id' => 0,
                        'commiter_id' => $_SESSION['accountId'],
                        'status' => '0',
                        'content' => json_encode($content),
                        'relevance' => $model->merchantNo,
                        'desc' => '',
                        'ip' => Tools::getIp(),
                        'ipDesc' => Tools::getIpDesc(),
                        'type' => '余额调整',
                        'created_at' => date('Y-m-d H:i:s', time()),
                        'updated_at' => date('Y-m-d H:i:s', time()),
                    ]);
                }

                if($bankrollDirection == 'Recharge'){//生成充值订单
                    //代理手续费
                    $agentId = AgentMerchantRelation::where('merchantId', $data->merchantId)->value('agentId');
                    if($agentId) {
                        $agentLog = new AgentIncomeLog();
                        //代付订单类型只有一种
                        $agentFee = $agentLog->getFee($agentId, $data->merchantId, $platformOrderNo, $amount,'recharge', 'EnterpriseAlipay');
                        $agentName = Agent::where('id', $agentId)->value('loginName');
                    }else {
                        $agentFee = 0;
                        $agentName = '';
                    }

                    $merchantRate = new MerchantRate();
                    $merchantRateConfig = $merchantRate->where('merchantNo', $merchantNo)
                        ->where('status','Normal')
                        ->where('payType', 'EnterpriseAlipay')->first();
                    $rechargeOrder = new PlatformRechargeOrder();
                    $rechargeOrder->platformOrderNo = $platformOrderNo;
                    $rechargeOrder->merchantNo = $merchantNo;
                    $rechargeOrder->merchantId = $data->merchantId;
                    $rechargeOrder->channelMerchantId = 0;
                    $rechargeOrder->channelMerchantNo = 0;
                    $rechargeOrder->orderAmount = $amount;
                    $rechargeOrder->realOrderAmount = $amount;
                    $rechargeOrder->serviceCharge = $request->getParam('factFee');//实际手续费
                    $rechargeOrder->channelServiceCharge = 0;
                    $rechargeOrder->channel = 'alipay';
                    $rechargeOrder->channelSetId = 0;
                    $rechargeOrder->orderStatus = 'Transfered';
                    $rechargeOrder->payType = 'EnterpriseAlipay';
                    $rechargeOrder->orderReason = $summary;
                    $rechargeOrder->agentFee = $agentFee;
                    $rechargeOrder->agentName = $agentName;
                    $merchantRateConfigTemp['rateType'] = $merchantRateConfig->rateType;
                    $merchantRateConfigTemp['rate'] = (string)$merchantRateConfig->rate;
                    $merchantRateConfigTemp['fixed'] = (string)$merchantRateConfig->fixed;
                    $rechargeOrder->rateTemp = json_encode(['merchant'=>$merchantRateConfigTemp,'sysFee'=>$request->getParam('sysFee'),'factFee'=>$request->getParam('factFee')]);
                    $rechargeOrder->save();
                }

                //冻结跟追收奖金相应发生变化，返还审核后才发生
                $merchantAmountData->settlementAmount = ($model->bankrollDirection == 'Retrieve' || $model->bankrollDirection == 'Freeze') ?
                $merchantAmountData->settlementAmount - $amount :
                $merchantAmountData->settlementAmount;
                if($bankrollDirection == "Freeze") {
                    $merchantAmountData->freezeAmount = $merchantAmountData->freezeAmount + $amount;
                }
//                $merchantAmountData->settlementAmount + $amount;
                $merchantAmountData->save();

                if($bankrollDirection != 'Restore' && $bankrollDirection != 'Recharge') {
                    Finance::insert([
                        'merchantId' => $model->merchantId,
                        'merchantNo' => $model->merchantNo,
                        'platformOrderNo' => $model->platformOrderNo,
                        'amount' => $amount,
                        'balance' => $merchantAmountData->settlementAmount,
                        'financeType' => 'PayOut',
                        'accountDate' => $accountDate,
                        'accountType' => $model->bankrollType == 'ServiceCharge' ? 'ServiceChargeAccount' : 'SettlementAccount',
                        'sourceId' => $id,
                        'sourceDesc' => '余额调整-' . ($model->bankrollDirection == 'Retrieve' ? '追收' : '冻结') . '-' . ($model->bankrollType == 'ServiceCharge' ? '手续费' : '账户资金'),
                        'operateSource' => 'admin',
                        'summary' => $summary,
                    ]);

                    AmountPay::where('merchantId', $model->merchantId)
                        ->where('accountDate', $accountDate)
                        ->update(['balance' => $merchantAmountData->settlementAmount]);
                }

                $db->getConnection()->commit();
                $merchantAmountData->refreshCache(['merchantId' => $model->merchantId]);
            } catch (\Exception $e) {
                $redis->del("balanceOperation".$merchantNo);
                $redis->del($balanceKey);
                $db->getConnection()->rollback();
                $logger->error('adjustment insert error' . $e->getMessage());
                return $response->withJson([
                    'result' => '操作异常:' . $e->getMessage(),
                    'success' => 0,
                ]);
            }
            $redis->del("balanceOperation".$merchantNo);
            $redis->del($balanceKey);
            return $response->withJson([
                'result' => '操作成功',
                'success' => 1,
            ]);
        }
    }

    public function getbasedata(Request $request, Response $response, $args)
    {
        $code = $this->c->code['bankrollDirection'];
        unset($code['Unfreeze']);
        $returnCode = [];
        foreach ($code as $k => $v) {
            $returnCode[] = ['key'=>$k, 'value'=>$v];
        }
        return $response->withJson([
            'code' => $returnCode,
            'success' => 1,
        ]);
    }

    // 获取充值费率
    public function getRechargeRate(Request $request, Response $response, $args){
        $merchantNo = $request->getParam('merchantNo');
        $amount = $request->getParam('amount');
        if(empty($amount)){
            return $response->withJson([
                'result' => '充值金额不能为0',
                'success' => 0,
            ]);
        }
        $merchant = new Merchant;
        $data = $merchant->where('merchantNo', $merchantNo)->first();
        if(empty($data)){
            return $response->withJson([
                'result' => '商户信息不存在',
                'success' => 0,
            ]);
        }
        $merchantRate = new MerchantRate();
        $rate = $merchantRate->where('merchantNo', $merchantNo)
            ->where('status','Normal')
            ->where('payType', 'EnterpriseAlipay')->first();
        if(empty($rate)){
            return $response->withJson([
                'result' => '请先配置商户充值费率',
                'success' => 0,
            ]);
        }
//        $rateFee = $rate->fixed + ($amount * $rate->rate);
        $rateFee = bcadd($rate->fixed, bcmul($amount, (string)$rate->rate, 3), 2);
//        $rateFee = intval(100 * (string)$rateFee);
        return $response->withJson([
//            'result' => sprintf('%.2f', $rateFee/100),
            'result' => $rateFee,
            'success' => 1,
        ]);
    }


    // public function detail(Request $request, Response $response, $args)
    // {
    //     return $this->c->view->render($response, 'gm/settlementorder/detail.twig', [
    //         'appName' => $this->c->settings['app']['name'],
    //         'userName' => $_SESSION['userName'] ?? null,
    //     ]);
    // }
}
