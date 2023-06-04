<?php
namespace App\Channels;
use App\Queues\SettlementPushExecutor;
abstract class ChannelsSettlement extends Channels
{
    abstract public function queryBalance();

    final public function getSettlementOrder($params)
    {
        if (!isset($params['platformOrderNo'])) {
            throw new ChannelsSettlementException("【{$this->config['name']}】platformOrderNo没有定义");
        }

        if (!isset($params['CB'])) {
            throw new ChannelsSettlementException("【{$this->config['name']}】CB没有定义");
        }

        if (!isset($params['orderAmount'])) {
            throw new ChannelsSettlementException("【{$this->config['name']}】orderAmount没有定义");
        }

        $params = $this->createParams($params);
        $sign = $this->createSign($params);
        $this->changeOrderAmount($params['orderAmount']);
        $params['orderAmount'] = $this->getOrderAmount();
        $request = $this->doRequest($params, $sign);

        if (empty($request)) {
            throw new ChannelsSettlementException("【{$this->config['name']}】{$this->gateway} 返回值为空");
        }

        if (!isset($request['status']) || !in_array($request['status'], ['WaitTransfer', 'Success', 'Fail'])) {
            throw new ChannelsSettlementException("【{$this->config['name']}】status返回值格式错误");
        }

        if ($request['status'] == 'Success' && !isset($request['orderNo'])) {
            throw new ChannelsSettlementException("【{$this->config['name']}】orderNo返回值格式错误");
        }

        if ($request['status'] == 'Fail' && !isset($request['failReason'])) {
            throw new ChannelsSettlementException("【{$this->config['name']}】failReason返回值格式错误");
        }
        if ($this->checkSign($request) == false) {
            throw new ChannelsSettlementException("【{$this->config['name']}】sign计算错误");
        }
        return $request;
    }

    public function doCallback($response, $orderData, $params)
    {
        $sign = $this->createSign($params);
        if ($this->checkSign($params)) {
            $standardParams = $this->getStandardParam($orderData, $params);
            (new SettlementPushExecutor)->push(0, $orderData['platformOrderNo'], $params, $standardParams);
            return $this->outputResponse($response, true);
        } else {
            return $this->outputResponse($response, false);
        }
    }
}
