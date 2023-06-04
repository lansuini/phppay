<?php

use Phinx\Seed\AbstractSeed;

class MerchantChannelSeeder extends AbstractSeed
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
        下游商户号,渠道名称,渠道商户号,支付渠道状态,支付方式,银行代码,银行卡类型,是否开启单笔金额控制,单笔最小金额,单笔最大金额,是否开启单日累计金额控制,单日累计金额上限,是否开启单日累计笔数控制,单日累计笔数上限,是否开启交易时间控制,开始时间,结束时间,配置状态
        88888888,mockTest,99999999,Normal,OnlineWechatQR,,DEBIT,1,300,5000,0,0,0,0,1,1000,2130,Normal
        88888888,mockTest,99999999,Normal,OnlineAlipayH5,,DEBIT,1,20,5000,0,0,0,0,0,0,0,Normal
        88888888,mockTest,99999999,Normal,OnlineAlipayQR,,DEBIT,1,20,5000,0,0,0,0,0,0,0,Normal
        ';
        $fields = ['merchantNo', 'channel', 'channelMerchantNo',
            'payChannelStatus', 'payType', 'bankCode', 'cardType',
            'openOneAmountLimit', 'oneMinAmount', 'oneMaxAmount', 'openDayAmountLimit',
            'dayAmountLimit', 'openDayNumLimit', 'dayNumLimit',
            'openTimeLimit', 'beginTime', 'endTime', 'status'];
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
            unset($temp['bankCode']);
            $data[] = $temp;
        }
        $posts = $this->table('merchant_channel');
        print_r($data);
        $posts->truncate();
        $posts->insert($data)->save();
    }
}
