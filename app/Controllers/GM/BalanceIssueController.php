<?php
/**
 * 代付余额查询记录和下发
 */
namespace App\Controllers\GM;

use App\Helpers\Tools;
use App\Models\ChannelBalanceQuery;
use App\Models\ChannelMerchant;
use App\Models\ChannelBalanceIssue;
use App\Models\SystemAccount;
use App\Models\SystemAccountActionLog;
use App\Logics\MerchantLogic;
use App\Models\SystemCheckLog;
use App\Models\SystemConfig;
use App\Queues\SettlementActiveQueryExecutor;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BalanceIssueController extends GMController{
    //渠道余额查询
    public function settlementBalance(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/balanceissue/settlementbalance.twig', [
            'role' => $_SESSION['role'],
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    //渠道余额查询接口
    public function settlementBalanceSearch(Request $request, Response $response, $args){
        $model = new ChannelMerchant();
        $bmodel = new ChannelBalanceQuery();
        $code = $this->c->code['channel'];
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $platformNo = $request->getParam('platformNo');//渠道号
        $channel = $request->getParam('channel');//渠道标识

        $platformNo && $model = $model->where('channelMerchantNo', $platformNo);
        $channel && $model = $model->where('channel', $channel);

        $model = $model->where([['status', 'Normal'],['channel', '<>', 'alipay']]);

        $total = $model->count();
        $data = $model->orderBy('channelMerchantId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $bdata = $bmodel->where('channelNo', $v->channelMerchantNo)->orderby('bId', 'desc')->first();
            if(empty($bdata)){
                $nv = [
                    "channelId" => $v->channelMerchantId,
                    "channelNo" => $v->channelMerchantNo,
                    "channel" => $v->channel,
                    "channelDesc" => $code[$v->channel]['name'] ?? '',
                    "channelBalance" => 0,
                    "merchantBalance" => 0,
                    "merchantCount" => 0,
                    "diffValue" => 0,
                    "insTime" => '',
                    "bId" => '',
                ];
            }else{
                $nv = [
                    "channelId" => $v->channelMerchantId,
                    "channelNo" => $v->channelMerchantNo,
                    "channel" => $v->channel,
                    "channelDesc" => $code[$v->channel]['name'] ?? '',
                    "channelBalance" => $bdata->channelBalance,
                    "merchantBalance" => $bdata->merchantBalance,
                    "merchantCount" => $bdata->merchantCount,
                    "diffValue" => $bdata->diffValue,
                    "insTime" => $bdata->created_at->format('Y-m-d H:i:s'),
                    "bId" => Tools::getHashId($bdata->bId),
                ];
            }
            $rows[] = $nv;
        }

        return $response->withJson([
            'result' => [],
            'rows' => $rows,
            'success' => 1,
            'total' => $total,
        ]);
    }

    //余额更新
    public function settlementBalanceUpdate(Request $request, Response $response, $args){
        $channelId = $request->getParam('channelId');
        $data = ChannelMerchant::where('channelMerchantId', $channelId)->first();
        if(empty($data)){
            return $response->withJson([
                'success' => 0,
                'msg' => '上游渠道信息不存在',
            ]);
        }else{
            $data = $data->toArray();
            $logic = new MerchantLogic($this->c);
            $logic->channelBalance($data);
            return $response->withJson([
                'success' => 1,
                'msg' => '更新成功',
            ]);
        }
    }

    //余额提现
    public function settlementBalanceWithdraw(Request $request, Response $response, $args){
        $bId = $request->getParam('bId');
        $bankCode = $request->getParam('bankCode');
        $cardNo = $request->getParam('cardNo');
        $cardNo = str_replace(' ', '', $cardNo);
        $userName = $request->getParam('userName');
        $userName = str_replace(' ', '', $userName);
        $money = $request->getParam('money');
        $money = str_replace(' ', '', $money);
        $success = 1;
        if($_SESSION['role'] != 5){
            $success = 0;
            $result = '您没有权限操作这个菜单';
        }
        if($success == 1 && empty($bId)){
            $success = 0;
            $result = 'bId不能为空';
        }
        $bId = Tools::getIdByHash($bId);
        $qData = ChannelBalanceQuery::where('bId', $bId)->first();
        if($success == 1 && empty($qData)){
            $success = 0;
            $result = '余额查询记录不存在';
        }
        $qData = $qData->toArray();
        //不限制提款金额
//        $diffMoney = $qData['diffValue'] * 2;
//        $maxMoney = $diffMoney > $qData['channelBalance'] ? $qData['channelBalance'] : $diffMoney;
//        if($success == 1 && $money > $maxMoney){
        if($success == 1 && $qData['diffValue'] < SystemConfig::where('module','withdraw')->where('key','limit')->value('value')){
            $success = 0;
            $result = '取款额度超过最高取款额度';
        }
        if($success){
            $issueOrderNo = Tools::getPlatformOrderNo('I');//提现订单
            try {
                $db = $this->c->database;
                $db->getConnection()->beginTransaction();
                $issueData = [
                    'issueOrderNo'=>$issueOrderNo,
                    'channelId'=>$qData['channelId'],
                    'channelNo'=>$qData['channelNo'],
                    'bankCode'=>$bankCode,
                    'cardNo'=>$cardNo,
                    'userName'=>$userName,
                    'issueAmount'=>$money,
                    'adminName'=>$_SESSION['loginName'],
                    'orderStatus'=>'WaitTransfer',
                ];
                ChannelBalanceIssue::insert($issueData);
                //日志记录发起代付
                SystemAccountActionLog::insert([
                    'action' => 'UPDATE_CHANNEL_MERCHANT',
                    'actionBeforeData' => json_encode($issueData),
                    'actionAfterData' => '',
                    'status' => 'Success',
                    'accountId' => $_SESSION['accountId'],
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                ]);
                $issueData['bankName'] = $this->c->code['bankCode'][$bankCode];
                //提交审核
                SystemCheckLog::insert( [
                    'admin_id' => 0,
                    'commiter_id' => $_SESSION['accountId'],
                    'status' => '0',
                    'content' => json_encode($issueData),
                    'relevance' => $issueOrderNo,
                    'desc' => '',
                    'ip' => Tools::getIp(),
                    'ipDesc' => Tools::getIpDesc(),
                    'type' => '渠道余额取款',
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'updated_at' => date('Y-m-d H:i:s', time()),
                ]);
                $db->getConnection()->commit();
                $result = "提交下发订单成功";
            } catch (\Exception $e) {
                $this->logger->error('Issue Exception:' . $e->getMessage());
                $db->getConnection()->rollback();
                $success = 0;
                $result = $e->getMessage();
            }
        }
        return $response->withJson([
            'success' => $success,
            'result' => $result,
        ]);
    }

    public function submitBalanceWithdraw(Request $request, Response $response, $args){
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

        $checkId = $request->getParam('id');
        $result = $request->getParam('result');
        $desc = $request->getParam('desc');
        if(!in_array($result,['unaudit','audit'])){
            return $response->withJson([
                'success' => 0,
                'result' => '类型错误！！！',
            ]);
        }
        $checkData = SystemCheckLog::find($checkId);
        if(!$checkData || $checkData && $checkData['status'] != 0){
            return $response->withJson([
                'success' => 0,
                'result' => '已处理过，请刷新',
            ]);
        }
        $withdraw = ChannelBalanceIssue::where('issueOrderNo',$checkData['relevance'])->first();
        $beforeAction = $withdraw->toArray();
        if(!$withdraw || $withdraw && $withdraw['orderStatus'] != 'WaitTransfer') {
            return $response->withJson([
                'success' => 0,
                'result' => '已处理过，请刷新2',
            ]);
        }
        try {
            $db = $this->c->database;
            $db->getConnection()->beginTransaction();
            $withdraw->orderStatus = $result == 'audit' ? 'Transfered' : 'Fail';
            $withdraw->save();
            $checkData->status = $result == 'audit' ? '1' : '2';
            $checkData->desc = $desc;
            $checkData->save();
            $logic = new MerchantLogic($this->c);
            //发起代付
            if($result == 'audit') { //通过提交取款
                $logic->channelSettlement(['issueOrderNo' => $withdraw['issueOrderNo'],
                    'channelId' => $withdraw['channelId'],
                    'channelNo' => $withdraw['channelNo'],
                    'bankCode' => $withdraw['bankCode'],
                    'cardNo' => $withdraw['cardNo'],
                    'userName' => $withdraw['userName'],
                    'issueAmount' => $withdraw['issueAmount']]);
            }
            //操作记录
            SystemAccountActionLog::insert([
                'action' => 'UPDATE_CHANNEL_MERCHANT',
                'actionBeforeData' => json_encode($beforeAction),
                'actionAfterData' => json_encode($withdraw->toArray()),
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
            'result' => $result,
        ]);
    }

    //余额查询记录
    public function settlementBalanceRecord(Request $request, Response $response, $args){
        $model = new ChannelBalanceQuery();
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $channelId = $args['channelId'];//渠道id

        $channelId && $model = $model->where('channelId', $channelId);

        $total = $model->count();
        $data = $model->orderBy('bId', 'desc')->offset($offset)->limit($limit)->get();
        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                "channelNo" => $v->channelNo,
                "channelBalance" => $v->channelBalance,
                "merchantBalance" => $v->merchantBalance,
                "merchantCount" => $v->merchantCount,
                "diffValue" => $v->diffValue,
                "insTime" => $v->created_at->format('Y-m-d H:i:s'),
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

    //下发记录
    public function issueRecord(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/balanceissue/issuerecord.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    //下发记录搜索
    public function issueRecordSearch(Request $request, Response $response, $args){
        $channels = $this->code['channel'];
        $banks = $this->code['bankCode'];
        $orderStatus = $this->code['settlementOrderStatus'];
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        $channel = $request->getParam('channel');
        $platformNo = $request->getParam('platformNo');

        $value = [];
        $where[] = '1=1';
        $channel && $where[] = "channel_merchant.channel=?";
        $channel && $value[] = $channel;
        $platformNo && $where[] = "channel_balance_issue.channelNo=?";
        $platformNo && $value[] = $platformNo;

        $whereStr = implode(' and ', $where);
        $total = \Illuminate\Database\Capsule\Manager::select("select count(issueId) as icount from channel_balance_issue
        left join channel_merchant on channel_balance_issue.channelId = channel_merchant.channelMerchantId
        WHERE {$whereStr}", $value);
        $total = current(current($total));

        $value[] = $limit;
        $value[] = $offset;
        $data = \Illuminate\Database\Capsule\Manager::select("select issueId, issueOrderNo, channelNo, bankCode, cardNo, userName, issueAmount, 
        adminName, orderStatus, channel_balance_issue.created_at, channel_balance_issue.updated_at, channel_merchant.channel from channel_balance_issue
        left join channel_merchant on channel_balance_issue.channelId = channel_merchant.channelMerchantId
        WHERE {$whereStr} order by issueId desc limit ? offset ?", $value);

        $rows = [];
        foreach ($data ?? [] as $k => $v) {
            $nv = [
                "issueId" => Tools::getHashId($v->issueId),
                'issueOrderNo' => $v->issueOrderNo,
                'channelNo' => $v->channelNo,
                'bankCode' => $banks[$v->bankCode] ?? "",
                'cardNo' => $v->cardNo,
                'userName' => $v->userName,
                'issueAmount' => $v->issueAmount,
                'adminName' => $v->adminName,
                'created_at' => $v->created_at,
                'updated_at' => $v->updated_at,
                'channel' => $channels[$v->channel]['name'],
                'orderStatus'=>$v->orderStatus,
                'orderStatusDes' => $orderStatus[$v->orderStatus],
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

    //下发订单状态
    public function orderQuery(Request $request, Response $response, $args){
        $issueId = $request->getParam('issueId');
        if(empty($issueId)){
            return $response->withJson([
                'success' => 0,
                'msg' => 'issueId不能为空',
            ]);
        }
        $issueId = Tools::getIdByHash($issueId);
        $data = ChannelBalanceIssue::where('issueId', $issueId)->first();
        if(empty($data)){
            return $response->withJson([
                'success' => 0,
                'msg' => '下发订单不存在',
            ]);
        }
        $logic = new MerchantLogic($this->c);
        $res = $logic->channelSettlementQuery($data->toArray());
        if($res['status'] == 'Success'){
            $status = 'Success';
            $msg = '提现成功';
            ChannelBalanceIssue::where('issueId', $issueId)->update(['orderStatus'=> $status]);
        }else if($res['status'] == 'Fail'){
            $status = 'Fail';
            $msg = $res['failReason'];
            ChannelBalanceIssue::where('issueId', $issueId)->update(['orderStatus'=> $status]);
        }else{
            $msg = $res['failReason'];
        }
        return $response->withJson([
            'success' => 1,
            'msg' => $msg,
        ]);
    }
}