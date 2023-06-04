<?php

use Phinx\Seed\AbstractSeed;

class ChannelMerchantSeeder extends AbstractSeed
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
        $data = [
            [
                'channelMerchantNo' => '99999999',
                'channel' => 'mockTest',
                'status' => 'Normal',
                'platformNo' => '99999999',
                // 'holidaySettlementMaxAmount' => 1000000,
                // 'holidaySettlementRate' => 1,
                // 'holidaySettlementType' => 'OverplusD1Settlement',
                // 'workdaySettlementMaxAmount' => 1000000,
                // 'workdaySettlementRate' => 1,
                // 'workdaySettlementType' => 'OverplusD1Settlement',
                'settlementTime' => 0,
                'oneSettlementMaxAmount' => 1000000,
                // 'openHolidaySettlement' => true,
                'openPay' => true,
                'openQuery' => true,
                'openSettlement' => true,
                'D0SettlementRate' => 1,
                // 'openWorkdaySettlement' => true,
                'delegateDomain' => '',
                'param' => '',
            ],
        ];
        $posts = $this->table('channel_merchant');
        $posts->truncate();
        $posts->insert($data)->save();
    }
}
