<?php

use Phinx\Seed\AbstractSeed;

class MerchantSeeder extends AbstractSeed
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
                'merchantNo' => '88888888',
                'fullName' => 'Seeder',
                'shortName' => 'Seeder',
                'status' => 'Normal',
                'platformNo' => '88888888',
                // 'holidaySettlementMaxAmount' => 1000000,
                // 'holidaySettlementRate' => 1,
                // 'holidaySettlementType' => 'OverplusD1Settlement',
                // 'workdaySettlementMaxAmount' => 1000000,
                // 'workdaySettlementRate' => 1,
                // 'workdaySettlementType' => 'OverplusD1Settlement',
                'settlementTime' => 0,
                'D0SettlementRate' => 1,
                'oneSettlementMaxAmount' => 1000000,
                // 'openHolidaySettlement' => true,
                'openPay' => true,
                'openQuery' => true,
                'openSettlement' => true,
                'openCheckDomain' => false,
                // 'openWorkdaySettlement' => true,
                'domain' => 'http://www.baidu.com',
                // 'signKey' => '4cb3d3f7048a428092dda2600981ba18',
                'signKey' => 'S8c6XxyZrM7kKuUMvgdn+c8UzSpxwQdkQWQjD29rWhCb6H3hHJSEZ2wJxGQnAXGl',
            ],

        ];
        $posts = $this->table('merchant');
        $posts->truncate();
        $posts->insert($data)->save();

        $data = [
            'platformNo' => "88888888",
            'merchantNo' => '88888888',
            'merchantId' => 1,
            'loginName' => "merchant",
            'loginPwd' => "02ae4d138ebff07cb6bb0efcca8c4546a105f520",
            'userName' => "Seeder",
            'securePwd' => "02ae4d138ebff07cb6bb0efcca8c4546a105f520",
        ];

        $posts = $this->table('merchant_account');
        $posts->truncate();
        $posts->insert($data)->save();

        $sql = "
        INSERT INTO `merchant`(`merchantId`, `merchantNo`, `fullName`, `shortName`, `status`, `platformNo`, `D0SettlementRate`, `settlementTime`, `oneSettlementMaxAmount`, `openPay`, `openQuery`, `openSettlement`, `openBackNotice`, `openCheckAccount`, `openCheckDomain`, `openFrontNotice`, `signKey`, `domain`, `description`, `backNoticeMaxNum`, `platformType`, `created_at`, `updated_at`) VALUES (2, '88888889', '测试001', 'zzz', 'Normal', '88888889', 1, 0, 200000, 1, 1, 1, 1, 0, 1, 0, '+2zH76+KyOdld4V0ko9I/qUJoBPPN0vSLHA+FV+reXtXr7DFDmqBRlscKOasKn3P', '', '测试用001', 10, 'Normal', '2019-04-17 11:16:50', '2019-04-18 18:00:02');
        INSERT INTO `merchant`(`merchantId`, `merchantNo`, `fullName`, `shortName`, `status`, `platformNo`, `D0SettlementRate`, `settlementTime`, `oneSettlementMaxAmount`, `openPay`, `openQuery`, `openSettlement`, `openBackNotice`, `openCheckAccount`, `openCheckDomain`, `openFrontNotice`, `signKey`, `domain`, `description`, `backNoticeMaxNum`, `platformType`, `created_at`, `updated_at`) VALUES (3, '88888890', '樱滢', 'xyy', 'Normal', '88888890', 1, 0, 200000, 1, 1, 1, 1, 0, 1, 0, '4nf4q66FYmVYvG8cAJM8gZL4V4lvCmci77Y6QwpC58XQSaaQNYr33r4plHOeuCCX', '', '1', 10, 'Normal', '2019-04-17 14:25:36', '2019-04-17 17:37:18');

        INSERT INTO `merchant_account`(`accountId`, `loginName`, `loginPwd`, `securePwd`, `userName`, `loginFailNum`, `loginPwdAlterTime`, `merchantId`, `merchantNo`, `platformNo`, `platformType`, `status`, `userLevel`, `latestLoginTime`, `googleAuthSecretKey`, `created_at`, `updated_at`) VALUES (2, 'zzz', '4e9ff6d54c428bae8ad50eb9a60d83f72bcaefa6', '4e9ff6d54c428bae8ad50eb9a60d83f72bcaefa6', 'zzz', 0, NULL, 2, '88888889', '88888889', 'Normal', 'Normal', 'MerchantManager', '2019-04-18 15:50:50', '', '2019-04-17 11:16:50', '2019-04-18 15:50:50');
        INSERT INTO `merchant_account`(`accountId`, `loginName`, `loginPwd`, `securePwd`, `userName`, `loginFailNum`, `loginPwdAlterTime`, `merchantId`, `merchantNo`, `platformNo`, `platformType`, `status`, `userLevel`, `latestLoginTime`, `googleAuthSecretKey`, `created_at`, `updated_at`) VALUES (3, 'xyy', '4e9ff6d54c428bae8ad50eb9a60d83f72bcaefa6', '61c5924f5c2cb0a09e8a83c3166bcdedb725fe8f', '樱滢', 0, '2019-04-19 10:48:20', 3, '88888890', '88888890', 'Normal', 'Normal', 'MerchantManager', '2019-04-19 10:48:29', '', '2019-04-17 14:25:36', '2019-04-19 10:48:29');
        ";

        $this->execute($sql);
    }
}
