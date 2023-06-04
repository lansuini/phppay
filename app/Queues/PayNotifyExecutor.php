<?php
namespace App\Queues;

use App\Helpers\Tools;
use App\Models\Merchant;
use App\Models\PayNotifyTask;
use App\Models\PlatformPayOrder;
use Requests;

class PayNotifyExecutor extends Executor
{
    protected $queueName = 'paynotify:queue';

    protected $maxRetryCount = 2;

    public function push($taskId, $platformOrderNo)
    {
        $data = [];
        if ($taskId == 0) {
            $task = PayNotifyTask::create([
                'platformOrderNo' => $platformOrderNo,
            ]);
            $data['taskId'] = $task->id;
        } else {
            $data['taskId'] = $taskId;
        }

        $data['platformOrderNo'] = $platformOrderNo;
        $this->redis->lpush($this->queueName, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function pop()
    {
        global $app;
        $data = $this->redis->rpop($this->queueName);
        $this->refreshLastExecutorTime();
        $data = $data ? json_decode($data, true) : [];

        if (empty($data)) {
            return;
        }
        // $this->logger->error('record', $data);
        $platformPayOrder = new PlatformPayOrder;
        $orderData = $platformPayOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        $taskData = PayNotifyTask::where('id', $data['taskId'])->where('status', 'Execute')->first();
        $db = $app->getContainer()->database;

        if (empty($taskData)) {
            return;
        }

        if (empty($orderData)) {
            PayNotifyTask::where('id', $data['taskId'])->update([
                'status' => 'Fail',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '订单不存在',
            ]);
            return;
        }

        if (empty($orderData['backNoticeUrl'])) {
            PayNotifyTask::where('id', $data['taskId'])->update([
                'status' => 'Fail',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '回调地址为空',
            ]);
            return;
        }

        try {

            $biz = [
                'merchantNo' => $orderData['merchantNo'],
                'merchantOrderNo' => $orderData['merchantOrderNo'],
                'platformOrderNo' => $orderData['platformOrderNo'],
                'orderStatus' => $orderData['orderStatus'],
                'orderAmount' => $orderData['realOrderAmount'],
                'merchantParam' => $orderData['merchantParam'],
            ];
            $merchant = new Merchant;
            $merchantData = $merchant->getCacheByMerchantId($orderData['merchantId']);
            $sign = Tools::getSign($biz, $merchantData['signKey']);
            $reqData = [
                'code' => 'SUCCESS',
                'msg' => $app->getContainer()->code['status']['SUCCESS'],
                'sign' => $sign,
                'biz' => $biz,
            ];
            $req = Requests::post($orderData['backNoticeUrl'], ['Content-Type' => 'application/json'], json_encode($reqData), ['timeout' => 15,'verify' => false]);
            if ($req->status_code == 200 && trim($req->body) == 'SUCCESS') {

                $db->getConnection()->beginTransaction();
                PayNotifyTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '',
                ]);

                $orderData['callbackSuccess'] = true;
                $orderData['callbackLimit'] = $orderData['callbackLimit'] + 1;
                PlatformPayOrder::where('orderId', $orderData['orderId'])->update([
                    'callbackSuccess' => $orderData['callbackSuccess'],
                    'callbackLimit' => $orderData['callbackLimit'],
                ]);
                // dump($orderData);
                $platformPayOrder->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderData);
                $db->getConnection()->commit();
            } else {
                throw new \Exception("c=" . $req->status_code . ", b=" . trim($req->body));
            }
        } catch (\Exception $e) {
            $db->getConnection()->rollback();
            $this->logger->error($data['platformOrderNo'] . ':' . $this->queueName . ':' . $e->getMessage());
            PayNotifyTask::where('id', $data['taskId'])->update([
                'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => '订单通知失败:' . $e->getMessage(),
            ]);

            $orderData['callbackLimit'] = $orderData['callbackLimit'] + 1;
            PlatformPayOrder::where('orderId', $orderData['orderId'])->update([
                'callbackLimit' => $orderData['callbackLimit'],
            ]);
            $platformPayOrder->setCacheByPlatformOrderNo($orderData['platformOrderNo'], $orderData);
        }
    }
}
