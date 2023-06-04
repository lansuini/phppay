<?php
namespace App\Queues;

use App\Helpers\Tools;
use App\Models\PayPushTask;
use App\Models\PlatformPayOrder;

class PayPushExecutor extends Executor
{
    protected $queueName = 'paypush:queue';

    protected $maxRetryCount = 5;
    //回调队列
    public function push($taskId, $platformOrderNo, $thirdParams, $standardParams)
    {
        if ($taskId == 0) {
            $task = PayPushTask::create([
                'ip' => Tools::getIp(),
                'ipDesc' => Tools::getIpDesc(),
                'thirdParams' => json_encode($thirdParams, JSON_UNESCAPED_UNICODE),
                'standardParams' => json_encode($standardParams, JSON_UNESCAPED_UNICODE),
                'platformOrderNo' => $platformOrderNo,
            ]);
            $data['taskId'] = $task->id;
        } else {
            $data['taskId'] = $taskId;
        }

        $data['platformOrderNo'] = $platformOrderNo;
        // $data['thirdParams'] = $thirdParams;
        // $data['standardParams'] = $standardParams;
        $this->redis->lpush($this->queueName, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public function pop()
    {
        $data = $this->redis->rpop($this->queueName);
        $this->refreshLastExecutorTime();
        $data = $data ? json_decode($data, true) : [];
        if (empty($data)) {
            return;
        }

        $platformPayOrder = new PlatformPayOrder;
        $orderData = $platformPayOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        $taskData = PayPushTask::where('id', $data['taskId'])->where('status', 'Execute')->first();
        try {

            if (empty($taskData)) {
                throw new \Exception("任务不存在:" . $data['taskId']);
            }
            $standardParams = json_decode($taskData->standardParams, true);

            if (empty($orderData)) {
                PayPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单不存在',
                ]);
                throw new \Exception("订单不存在:" . $data['platformOrderNo']);
            }

            if ($orderData['orderStatus'] == 'Success') {
                PayPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单已处理',
                ]);

                // if (!empty($orderData['backNoticeUrl'])) {
                //     (new PayNotifyExecutor)->push(0, $data['platformOrderNo']);
                // }

                throw new \Exception("订单已处理-:" . $data['platformOrderNo']);
            }

            $lockKey = $this->queueName . ":" . $data['platformOrderNo'];
            if ($this->redis->incr($lockKey) > 1) {
                $this->redis->expire($lockKey, 15);
                PayPushTask::where('id', $data['taskId'])->update([
                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '任务锁定',
                ]);
                throw new \Exception("任务锁定:" . $data['platformOrderNo']);
            } else {
                $this->redis->expire($lockKey, 15);
            }

            if (isset($standardParams['payType']) && !empty($standardParams['payType'])) {
                $orderData['payType'] = $standardParams['payType'];
            }

            if ($standardParams['status'] == 'Success' && $standardParams['orderStatus'] == 'Success' && $platformPayOrder->success($orderData, $standardParams['orderAmount'])) {
                PayPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new PayNotifyExecutor)->push(0, $data['platformOrderNo']);
                }
            } else {
                PayPushTask::where('id', $data['taskId'])->update([
                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单处理失败:' . $platformPayOrder->getErrorMessage(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error($this->queueName . ':' . $e->getMessage());
        }
    }
}
