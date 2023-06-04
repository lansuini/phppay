<?php

use Phinx\Seed\AbstractSeed;

class ChannelMerchantRateSeeder extends AbstractSeed
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
        渠道商户号,产品类型,支付方式,银行代码,卡种,费率类型,费率值,最小手续费,最大手续费,生效时间,失效时间,状态
99999999,Pay,EBank,,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,ABC,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,BCOM,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,BOC,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,CCB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,CEB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,CIB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,CITIC,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,CMB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,CMBC,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,GDB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,HXB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,HZB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,ICBC,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,NJCB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,PAB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,PSBC,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,SHB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,SPDB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,EBank,SRCB,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,OnlineAlipayH5,,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-09-16,,Normal
99999999,Pay,OnlineAlipayQR,,DEBIT,Rate,0.030000,0.100000,999999.000000,2018-09-16,,Normal
99999999,Pay,OnlineWechatH5,,DEBIT,Rate,0.035000,0.100000,999999.000000,2018-09-16,,Normal
99999999,Pay,OnlineWechatQR,,DEBIT,Rate,0.035000,0.100000,999999.000000,2018-09-16,,Normal
99999999,Pay,Quick,,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,ABC,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,BCOM,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,BOC,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,CCB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,CEB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,CIB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,CITIC,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,CMB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,CMBC,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,GDB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,HXB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,HZB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,ICBC,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,NJCB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,PAB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,PSBC,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,SHB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,SPDB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,Quick,SRCB,DEBIT,Rate,0.009500,0.100000,999999.000000,2018-10-12,,Normal
99999999,Pay,UnionPayQR,,DEBIT,Rate,0.010000,0.100000,999999.000000,2018-10-12,,Normal
99999999,Settlement,D0Settlement,,,FixedValue,3,,,2018-09-16,,Normal
        ';
        $fields = ['channelMerchantNo', 'productType', 'payType',
            'bankCode', 'cardType', 'rateType', 'rate', 'minServiceCharge',
            'maxServiceCharge', 'beginTime', 'endTime', 'status'];
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

            $temp['channelMerchantId'] = 1;
            $temp['channel'] = 'mockTest';
            $temp['endTime'] = !empty($temp['endTime']) ? $temp['endTime'] : null;
            $temp['minServiceCharge'] = (float) $temp['minServiceCharge'];
            $temp['maxServiceCharge'] = (float) $temp['maxServiceCharge'];
            $data[] = $temp;
        }
        $posts = $this->table('channel_merchant_rate');
        $posts->truncate();
        $posts->insert($data)->save();
    }
}
