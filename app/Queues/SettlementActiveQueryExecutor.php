<?php
namespace App\Queues;

use App\Channels\ChannelProxy;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementActiveQueryTask;

class SettlementActiveQueryExecutor extends Executor
{

    protected $maxRetryCount = 10;

    protected $queueName = 'settlementactivequery:queue';

    public function push($taskId, $platformOrderNo)
    {
        if ($taskId == 0) {
            $task = SettlementActiveQueryTask::create([
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
        $data = $this->redis->rpop($this->queueName);
        $len = $this->redis->llen($this->queueName);
        $this->refreshLastExecutorTime();
        $data = $data ? json_decode($data, true) : [];
        if (empty($data)) {
            return;
        }
        $stime = time();
        $this->logger->info("代付订单上游同步： {$this->queueName} 待同步个数 ：{$len}  -- 当前查询订单信息", $data);
        $platformSettlementOrder = new PlatformSettlementOrder;
        $orderData = $platformSettlementOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        $taskData = SettlementActiveQueryTask::where('id', $data['taskId'])->where('status', 'Execute')->first();

        try {
            if (empty($taskData) || empty($orderData)) {
                return;
            }

            if ($orderData['orderStatus'] == 'Success' || $orderData['orderStatus'] == 'Fail') {
                SettlementActiveQueryTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单已处理',
                ]);

                return;
            }
            if( (time() - strtotime($orderData['created_at'])) < 120){//2分钟内的订单不支持查询
//                sleep(40);
                return false;
            }
            $lock_key = $this->queueName . ":" . $data['platformOrderNo'];
            $lock = $this->redis->setnx($lock_key,1);
            if (!$lock) {
                throw new \Exception("任务锁定:" . $lock_key);
            }
            $this->redis->expire($lock_key, 60);

            $channelOrder = (new ChannelProxy)->querySettlementOrder($data['platformOrderNo']);
            $time = time() - $stime;
            $channelOrder['platformOrderNo'] = $data['platformOrderNo'];
            $this->logger->error("上游查询时间：{$time}秒  代付订单上游 -- 同步结果", $channelOrder);
            if ($channelOrder['status'] == 'Success' && $platformSettlementOrder->success($orderData, $channelOrder['orderAmount'], 'Success', $channelOrder['orderNo'], '主动查询')) {
                SettlementActiveQueryTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单状态orderStataus=Success',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $data['platformOrderNo']);
                }
            } else if ($channelOrder['status'] == 'Fail'
                && $platformSettlementOrder->fail($orderData, 'Success', $channelOrder['orderNo'], '主动查询-' . ($channelOrder['failReason'] ?? ''), $channelNoticeTime = '', $auditPerson = '', $channel = '',
                    $channelMerchantId = '', $channelMerchantNo = '', $channelServiceCharge = 0)) {
                SettlementActiveQueryTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单状态orderStataus=Fail',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $data['platformOrderNo']);
                }
            }else if($channelOrder['status'] == 'Execute' && $taskData->retryCount < $this->maxRetryCount) {
//                sleep($taskData->retryCount * 2);   //处理中 按查询次数2秒过后再丢队列重新查询
//                $this->push($data['taskId'],$data['platformOrderNo']);
                SettlementActiveQueryTask::where('id', $data['taskId'])->update([
//                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'status' => 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单处理中:' . json_encode($channelOrder, JSON_UNESCAPED_UNICODE),
                ]);
            }else {
//                $this->push($data['taskId'],$data['platformOrderNo']);
                SettlementActiveQueryTask::where('id', $data['taskId'])->update([
//                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'status' => 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单处理失败:' . json_encode($channelOrder, JSON_UNESCAPED_UNICODE),
                ]);
            }
            $time = time() - $stime;
            $this->logger->info("执行时间：{$time}秒  代付订单上游 -- 同步结果", $channelOrder);
            $this->redis->del($lock_key);
        } catch (\Exception $e) {
            $this->logger->info("代付查询队列执行错误：{$e->getMessage()}", $channelOrder);
            if(strpos($e->getMessage(),'任务锁定') === false) {
                isset($lock_key) && $this->redis->del($lock_key);
                SettlementActiveQueryTask::where('id', $data['taskId'])->update([
//                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'status' => 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单处理失败:' . $e->getMessage(),
                ]);
            }
        }
    }

    public function syncSettlementOrder($platformOrderNo,$taskId = 0)
    {
        $taskId = $taskId == 0 ? SettlementActiveQueryTask::where('platformOrderNo',$platformOrderNo)->orderBy('id')->value('id') : $taskId;
        $platformSettlementOrder = new PlatformSettlementOrder;
        $orderData = $platformSettlementOrder->getCacheByPlatformOrderNo($platformOrderNo);
        $taskData = SettlementActiveQueryTask::where('id', $taskId)->where('status', 'Execute')->first();
        $taskData->retryCount = $taskData->retryCount ?? 0;
        $msg = ['msg'=>"开始查询:taskID--" . $taskId];
        try {
            $lock_key =  $this->queueName . ":hands:" . $platformOrderNo;
            $lock = $this->redis->setnx($lock_key,1);
            if (!$lock) {
                throw new \Exception("任务锁定:" . $platformOrderNo);
            }
            $channelOrder = (new ChannelProxy)->querySettlementOrder($platformOrderNo);

            if ($channelOrder['status'] == 'Success' && $platformSettlementOrder->success($orderData, $channelOrder['orderAmount'], 'Success', $channelOrder['orderNo'], '手动查询')) {
                SettlementActiveQueryTask::where('id', $taskId)->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单状态orderStataus=Success',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $platformOrderNo);
                }
            } else if ($channelOrder['status'] == 'Fail'
                && $platformSettlementOrder->fail($orderData, 'Success', $channelOrder['orderNo'], '主动查询-' . ($channelOrder['failReason'] ?? ''), $channelNoticeTime = '', $auditPerson = '', $channel = '',
                    $channelMerchantId = '', $channelMerchantNo = '', $channelServiceCharge = 0)) {
                SettlementActiveQueryTask::where('id', $taskId)->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单状态orderStataus=Fail',
                ]);

                if (!empty($orderData['backNoticeUrl'])) {
                    (new SettlementNotifyExecutor)->push(0, $platformOrderNo);
                }
            } else {
                SettlementActiveQueryTask::where('id', $taskId)->update([
                    'status' => 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单处理中:' . json_encode($channelOrder, JSON_UNESCAPED_UNICODE),
                ]);
            }
            $this->redis->del($lock_key);
            return $channelOrder;
        } catch (\Exception $e) {
            SettlementActiveQueryTask::where('id', $taskId)->update([
                'status' => 'Execute',
                'retryCount' => 1,
                'failReason' => '订单处理中:' . $e->getMessage(),
            ]);
            $this->redis->del($lock_key);
            return [
                "status" => "Execute",
                "failReason" => "订单处理中:查询异常".json_encode($msg, JSON_UNESCAPED_UNICODE),
                "orderAmount" => 0,
                "orderNo" => ""
            ];
        }
    }
}
