<?php
/**
 * Created by PhpStorm.
 * User: benchan
 * Date: 2020/3/4
 * Time: 10:13
 */
namespace App\Queues;

use App\Models\PlatformNotify;
use App\Models\SystemAccount;
class PlatformNotifyExecutor extends Executor
{
    protected $queueName = 'platformnotify:queue';

    protected $maxRetryCount = 3;

    public function push($platform = 'gm', $type = 'risk',$content = '')
    {
        try {

            $data = [];
            $data['platform'] = $platform;
            $data['type'] = $type;
            $data['content'] = $content;
            $this->redis->lpush($this->queueName, json_encode($data, JSON_UNESCAPED_UNICODE));

        }catch (\Exception $e){
            $this->logger->error($this->queueName . ':' . $e->getMessage());
        }
    }



    public function pop()
    {
        $data = $this->redis->rpop($this->queueName);
        $this->refreshLastExecutorTime();
        $data = $data ? json_decode($data, true) : [];

        if (empty($data)) {
            return;
        }
        try{
            if($data['type'] == 'risk' && $data['platform'] == 'gm'){
                $systemAccountIds = SystemAccount::where('status','Normal')->pluck('id');
                print_r($systemAccountIds);
                $platformNotifitions = [];
                foreach ($systemAccountIds as $systemAccountId){
                    $platformNotifition = [];
                    $platformNotifition['accountId'] = $systemAccountId;
                    $platformNotifition['type'] = $data['type'];
                    $platformNotifition['platform'] = $data['platform'];
                    $infos = explode('-',$data['content']);
                    $platformNotifition['title'] = '出款风险提示：商户'.$infos[1] . '，收款人：'.$infos[0];
                    $platformNotifition['content'] = '出款风险提示：' . '收款人：'.$infos[0] . '一小时内出款次数或金额高于系统异常值，最后一笔来自商户'.$infos[1].'，请审查清楚并通知商户谨慎操作！';
                    array_push($platformNotifitions,$platformNotifition);
                }
                PlatformNotify::insert($platformNotifitions);
                print_r($platformNotifitions);
            }
        }catch (\Exception $e){
            $this->logger->error($this->queueName . ':' . $e->getMessage());
        }

    }
}