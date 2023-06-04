<?php

use Phinx\Seed\AbstractSeed;

class MerchantChannelSettlementSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $content = '
        下游商户号,渠道名称,渠道商户号,代付渠道状态,代付账户类型,账户余额,账户最少保留金额,是否开启单笔金额控制,单笔最小金额,单笔最大金额,是否开启单日累计金额控制,单日累计金额上限,是否开启单日累计笔数控制,单日累计笔数上限,是否开启交易时间控制,开始时间,结束时间,配置状态
        88888888,mockTest,99999999,Normal,UsableAccount,10000000,0,1,1,50000,0,0,0,0,0,20,2215,Normal
        ';
        $fields = ['merchantNo', 'channel', 'channelMerchantNo',
            'settlementChannelStatus', 'settlementAccountType', 'accountBalance', 'accountReservedBalance',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit', 'openTimeLimit', 'beginTime', 'endTime', 'status'];
        $merchantId = 1;
        $contents = explode("\n", $content);
        $data = [];
        foreach ($contents as $k => $row) {
            if (empty($row)) {
                continue;
            }
            if ($k <= 1) {
                continue;
            }
            $row = str_replace(' ', '', trim($row));
            $rows = explode(',', $row);
            if (count($fields) != count($rows)) {
                continue;
            }
            $temp = array_combine($fields, $rows);

            $temp['merchantNo'] = '88888888';
            $temp['merchantId'] = 1;
            $temp['channelMerchantId'] = 1;
            $temp['endTime'] = intval($temp['endTime']);
            $temp['endTime'] = intval($temp['endTime']);
            $data[] = $temp;
        }
        $posts = $this->table('merchant_channel_settlement');
        $posts->truncate();
        $posts->insert($data)->save();
    }
}
