<?php
namespace App\Queues;

use App\Channels\ChannelProxy;
use App\Helpers\Tools;
use App\Models\ChannelMerchant;
use App\Models\ChannelMerchantRate;
use App\Models\Merchant;
use App\Models\MerchantChannelSettlement;
use App\Models\PlatformSettlementOrder;
use App\Models\SettlementFetchTask;
use App\Queues\SettlementActiveQueryExecutor;
use App\Queues\SettlementNotifyExecutor;

class SettlementFetchExecutor extends Executor
{
    protected $queueName = 'settlementfetch:queue';

    protected $maxRetryCount = 10;

    public function push($taskId, $platformOrderNo)
    {
        if ($taskId == 0) {
            $task = SettlementFetchTask::create([
                'platformOrderNo' => $platformOrderNo,
            ]);
            $data['taskId'] = $task->id;
        } else {
            $data['taskId'] = $taskId;
        }

        $data['platformOrderNo'] = $platformOrderNo;
        $lpRes = $this->redis->lpush($this->queueName, json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->logger->debug($this->queueName . ',lpRes:' . $lpRes . ',platformOrderNo:' . $data['platformOrderNo'] . ',taskId:' . $data['taskId']);
    }

    public function pop()
    {
        global $app;
        $code = 'SUCCESS';
        $data = $this->redis->rpop($this->queueName);
//        $this->logger->debug("line++++++++++++39++", $data);

        $this->refreshLastExecutorTime();
        $data = $data ? json_decode($data, true) : [];
        if (empty($data)) {
            return;
        }
        $channels = $app->getContainer()->code['channel'];
        $platformPayOrder = new PlatformSettlementOrder;
        $orderData = $platformPayOrder->getCacheByPlatformOrderNo($data['platformOrderNo']);
        // $this->logger->debug("line++++++++++++49++", $orderData);
        $taskData = null;
        for ($i = 0; $i < 5; $i++) {
            $taskData = SettlementFetchTask::where('id', $data['taskId'])->where('status', 'Execute')->first();
            if (!empty($taskData)) {
                $this->logger->debug($this->queueName . ',settlement fetch task count:' . $i . ',platformOrderNo:' . $data['platformOrderNo'] . ',taskId:' . $data['taskId']);
                break;
            }

            usleep(500000); //0.5秒
        }
        // $this->logger->debug("line++++++++++++51++", $taskData);
        try {
            if (empty($taskData)) {
                $this->logger->error($this->queueName . ':empty taskData, taskId:' . $data['taskId']);
                return;
            }

            if (empty($orderData)) {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Fail',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单不存在',
                ]);
                return;
            }

            if (in_array($orderData['orderStatus'], ['Success', 'Fail', 'Exception'])) {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => 'Success',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => '订单已完成-2',
                ]);
                return;
            }

            $lockKey = $this->queueName . ":" . $data['platformOrderNo'];

            // $this->logger->debug("line++++++++77", $this->redis->incr($lockKey));
            if ($this->redis->incr($lockKey) > 1) {
                throw new \Exception("任务锁定:" . $data['platformOrderNo']);
            } else {
                $this->redis->expire($lockKey, 180);
            }

            $merchant = new Merchant();
            $merchantData = $merchant->getCacheByMerchantNo($orderData['merchantNo']);

            $code = 'SUCCESS';
            $merchantChannel = new MerchantChannelSettlement();
            $merchantChannelData = $merchantChannel->getCacheByMerchantNo($orderData['merchantNo']);
            if (empty($merchantChannelData)) {
                $code = 'E2003';
            }

            if ($code == 'SUCCESS') {
                $merchantChannelConfig = $merchantChannel->fetchConfig($orderData['merchantNo'], $merchantChannelData, $settlementType = '', $orderData['orderAmount'], \App\Helpers\Tools::decrypt($orderData['bankAccountNo']), $channels, $orderData['bankCode']);

                if (empty($merchantChannelConfig)) {
                    $code = 'E2003';
                    $this->logger->debug($data['platformOrderNo'].":merchantChannelSettelement fetchConfig失败", $merchantChannelData);
                }
            }
            $model = new MerchantChannelSettlement();
            if ($code == 'SUCCESS') {
                $channelMerchantRate = new ChannelMerchantRate;
                $channelOrder = null;
                shuffle($merchantChannelConfig);
                foreach ($merchantChannelConfig as $channelConfig) {
                    $orderData['channel'] = $channelConfig['channel'];
                    $orderData['channelMerchantId'] = $channelConfig['channelMerchantId'];
                    $orderData['channelMerchantNo'] = $channelConfig['channelMerchantNo'];

                    $channelMerchantRateData = $channelMerchantRate->getCacheByChannelMerchantId($channelConfig['channelMerchantId']);

                    $orderData['channelServiceCharge'] = $channelMerchantRate->getServiceCharge($channelMerchantRateData, $orderData, 'Settlement');

                    if ($orderData['channelServiceCharge'] === null) {
                        continue;
                    }

                    $channelOrder = (new ChannelProxy)->getSettlementOrder($orderData);
                    $this->logger->debug($orderData['platformOrderNo'].'-'.$channelConfig['channelMerchantNo'].' channel status:', $channelOrder);
                    //第三方代付异常，账户异常下次不能代付
                    if ($channelOrder['status'] == 'Exception' && $channelOrder['failReason'] == '支付宝账号异常') {
                        //更新当前上游渠道状态剔除循环列表中
                        $cmModel = new ChannelMerchant();
                        $data = $cmModel->where('channelMerchantId', $channelConfig['channelMerchantId'])->first();
                        $data->status = 'Exception';
                        $data->save();
                        //刷新缓存
                        $cmModel->refreshCache(['channelMerchantNo' => $channelConfig['channelMerchantNo']]);
                    }

                    if (in_array($channelOrder['status'],['Success','DirectSuccess','Exception'])) {
                        break;
                    }
                }

                if ($orderData['channelServiceCharge'] === null) {
                    $code = 'E9001';
                    $this->logger->error($data['platformOrderNo'].':channelServiceCharge', $channelMerchantRateData);
                }

                if ($code == 'SUCCESS') {
                    $model = new PlatformSettlementOrder;
                    if ($channelOrder['status'] == 'Success') {
                        if ($model->start($orderData, $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $channelOrder['orderNo'], $orderData['channelServiceCharge'])) {
                            $orderData['channel'] != 'InnerChannel' && (new SettlementActiveQueryExecutor)->push(0, $orderData['platformOrderNo']);

                            SettlementFetchTask::where('id', $data['taskId'])->update([
                                'status' => 'Success',
                                'retryCount' => $taskData->retryCount + 1,
                                'failReason' => '',
                            ]);
                        } else {
                            //处理start失败，仍然查询代付订单
                            $orderData['channel'] != 'InnerChannel' && (new SettlementActiveQueryExecutor)->push(0, $orderData['platformOrderNo']);

                            SettlementFetchTask::where('id', $data['taskId'])->update([
                                'status' => 'Fail',
                                'retryCount' => $taskData->retryCount + 1,
                                'failReason' => '处理start失败',
                            ]);
                        }
                    } else if ($channelOrder['status'] == 'DirectSuccess') {
                        if ($model->directSuccess($orderData, $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $channelOrder['orderNo'], $orderData['channelServiceCharge'])) {
                            //发送通知给调用方
                            if (!empty($orderData['backNoticeUrl'])) {
                                (new SettlementNotifyExecutor)->push(0, $orderData['platformOrderNo']);
                            }
                            SettlementFetchTask::where('id', $data['taskId'])->update([
                                'status' => 'Success',
                                'retryCount' => $taskData->retryCount + 1,
                                'failReason' => '',
                            ]);
                        } else {
                            //处理失败，仍然查询代付订单
                            $orderData['channel'] != 'InnerChannel' && (new SettlementActiveQueryExecutor)->push(0, $orderData['platformOrderNo']);

                            SettlementFetchTask::where('id', $data['taskId'])->update([
                                'status' => 'Fail',
                                'retryCount' => $taskData->retryCount + 1,
                                'failReason' => '处理directSuccess失败',
                            ]);
                        }
                    }  else {
                        $orderData['channelOrderNo'] = $orderData['channelOrderNo'] ?? $channelOrder['orderNo'];
                        if ($model->fail($orderData, $processType = 'Success', $channelOrder['orderNo'], $failReason = ('自动处理-' . $channelOrder['failReason']), $channelNoticeTime = '', $auditPerson = '',
                            $orderData['channel'], $orderData['channelMerchantId'], $orderData['channelMerchantNo'], $orderData['channelServiceCharge'])) {
                            //发送通知给调用方
                            if (!empty($orderData['backNoticeUrl'])) {
                                (new SettlementNotifyExecutor)->push(0, $orderData['platformOrderNo']);
                            }

                            SettlementFetchTask::where('id', $data['taskId'])->update([
                                'status' => 'Fail',
                                'retryCount' => $taskData->retryCount + 1,
                                'failReason' => '请求第三方返回代付失败:' . $channelOrder['failReason'],
                            ]);
                        } else {
                            SettlementFetchTask::where('id', $data['taskId'])->update([
                                'status' => 'Fail',
                                'retryCount' => $taskData->retryCount + 1,
                                'failReason' => '处理fail失败' . $channelOrder['failReason'],
                            ]);
                        }
                    }
                }
            }

            if ($code != 'SUCCESS') {
                SettlementFetchTask::where('id', $data['taskId'])->update([
                    'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                    'retryCount' => $taskData->retryCount + 1,
                    'failReason' => $code,
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error($this->queueName . ':' . $e->getMessage());
            SettlementFetchTask::where('id', $data['taskId'])->update([
                'status' => $taskData->retryCount + 1 >= $this->maxRetryCount ? 'Fail' : 'Execute',
                'retryCount' => $taskData->retryCount + 1,
                'failReason' => 'error:' . $e->getMessage(),
            ]);
        }
    }
}
