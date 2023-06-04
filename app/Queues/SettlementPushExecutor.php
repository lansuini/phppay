<?php
namespace App\Queues;

use App\Helpers\Tools;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementPushTask;

class SettlementPushExecutor extends Executor
{
    protected $queueName = 'settlementpush:queue';

    protected $maxRetryCount = 5;

    public function push($taskId, $platformOrderNo, $thirdParams, $standardParams)
    {
        if ($taskId == 0) {
            $task = SettlementPushTask::create([
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
        $data['thirdParams'] = $thirdParams;
        $data['standardParams'] = $standardParams;
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

        $platformSettlementOrder = new PlatformSettlementOrder;
        $orderData = $platformSettlementOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        $taskData = SettlementPushTask::where('id', $data['taskId'])->where('status', 'Execute')->first();

        try {
            if (empty($taskData)) {
                return;
            }

            $standardParams = json_decode($taskData->standardParams, true);
            if (empty($orderData)) {
                SettlementPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单不存在',
                ]);

                return;
            }

            if ($orderData['orderStatus'] == 'Success' || $orderData['orderStatus'] == 'Fail') {
                SettlementPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单已处理',
                ]);

                return;
            }

            $lockKey = $this->queueName . ":" . $data['platformOrderNo'];
            if ($this->redis->incr($lockKey) > 1) {
                throw new \Exception("任务锁定:" . $data['platformOrderNo']);
            } else {
                $this->redis->expire($lockKey, 15);
            }

            if ($standardParams['status'] == 'Success'
                && $standardParams['orderStatus'] == 'Success'
                && $platformSettlementOrder->success($orderData, $standardParams['orderAmount'], 'Success', $standardParams['orderNo'], '回调通知', $channelNoticeTime = '',
                    $auditPerson = '', (isset($standardParams['channelServiceCharge']) ? $standardParams['channelServiceCharge'] : 0))) {
                SettlementPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单状态orderStataus=Success',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $data['platformOrderNo']);
                }
            } else if ($standardParams['status'] == 'Success'
                && $standardParams['orderStatus'] == 'Fail'
                && $platformSettlementOrder->fail($orderData, 'Success', $standardParams['orderNo'], '回调通知-' . ($standardParams['failReason'] ?? ''), $channelNoticeTime = '', $auditPerson = '', $channel = '',
                    $channelMerchantId = '',
                    $channelMerchantNo = '',
                    (isset($standardParams['channelServiceCharge']) ? $standardParams['channelServiceCharge'] : 0))) {
                SettlementPushTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单状态orderStataus=Fail',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $data['platformOrderNo']);
                }
            } else {
                SettlementPushTask::where('id', $data['taskId'])->update([
                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => substr('订单处理失败:' . $platformSettlementOrder->getErrorMessage(),0,255),
                ]);
            }
        } catch (\Exception $e) {
            SettlementPushTask::where('id', $data['taskId'])->update([
                'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => substr('订单处理失败:' . $e->getMessage(),0, 255),
            ]);
        }
    }
}
