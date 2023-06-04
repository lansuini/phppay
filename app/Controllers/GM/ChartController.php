<?php

namespace App\Controllers\GM;

set_time_limit(0);

use App\Helpers\Tools;
use App\Models\AmountPay;
use App\Models\Channel;
use App\Models\ChannelMerchant;
use App\Models\Merchant;
use App\Models\MerchantDailyStats;
use App\Models\ChannelDailyStats;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChartController extends GMController
{
    public function payOrderAmount(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/chart/payorderamount.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }
    private function exportPayOrderHead(){
        return [
            'accountDate' => '账务日期',
            'merchantNo' => '商户号',
            'shortName' => '商户简称',
            'payTypeDesc' => '支付方式',
            'amount' => '支付订单金额',
        ];
    }
    public function getPayOrderAmount(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');
        $payType = $request->getParam('payType');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $export = $request->getParam('export');

        empty($endDate) && $endDate = date('Ymd');

        $where = [];
        $where[] = '1=1';
        $value = [];
        $merchantNo && $where[] = 'merchantNo=?';
        $merchantNo && $value[] = $merchantNo;

        $payType && $where[] = 'payType=?';
        $payType && $value[] = $payType;

        $beginDate && $where[] = 'accountDate>=?';
        $beginDate && $value[] = $beginDate;

        $endDate && $where[] = 'accountDate<=?';
        $endDate && $value[] = $endDate;

        $whereStr = implode(' and ', $where);
        if(!$export) {
            $sql = "select count(*) from (
            select merchantId, accountDate, payType, balance, sum(amount) as amount
            from amount_pay
            where {$whereStr}
            group by merchantId, accountDate, payType order by accountDate desc, merchantId, payType
            ) a";
            $total = \Illuminate\Database\Capsule\Manager::select($sql, $value);
            $total = current(current($total));
        }

        $whereStr = implode(' and ', $where);
        $sql = "select a.*, merchant.merchantNo, merchant.shortName from (
            select merchantId, accountDate, payType, balance, sum(amount) as amount
            from amount_pay
            where {$whereStr}
            group by merchantId, accountDate, payType order by accountDate desc, merchantId, payType
            ) a left join merchant on a.merchantId = merchant.merchantId";
        if(!$export){
            $value[] = $limit;
            $value[] = $offset;
            $sql .= " LIMIT ? offset ?";

        }
        $data = \Illuminate\Database\Capsule\Manager::select($sql, $value);

        foreach ($data ?? [] as $k => $v) {
            $v->payTypeDesc = $this->c->code['payType'][$v->payType] ?? '';
            $data[$k] = $v;
        }
        if($export){
            Tools::csv_export($data, $this->exportPayOrderHead(), 'payOrderSumList');
            die();
        }
        $sql = "select count(accountDate) as num , sum(amount) as amount from(
            select merchantId, accountDate, payType, balance, sum(amount) as amount
            from amount_pay
            where {$whereStr}
            group by merchantId, accountDate, payType order by accountDate desc, merchantId, payType
            ) a";
        $stat = \Illuminate\Database\Capsule\Manager::select($sql, $value);
        $stat = current($stat);
        $stat = json_decode(json_encode($stat), true);
        $stat['amount'] = number_format($stat['amount'], 2);
        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $data,
            'stat' => $stat,
        ]);
    }

    public function settlementOrderAmount(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/chart/settlementorderamount.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

//    public function getSettlementOrderAmount(Request $request, Response $response, $args)
//    {
//        $merchantNo = $request->getParam('merchantNo');
//        $beginDate = $request->getParam('beginDate');
//        $endDate = $request->getParam('endDate');
//        $limit = $request->getParam('limit');
//        $offset = $request->getParam('offset');
//        empty($endDate) && $endDate = date('Ymd');
//
//        $value = [];
//        $whereStr = "1=1 ";
//        $merchantNo && $whereStr .= " and merchantNo=" . $merchantNo;
//        $beginDate && $whereStr .= " and accountDate>=" . date('Ymd', strtotime($beginDate));
//        $endDate && $whereStr .= " and accountDate<=" . date('Ymd', strtotime($endDate));
//
////        $whereStr = implode(' and ', $where);
//        //        var_dump($whereStr);exit;
//        $sql = "select count(*) from (select *  from ((
//                          select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc) a
//                          left join (
//                          select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc
//                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD)
//                          union
//                          select * from ((
//                          select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc) a
//                          right join (
//                          select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc
//                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD) ) c";
//        $total = \Illuminate\Database\Capsule\Manager::select($sql, $value);
//        $total = current(current($total));
//
//        $value[] = $limit;
//        $value[] = $offset;
//
//        $settlementSql = "select c.*, merchant.merchantNo, merchant.shortName from (select *, ifnull(b.pAD,a.accountDate) as newDate , ifnull(b.payMerchantId, a.settlementMerchantId) as merchantId  from ((
//                          select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc) a
//                          left join (
//                          select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc
//                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD)
//                          union
//                          select *, ifnull(a.accountDate,b.pAD) as newDate, ifnull(a.settlementMerchantId,b.payMerchantId) as merchantId from ((
//                          select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc) a
//                          right join (
//                          select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by merchantId, accountDate order by accountDate desc
//                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD) ) c
//                          left join
//                          merchant on c.merchantId = merchant.merchantId order by c.newDate desc limit ? offset ? ";
//
//        $settlementData = \Illuminate\Database\Capsule\Manager::select($settlementSql, $value);
//
//        $sql = "select count(*) as num, sum(payAmount) as payAmount, sum(payServiceCharge) as payServiceCharge, sum(settlementAmount) as settlementAmount, sum(settlementServiceCharge) as settlementServiceCharge, sum(settlementTimes) as settlementTimes from (select *  from ((
//            select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//            from amount_settlement where {$whereStr}
//            group by merchantId, accountDate order by accountDate desc) a
//            left join (
//            select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//            from amount_pay
//            where {$whereStr}
//            group by merchantId, accountDate order by accountDate desc
//            ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD)
//            union
//            select * from ((
//            select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//            from amount_settlement where {$whereStr}
//            group by merchantId, accountDate order by accountDate desc) a
//            right join (
//            select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//            from amount_pay
//            where {$whereStr}
//            group by merchantId, accountDate order by accountDate desc
//            ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD) ) c";
//        $stat = \Illuminate\Database\Capsule\Manager::select($sql, $value);
//        $stat = current($stat);
//        $stat = json_decode(json_encode($stat), true);
//        $stat['payAmount'] = number_format($stat['payAmount'], 2);
//        $stat['settlementAmount'] = number_format($stat['settlementAmount'], 2);
//        $stat['settlementServiceCharge'] = number_format($stat['settlementServiceCharge'], 2);
//        $stat['payServiceCharge'] = number_format($stat['payServiceCharge'], 2);
//
//        return $response->withJson([
//            'success' => 1,
//            'result' => [],
//            'total' => $total,
//            'rows' => $settlementData,
//            'stat' => $stat,
//        ]);
//    }

    //代付明细表
    public function getSettlementOrderAmount(Request $request, Response $response, $args)
    {
        $merchant = new Merchant();
        $model = new MerchantDailyStats();
        $merchantNo = $request->getParam('merchantNo');
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');
        $export = $request->getParam('export');
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        empty($endDate) && $endDate = date('Y-m-d');

        $merchantNo && $model = $model->where('merchantNo', $merchantNo);
        $beginDate && $model = $model->where('accountDate', '>=', $beginDate);
        $endDate && $model =  $model->where('accountDate', '<=', $endDate);

        $cmodel = clone $model;
        $smodel = clone $model;
        $total = $cmodel->count();
        $data = $model->orderBy('dailyId', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        $merchantData = [];

        foreach ($data ?? [] as $k => $v) {
            $merchantData[$v['merchantId']] = isset($merchantData[$v['merchantId']]) ? $merchantData[$v['merchantId']] : $merchant->getCacheByMerchantId($v['merchantId']);
            $data[$k]['shortName'] = isset($merchantData[$v['merchantId']]) ? $merchantData[$v['merchantId']]['shortName'] : null;
        }
        if(!$export){
            $statData = $smodel->selectRaw('sum(payAmount) as pAmount, sum(payServiceFees) as pFees, sum(payChannelServiceFees) as pChanFees, sum(agentPayFees) as pAgentFees, sum(settlementCount) as sCount, sum(settlementAmount) as sAmount, sum(settlementServiceFees) as sFees, sum(settlementChannelServiceFees) as sChanFees, sum(agentsettlementFees) as sAgentFees, sum(chargeCount) as cCount, sum(chargeAmount) as cAmount, sum(chargeServiceFees) as cFees, sum(chargeChannelServiceFees) as cChanFees, sum(agentchargeFees) as cAgentFees')->first()->toArray();
            $stat['num'] = $total;
            $stat['payAmount'] = number_format($statData['pAmount'], 2);
            $stat['payServiceFees'] = number_format($statData['pFees'], 2);
            $stat['payChanServiceFees'] = number_format($statData['pChanFees'], 2);
            $stat['pAgentFees'] = number_format($statData['pAgentFees'], 2);
            $stat['settlementCount'] = $statData['sCount'];
            $stat['settlementAmount'] = number_format($statData['sAmount'], 2);
            $stat['settlementServiceFees'] = number_format($statData['sFees'], 2);
            $stat['settlementChanServiceFees'] = number_format($statData['sChanFees'], 2);
            $stat['sAgentFees'] = number_format($statData['sAgentFees'], 2);
            $stat['chargeCount'] = $statData['cCount'];
            $stat['chargeAmount'] = number_format($statData['cAmount'], 2);
            $stat['chargeServiceFees'] = number_format($statData['cFees'], 2);
            $stat['chargeChanServiceFees'] = number_format($statData['cChanFees'], 2);
            $stat['cAgentFees'] = number_format($statData['cAgentFees'], 2);
        }else{
            $headers = [
                'accountDate' => '财务日期',
                'merchantNo' => '商户号',
                'shortName' => '商户简称',
                'payAmount' => '今日支付总金额',
                'payServiceFees' => '今日支付手续费',
                'payChannelServiceFees' => '今日支付上游手续费',
                'agentPayFees' => '今日支付代理费',
                'settlementCount' => '今日代付笔数',
                'settlementAmount' => '今日代付总金额',
                'settlementServiceFees' => '今日代付手续费',
                'settlementChannelServiceFees' => '今日代付上游手续费',
                'agentsettlementFees' => '今日代付代理费',
                'chargeCount' => '今日充值笔数',
                'chargeAmount' => '今日充值总金额',
                'chargeServiceFees' => '今日充值手续费',
                'chargeChannelServiceFees' => '今日支付上游手续费',
                'agentchargeFees' => '今日充值代理费',
            ];
            Tools::csv_export($data, $headers, 'merchantDailyStats');
            die();
        }

        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $data,
            'stat' => $stat,
        ]);
    }

    public function businessAmount(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/chart/businessamount.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }
    private function exportChartHead(){
        return [
            'pAD' => '日期',
            'merchantNo' => '商户号',
            'shortName' => '商户简称',
            'merchantBalance' => '账户余额',
            'payAmount' => '今日支付',
            'settlementAmount' => '今日代付',
            'payServiceCharge' => '今日商户手续费',
            'channelServiceCharge' => '今日上游手续费',
            'payCSC' => '平台今日收入',
        ];
    }
    public function getBusinessAmount(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');
        $export = $request->getParam('export');
        empty($endDate) && $endDate = date('Ymd');

        $whereStr = "1=1 ";
        $merchantNo && $whereStr .= " and merchantNo=" . $merchantNo;
        $beginDate && $whereStr .= " and accountDate>=" . date("Ymd", strtotime($beginDate));
        $endDate && $whereStr .= " and accountDate<=" . date('Ymd', strtotime($endDate));
        if(!$export) {
            $value = [];
            $value[] = $limit;
            $value[] = $offset;

            $sql = "select count(*) from (select *  from ((
                          select merchantId as settlementMerchantId, accountDate
                          from amount_settlement where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc) a
                          left join (
                          select merchantId as payMerchantId, accountDate as pAD
                          from amount_pay
                          where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc
                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD)
                          union
                          select * from ((
                          select merchantId as settlementMerchantId, accountDate
                          from amount_settlement where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc) a
                          right join (
                          select merchantId as payMerchantId, accountDate as pAD
                          from amount_pay
                          where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc
                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD) ) c";
            $total = \Illuminate\Database\Capsule\Manager::select($sql, $value);
            $total = current(current($total));
        }
        $value[] = $limit;
        $value[] = $offset;

        $settlementSql = "select c.*, merchant.merchantNo, merchant.shortName,merchant_amount.settlementAmount as merchantBalance from (select *, ifnull(b.pAD,a.accountDate) as newDate , ifnull(b.payMerchantId, a.settlementMerchantId) as merchantId  from ((
                          select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes, sum(channelServiceCharge) as channelServiceCharge
                          from amount_settlement where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc) a
                          left join (
                          select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge, sum(channelServiceCharge) as payCSC
                          from amount_pay
                          where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc
                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD)
                          union
                          select *, ifnull(a.accountDate,b.pAD) as newDate, ifnull(a.settlementMerchantId,b.payMerchantId) as merchantId from ((
                          select merchantId as settlementMerchantId, accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes, sum(channelServiceCharge)  as channelServiceCharge
                          from amount_settlement where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc) a
                          right join (
                          select merchantId as payMerchantId, accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge, sum(channelServiceCharge) as payCSC
                          from amount_pay
                          where {$whereStr}
                          group by merchantId, accountDate order by accountDate desc
                          ) b on a.settlementMerchantId = b.payMerchantId and a.accountDate = b.pAD) ) c
                          left join
                          merchant on c.merchantId = merchant.merchantId left Join merchant_amount on c.merchantId = merchant_amount.merchantId order by c.newDate desc";
        if($export){
            $settlementData = \Illuminate\Database\Capsule\Manager::select($settlementSql);
            if($export) {
                Tools::csv_export($settlementData, $this->exportChartHead(), 'ChartList');
                die();
            }
        }
        $settlementSql .= " limit ? offset ? ";
        $settlementData = \Illuminate\Database\Capsule\Manager::select($settlementSql, $value);

        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $settlementData,
        ]);
    }

    public function revenueChart(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/chart/revenueChart.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

//    public function getRevenueChart(Request $request, Response $response, $args)
//    {
//        $merchantNo = $request->getParam('merchantNo');
//        $beginDate = $request->getParam('beginDate');
//        $endDate = $request->getParam('endDate');
//        $limit = $request->getParam('limit');
//        $offset = $request->getParam('offset');
//        empty($endDate) && $endDate = date('Ymd');
//
//        $value = [];
//        $whereStr = "1=1 ";
//        $merchantNo && $whereStr .= " and merchantNo=" . $merchantNo;
//        $beginDate && $whereStr .= " and accountDate>=" . date("Ymd", strtotime($beginDate));
//        $endDate && $whereStr .= " and accountDate<=" . date('Ymd', strtotime($endDate));
//
//        $sql = "select count(*) from (select *   from ((
//                          select  accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by accountDate order by accountDate desc) a
//                          left join (
//                          select  accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by accountDate order by accountDate desc
//                          ) b on a.accountDate = b.pAD)
//                          union
//                          select * from ((
//                          select  accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by accountDate order by accountDate desc) a
//                          right join (
//                          select  accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by accountDate order by accountDate desc
//                          ) b on a.accountDate = b.pAD) ) c";
//        $total = \Illuminate\Database\Capsule\Manager::select($sql, $value);
//        $total = current(current($total));
//
//        $value[] = $limit;
//        $value[] = $offset;
//        $settlementSql = "select c.* from (select *, ifnull(b.pAD,a.accountDate) as newDate   from ((
//                          select  accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by accountDate order by accountDate desc) a
//                          left join (
//                          select  accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by pAD order by pAD desc
//                          ) b on a.accountDate = b.pAD)
//                          union
//                          select *, ifnull(a.accountDate,b.pAD) as newDate from ((
//                          select  accountDate, sum(amount) as settlementAmount, sum(serviceCharge) as settlementServiceCharge, sum(transferTimes) as settlementTimes
//                          from amount_settlement where {$whereStr}
//                          group by accountDate order by accountDate desc) a
//                          right join (
//                          select  accountDate as pAD, sum(amount) as payAmount, sum(serviceCharge) as payServiceCharge
//                          from amount_pay
//                          where {$whereStr}
//                          group by pAD order by pAD desc
//                          ) b on  a.accountDate = b.pAD) ) c  order by c.newDate desc limit ? offset ? ";
//        $settlementData = \Illuminate\Database\Capsule\Manager::select($settlementSql, $value);
//
//        $sql = "SELECT count(newDate) as num, SUM(settlementAmount) AS settlementAmount, SUM(settlementServiceCharge) AS settlementServiceCharge, SUM(settlementTimes) AS settlementTimes, SUM(payAmount) AS payAmount, SUM(payServiceCharge) AS payServiceCharge FROM (
//            SELECT *, a.accountDate AS newDate FROM (
//                    SELECT accountDate, SUM(amount) AS settlementAmount, SUM(serviceCharge) AS settlementServiceCharge, SUM(transferTimes) AS settlementTimes
//                    FROM amount_settlement
//                    WHERE {$whereStr}
//                    GROUP BY accountDate
//                ) a LEFT JOIN (
//                        SELECT accountDate AS pAD, SUM(amount) AS payAmount, SUM(serviceCharge) AS payServiceCharge
//                        FROM amount_pay
//                        WHERE {$whereStr}
//                        GROUP BY pAD
//                    ) b ON a.accountDate = b.pAD
//            UNION
//            SELECT *, b.pAD AS newDate
//            FROM (
//                    SELECT accountDate, SUM(amount) AS settlementAmount, SUM(serviceCharge) AS settlementServiceCharge, SUM(transferTimes) AS settlementTimes
//                    FROM amount_settlement
//                    WHERE {$whereStr}
//                    GROUP BY accountDate
//                ) a RIGHT JOIN (
//                        SELECT accountDate AS pAD, SUM(amount) AS payAmount, SUM(serviceCharge) AS payServiceCharge
//                        FROM amount_pay
//                        WHERE {$whereStr}
//                        GROUP BY pAD
//                    ) b ON a.accountDate = b.pAD
//            WHERE a.accountDate IS NULL
//        ) c";
//        $stat = \Illuminate\Database\Capsule\Manager::select($sql, $value);
//        $stat = current($stat);
//        $stat = json_decode(json_encode($stat), true);
//        $stat['payAmount'] = number_format($stat['payAmount'], 2);
//        $stat['settlementAmount'] = number_format($stat['settlementAmount'], 2);
//        $stat['settlementServiceCharge'] = number_format($stat['settlementServiceCharge'], 2);
//        $stat['payServiceCharge'] = number_format($stat['payServiceCharge'], 2);
//        return $response->withJson([
//            'success' => 1,
//            'result' => [],
//            'total' => $total,
//            'rows' => $settlementData,
//            'stat' => $stat,
////            'revenue' => ['totalSettlement'=>['money'=>$totalSettlement,'count'=>$settlementCount], 'totalPay'=> ['money'=>$totalPay,'count'=>$payCount]],
//        ]);
//    }

    //代付汇总表
    public function getRevenueChart(Request $request, Response $response, $args){
        $model = new MerchantDailyStats();
        $merchantNo = $request->getParam('merchantNo');
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');
        $export = $request->getParam('export');
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        empty($endDate) && $endDate = date('Y-m-d');

        $merchantNo && $model = $model->where('merchantNo', $merchantNo);
        $beginDate && $model = $model->where('accountDate', '>=', $beginDate);
        $endDate && $model =  $model->where('accountDate', '<=', $endDate);

        $cmodel = clone $model;
        $smodel = clone $model;
        $total = $cmodel->selectRaw('count(DISTINCT accountDate) as c')->first()->toArray();
        $data = $model->selectRaw('accountDate,sum(payAmount) as payAmount, sum(payServiceFees) as payServiceFees, sum(payChannelServiceFees) as payChannelServiceFees,
         sum(agentPayFees) as payAgentFees, sum(settlementCount) as settlementCount, sum(settlementAmount) as settlementAmount, 
         sum(settlementServiceFees) as settlementServiceFees, sum(settlementChannelServiceFees) as settlementChannelServiceFees, sum(agentsettlementFees) as settlementAgentFees, sum(chargeCount) as chargeCount, 
         sum(chargeAmount) as chargeAmount, sum(chargeServiceFees) as chargeServiceFees, sum(chargeChannelServiceFees) as chargeChannelServiceFees, sum(agentchargeFees) as chargeAgentFees')
            ->groupBy('accountDate')->orderBy('accountDate', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        if(!$export){
            $statData = $smodel->selectRaw('sum(payAmount) as pAmount, sum(payServiceFees) as pFees, sum(payChannelServiceFees) as pChannelFees, 
            sum(agentPayFees) as pAgentFees, sum(settlementCount) as sCount, sum(settlementAmount) as sAmount, 
            sum(settlementServiceFees) as sFees, sum(settlementChannelServiceFees) as sChannelFees, sum(agentsettlementFees) as sAgentFees, 
            sum(chargeCount) as cCount, sum(chargeAmount) as cAmount, sum(chargeServiceFees) as cFees, 
            sum(chargeChannelServiceFees) as cChannelFees, sum(agentchargeFees) as cAgentFees')->first()->toArray();
        }else{
            $headers = [
                'accountDate' => '财务日期',
                'payAmount' => '今日支付总金额',
                'payServiceFees' => '今日支付手续费',
                'payChannelServiceFees' => '今日支付上游手续费',
                'payAgentFees' => '今日支付代理费',
                'settlementCount' => '今日代付笔数',
                'settlementAmount' => '今日代付总金额',
                'settlementServiceFees' => '今日代付手续费',
                'settlementChannelServiceFees' => '今日代付上游手续费',
                'settlementAgentFees' => '今日代付代理费',
                'chargeCount' => '今日充值笔数',
                'chargeAmount' => '今日充值总金额',
                'chargeServiceFees' => '今日充值手续费',
                'chargeChannelServiceFees' => '今日充值上游手续费',
                'chargeAgentFees' => '今日充值代理费',
            ];
            Tools::csv_export($data, $headers, 'dailyGatherStats');
            die();
        }
        $stat['num'] = $total['c'];
        $stat['payAmount'] = number_format($statData['pAmount'], 2);
        $stat['payServiceFees'] = number_format($statData['pFees'], 2);
        $stat['payChanServiceFees'] = number_format($statData['pChannelFees'], 2);
        $stat['pAgentFees'] = number_format($statData['pAgentFees'], 2);
        $stat['settlementCount'] = $statData['sCount'];
        $stat['settlementAmount'] = number_format($statData['sAmount'], 2);
        $stat['settlementServiceFees'] = number_format($statData['sFees'], 2);
        $stat['settlementChanServiceFees'] = number_format($statData['sChannelFees'], 2);
        $stat['sAgentFees'] = number_format($statData['sAgentFees'], 2);
        $stat['chargeCount'] = $statData['cCount'];
        $stat['chargeAmount'] = number_format($statData['cAmount'], 2);
        $stat['chargeServiceFees'] = number_format($statData['cFees'], 2);
        $stat['chargeChanServiceFees'] = number_format($statData['cChannelFees'], 2);
        $stat['cAgentFees'] = number_format($statData['cAgentFees'], 2);

        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total['c'],
            'rows' => $data,
            'stat' => $stat,
        ]);
    }

    //渠道明细表
    public function channelDaily(Request $request, Response $response, $args){
        return $this->c->view->render($response, 'gm/chart/channeldaily.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    //渠道明细表api
    public function getChannelDaily(Request $request, Response $response, $args){
        $channel = new ChannelMerchant();
        $model = new ChannelDailyStats();
        $channelNo = $request->getParam('channelNo');
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');
        $export = $request->getParam('export');
        $limit = $request->getParam('limit', 20);
        $offset = $request->getParam('offset', 0);
        empty($endDate) && $endDate = date('Y-m-d');

        $channelNo && $model = $model->where('channelMerchantNo', $channelNo);
        $beginDate && $model = $model->where('accountDate', '>=', $beginDate);
        $endDate && $model =  $model->where('accountDate', '<=', $endDate);

        $cmodel = clone $model;
        $smodel = clone $model;
        $total = $cmodel->count();
        $data = $model->orderBy('dailyId', 'desc')->offset($offset)->limit($limit)->get()->toArray();

        $channelData = [];

        foreach ($data ?? [] as $k => $v) {
            $channelData[$v['channelMerchantId']] = isset($channelData[$v['channelMerchantId']]) ? $channelData[$v['channelMerchantId']] : $channel->getCacheByChannelMerchantId($v['channelMerchantId']);
            $data[$k]['channel'] = isset($channelData[$v['channelMerchantId']]) ? $channelData[$v['channelMerchantId']]['channel'] : null;
            $data[$k]['channelDesc'] = isset($data[$k]['channel']) ? $this->code['channel'][$data[$k]['channel']]['name'] : null;
        }
        if(!$export){
            $statData = $smodel->selectRaw('sum(payAmount) as pAmount, sum(payServiceFees) as pFees, sum(payChannelServiceFees) as pChanFees, 
            sum(agentPayFees) as pAgentFees, sum(settlementCount) as sCount, sum(settlementAmount) as sAmount, 
            sum(settlementServiceFees) as sFees, sum(settlementChannelServiceFees) as sChanFees, sum(agentsettlementFees) as sAgentFees, 
            sum(chargeCount) as cCount, sum(chargeAmount) as cAmount, sum(chargeServiceFees) as cFees, sum(chargeChannelServiceFees) as cChanFees, sum(agentchargeFees) as cAgentFees')->first()->toArray();
            $stat['num'] = $total;
            $stat['payAmount'] = number_format($statData['pAmount'], 2);
            $stat['payServiceFees'] = number_format($statData['pFees'], 2);
            $stat['payChanServiceFees'] = number_format($statData['pChanFees'], 2);
            $stat['pAgentFees'] = number_format($statData['pAgentFees'], 2);
            $stat['settlementCount'] = $statData['sCount'];
            $stat['settlementAmount'] = number_format($statData['sAmount'], 2);
            $stat['settlementServiceFees'] = number_format($statData['sFees'], 2);
            $stat['settlementChanServiceFees'] = number_format($statData['sChanFees'], 2);
            $stat['sAgentFees'] = number_format($statData['sAgentFees'], 2);
            $stat['chargeCount'] = $statData['cCount'];
            $stat['chargeAmount'] = number_format($statData['cAmount'], 2);
            $stat['chargeServiceFees'] = number_format($statData['cFees'], 2);
            $stat['chargeChanServiceFees'] = number_format($statData['cChanFees'], 2);
            $stat['cAgentFees'] = number_format($statData['cAgentFees'], 2);
        }else{
            $headers = [
                'accountDate' => '财务日期',
                'channelMerchantNo' => '渠道号',
                'channelDesc' => '商户简称',
                'payAmount' => '今日支付总金额',
                'payServiceFees' => '今日支付手续费',
                'payChannelServiceFees' => '今日支付上游手续费',
                'agentPayFees' => '今日支付代理费',
                'settlementCount' => '今日代付笔数',
                'settlementAmount' => '今日代付总金额',
                'settlementServiceFees' => '今日代付手续费',
                'settlementChannelServiceFees' => '今日代付上游手续费',
                'agentsettlementFees' => '今日代付代理费',
                'chargeCount' => '今日充值笔数',
                'chargeAmount' => '今日充值总金额',
                'chargeServiceFees' => '今日充值手续费',
                'chargeChannelServiceFees' => '今日充值上游手续费',
                'agentchargeFees' => '今日充值代理费',
            ];
            Tools::csv_export($data, $headers, 'channelDailyStats');
            die();
        }

        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $data,
            'stat' => $stat,
        ]);
    }

    public function channelAmount(Request $request, Response $response, $args)
    {
        return $this->c->view->render($response, 'gm/chart/channelamount.twig', [
            'appName' => $this->c->settings['app']['name'],
            'userName' => $_SESSION['userName'] ?? null,
            'menus' => $this->menus,
        ]);
    }

    public function getChannelAmount(Request $request, Response $response, $args)
    {
        $merchantNo = $request->getParam('merchantNo');
        $beginDate = $request->getParam('beginDate');
        $endDate = $request->getParam('endDate');
        $limit = $request->getParam('limit');
        $offset = $request->getParam('offset');

        empty($endDate) && $endDate = date('Ymd');
        $model = new AmountPay();
        $merchantNo && $model = $model->where('merchantNo', $merchantNo);
        $beginDate && $model = $model->where('accountDate', '>=', $beginDate);
        $endDate && $model = $model->where('accountDate', '<=', $endDate);

        $total = $model->count();
        $rows = $model->select(['merchantNo', 'amount as orderAmount', 'accountDate'])->orderBy('id', 'desc')->limit($limit)->offset($offset)->get();
        return $response->withJson([
            'success' => 1,
            'result' => [],
            'total' => $total,
            'rows' => $rows ? $rows->toArray() : [],
        ]);
    }
}
