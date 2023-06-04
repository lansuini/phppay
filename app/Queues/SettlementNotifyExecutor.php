<?php
namespace App\Queues;

use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementNotifyTask;
use Requests;

class SettlementNotifyExecutor extends Executor
{
    protected $queueName = 'settlementnotify:queue:key';
    //开户队列个数
    protected $queueNameList = [
        'settlementnotify:queue' => 0,
        'settlementnotify:queue2' => 0,
        'settlementnotify:queue3' => 0,
        'settlementnotify:queue4' => 0,
        'settlementnotify:queue5' => 0,
        'settlementnotify:queue6' => 0,
    ];

    protected $maxRetryCount = 3;

    public function push($taskId, $platformOrderNo)
    {
        if ($taskId == 0) {
            $task = SettlementNotifyTask::create([
                'platformOrderNo' => $platformOrderNo,
            ]);
            $data['taskId'] = $task->id;
        } else {
            $data['taskId'] = $taskId;
        }

        $data['platformOrderNo'] = $platformOrderNo;

        //不需要回调的订单号不往队列里面丢
        $platformPayOrder = new PlatformSettlementOrder;
        $orderData = $platformPayOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        $merchant = new Merchant;
        $merchantData = $merchant->getCacheByMerchantId($orderData['merchantId']);

        if (isset($merchantData['openRepayNotice']) && $merchantData['openRepayNotice'] == 0) {
            return;
        }
        if (empty($orderData['backNoticeUrl'])) {
            return;
        }

        $queue = $this->redis->hGetAll($this->queueName);
        $queue = array_merge($this->queueNameList,$queue);
        asort($queue);
        foreach ($queue as $k=>$v) {
            $this->redis->lpush($k, json_encode($data, JSON_UNESCAPED_UNICODE));
            $l = $this->redis->llen($k);
            $this->redis->hSet($this->queueName, $k ,$l);
            break;
        }
    }

    public function pop($queueKey = 'settlementnotify:queue')
    {
        global $app;
        $db = $app->getContainer()->database;
        $data = $this->redis->rpop($queueKey);
        $len = $this->redis->llen($queueKey);
        $this->redis->hSet($this->queueName, $queueKey , $len );
        $this->refreshLastExecutorTime();

        $data = $data ? json_decode($data, true) : [];
        if (empty($data)) {
            return;
        }
        $this->logger->error("代付订单回调： {$queueKey} 待回调个数 ：{$len}  -- 当前回调订单信息", $data);

        $platformPayOrder = new PlatformSettlementOrder;

        $orderData = $platformPayOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        $taskData = SettlementNotifyTask::where('id', $data['taskId'])->where('status', 'Execute')->first();
        if (empty($taskData)) {
            return;
        }

        if (empty($orderData)) {
            SettlementNotifyTask::where('id', $data['taskId'])->update([
                'status' => 'Fail',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '订单不存在',
            ]);
            return;
        }

        if (empty($orderData['backNoticeUrl'])) {
            SettlementNotifyTask::where('id', $data['taskId'])->update([
                'status' => 'Fail',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '回调地址为空',
            ]);
            return;
        }

        $merchant = new Merchant;
        $merchantData = $merchant->getCacheByMerchantId($orderData['merchantId']);

        if (isset($merchantData['openRepayNotice']) && $merchantData['openRepayNotice'] == 0) {
            SettlementNotifyTask::where('id', $data['taskId'])->update([
                'status' => 'Success',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '下游回调开关关闭，以查询为主',
            ]);
            return;
        }

        try {
            $orderMsg = '';
            switch ($orderData['orderStatus']) {
                case 'WaitTransfer':
                case 'Transfered': $orderMsg = '代付中';break;
                case 'Success': $orderMsg = '代付成功';break;
                case 'Fail': $orderMsg = $orderData['failReason'];break;
            }
            $biz = [
                'merchantNo' => $orderData['merchantNo'],
                'merchantOrderNo' => $orderData['merchantOrderNo'],
                'platformOrderNo' => $orderData['platformOrderNo'],
                'orderStatus' => ($orderData['orderStatus'] == 'Exception' ? 'Transfered' : $orderData['orderStatus']),
                'orderAmount' => $orderData['orderAmount'],
                'orderMsg' => $orderMsg,
                'merchantParam' => $orderData['merchantParam'],
            ];

            $sign = Tools::getSign($biz, $merchantData['signKey']);
            $reqData = [
                'code' => 'SUCCESS',
                'msg' => $app->getContainer()->code['status']['SUCCESS'],
                'sign' => $sign,
                'biz' => $biz,
            ];
            $req = Requests::post($orderData['backNoticeUrl'], ['Content-Type' => 'application/json'], json_encode($reqData), ['timeout' => 10,'verify' => false]);
            $this->logger->error("{$orderData['merchantOrderNo']}回调订单通知：请求:".json_encode($reqData).',返回:'.trim($req->body));

            if ($req->status_code == 200 && trim($req->body) == 'SUCCESS') {
                $db->getConnection()->beginTransaction();
                SettlementNotifyTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '',
                ]);

                $orderData['callbackSuccess'] = true;
                $orderData['callbackLimit'] = $orderData['callbackLimit'] + 1;
                PlatformSettlementOrder::where('orderId', $orderData['orderId'])->update([
                    'callbackSuccess' => $orderData['callbackSuccess'],
                    'callbackLimit' => $orderData['callbackLimit'],
                ]);
                $platformPayOrder->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderData);
                $db->getConnection()->commit();
            } else {
//                if($taskData->retryCount + 1 < $this->maxRetryCount){
//                    $this->push($data['taskId'],$orderData['platformOrderNo']);
//                }else {
                    $this->logger->error("回调状态：".$req->status_code . ",回调内容:" .trim($req->body),$reqData);
                    throw new \Exception("c=" . $req->status_code.",{$orderData['merchantOrderNo']}回调订单通知：请求:".json_encode($reqData).",返回:".trim($req->body));
//                }
            }
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            $this->logger->error("回调异常：".json_encode($orderData).$e->getMessage());
            // $this->logger->error($this->queueName . ':' . $e->getMessage());
            // $orderData['callbackSuccess'] = true;
            $orderData['callbackLimit'] = $orderData['callbackLimit'] + 1;
            PlatformSettlementOrder::where('orderId', $orderData['orderId'])->update([
                // 'callbackSuccess' => $orderData['callbackSuccess'],
                'callbackLimit' => $orderData['callbackLimit'],
            ]);

            SettlementNotifyTask::where('id', $data['taskId'])->update([
                'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '订单通知失败:' . $e->getMessage(),
            ]);
        }
    }
}
