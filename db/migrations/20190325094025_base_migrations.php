<?php
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

class BaseMigrations extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $merchant = $this->table('merchant', ['id' => 'merchantId', 'comment' => '商户信息表', 'signed' => false]);
        $merchant->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('fullName', 'string', ['limit' => 50, 'comment' => '商户全称'])
            ->addColumn('shortName', 'string', ['limit' => 50, 'comment' => '商户简称'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '状态(正常，关闭)'])
        // ->addColumn('statusDesc', 'string', ['limit' => 50, 'comment' => '状态描述'])

        // ->addColumn('platformId', 'integer', ['limit' => 50, 'comment' => '平台ID'])
            ->addColumn('platformNo', 'string', ['limit' => 50, 'comment' => '平台号码'])

        // ->addColumn('holidaySettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '节假日最大垫资金额', 'default' => 200000])
        // ->addColumn('holidaySettlementRate', 'float', ['signed' => false, 'comment' => '垫资比例', 'default' => 1])
        // ->addColumn('holidaySettlementType', 'enum', ['values' => [null, 'OverplusT1Settlement', 'OverplusD1Settlement'], 'default' => null, 'comment' => '结算类型(剩余结算类型, 剩余T1结算, 剩余D1结算)'])

        // ->addColumn('workdaySettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '工作日最大垫资金额', 'default' => 200000])
        // ->addColumn('workdaySettlementRate', 'float', ['signed' => false, 'comment' => '垫资比例', 'default' => 1])
        // ->addColumn('workdaySettlementType', 'enum', ['values' => [null, 'OverplusT1Settlement', 'OverplusD1Settlement'], 'default' => null, 'comment' => '结算类型(剩余结算类型, 剩余T1结算, 剩余D1结算)'])
            ->addColumn('D0SettlementRate', 'float', ['signed' => false, 'comment' => 'D0垫资比例', 'default' => 1])
            ->addColumn('settlementTime', 'integer', ['default' => 0, 'comment' => '结算时间'])

            ->addColumn('oneSettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 200000, 'comment' => '单卡单日最大结算金额'])
        // ->addColumn('openEntrustSettlement', 'boolean', ['default' => false, 'comment' => '直连委托结算开关'])
        // ->addColumn('openHolidaySettlement', 'boolean', ['default' => true, 'comment' => '节假日垫资结算开关'])
            ->addColumn('openPay', 'boolean', ['default' => true, 'comment' => '支付开关'])
            ->addColumn('openQuery', 'boolean', ['default' => true, 'comment' => '查询开关'])
            ->addColumn('openSettlement', 'boolean', ['default' => true, 'comment' => '结算开关'])
        // ->addColumn('openWorkdaySettlement', 'boolean', ['default' => false, 'comment' => '工作日垫资结算开关'])

        // 电子商务平台信息
            ->addColumn('openBackNotice', 'boolean', ['default' => true, 'comment' => '后台通知开关'])
            ->addColumn('openCheckAccount', 'boolean', ['default' => false, 'comment' => '对账开关'])
            ->addColumn('openCheckDomain', 'boolean', ['default' => true, 'comment' => '域名验证开关'])
            ->addColumn('openFrontNotice', 'boolean', ['default' => false, 'comment' => '前台通知开关'])
            ->addColumn('signKey', 'string', ['limit' => 500, 'default' => '', 'comment' => '加密key'])
            ->addColumn('domain', 'string', ['default' => '', 'comment' => '域名'])
            ->addColumn('description', 'string', ['default' => '', 'comment' => '描述'])
            ->addColumn('backNoticeMaxNum', 'integer', ['default' => 10, 'comment' => '最大回调次数'])
            ->addColumn('platformType', 'enum', ['values' => ['Normal', 'Proxy'], 'default' => 'Normal', 'comment' => '平台(一般、代理)'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantNo', ['unique' => true])
            ->save();

        $merchantAccount = $this->table('merchant_account', ['id' => 'accountId', 'comment' => '商户用户表', 'signed' => false]);
        $merchantAccount->addColumn('loginName', 'string', ['limit' => 50, 'comment' => '登录名称'])
            ->addColumn('loginPwd', 'string', ['limit' => 50, 'comment' => '密码'])
            ->addColumn('securePwd', 'string', ['comment' => '支付密码'])
            ->addColumn('userName', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('loginFailNum', 'integer', ['default' => 0, 'comment' => '登录失败次数'])
            ->addColumn('loginPwdAlterTime', 'datetime', ['null' => true, 'comment' => '密码修改时间'])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID'])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('platformNo', 'string', ['limit' => 50, 'comment' => '平台号'])
            ->addColumn('platformType', 'enum', ['values' => ['Normal', 'Proxy'], 'default' => 'Normal', 'comment' => '平台(一般、代理)'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Exception', 'Close'], 'default' => 'Normal', 'comment' => '状态'])
            ->addColumn('userLevel', 'enum', ['values' => ['MerchantManager', 'PlatformManager'], 'default' => 'MerchantManager', 'comment' => '用户级别'])
            ->addColumn('latestLoginTime', 'datetime', ['null' => true, 'comment' => '最后登录时间'])
            ->addColumn('googleAuthSecretKey', 'string', ['limit' => 500, 'comment' => '谷歌auth密钥', 'default' => ''])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('loginName', ['unique' => true])
            ->save();

        $merchantAmount = $this->table('merchant_amount', ['comment' => '商户金额统计表', 'signed' => false]);
        $merchantAmount->addColumn('merchantId', 'integer', ['comment' => '商户ID'])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])

            ->addColumn('settledAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '已结算金额'])
            ->addColumn('settlementAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '未结算金额'])

            ->addColumn('modTime', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantId', ['unique' => true])
            ->save();

        // $merchantDayAmount = $this->table('merchant_day_amount', ['comment' => '商户金额统计表']);
        // $merchantDayAmount->addColumn('merchantId', 'integer', ['comment' => '商户ID'])
        //     ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])

        //     ->addColumn('advanceAmount', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '垫资金额'])
        //     ->addColumn('settledAmount', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '已结算金额'])
        //     ->addColumn('settlementAmount', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '未结算金额'])

        //     ->addColumn('todaySettlementAmount', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '今日代付金额'])
        //     ->addColumn('todayAdvanceAmount', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '即时已提金额'])
        //     ->addColumn('todayPayAmount', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '今日支付金额'])
        //     ->addColumn('todayServiceCharge', 'decimal', ['precision'=>10,'scale'=>2, 'signed' => false,'default' => 0, 'comment' => '今日手续费'])

        //     ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addIndex('merchantId', ['unique' => true])
        //     ->save();

        $merchantRate = $this->table('merchant_rate', ['id' => 'rateId', 'comment' => '商户费率表', 'signed' => false]);
        $merchantRate->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '银行', 'default' => ''])
            ->addColumn('cardType', 'enum', ['values' => [null, 'DEBIT', 'CREDIT'], 'comment' => '银行卡类型(DEBIT=借记卡,CREDIT=信用卡)'])
        // ->addColumn('channel', 'string', ['comment' => '支付密码'])
            ->addColumn('beginTime', 'date', ['comment' => '生效时间', 'null' => true])
            ->addColumn('endTime', 'date', ['comment' => '失效时间', 'null' => true])
            ->addColumn('maxServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '最大手续费', 'default' => 0])
            ->addColumn('minServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '最小手续费', 'default' => 0])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('payType', 'enum', ['values' => ['EBank', 'Quick', 'OfflineWechatQR',
                'OfflineAlipayQR', 'OnlineWechatQR', 'OnlineAlipayQR', 'OnlineWechatH5', 'OnlineAlipayH5',
                'UnionPayQR', 'D0Settlement'],
                'comment' => '支付方式(EBank=网银,Quick=快捷,OfflineWechatQR=线下微信扫码,OfflineAlipayQR=线下支付宝扫码,
            OnlineWechatQR=线上微信扫码,OnlineAlipayQR=线上支付宝扫码,OnlineWechatH5=线上微信H5,OnlineAlipayH5=线上支付宝H5,
            UnionPayQR=银联扫码,D0Settlement=D0结算)'])
            ->addColumn('productType', 'enum', ['values' => ['Pay', 'Settlement'], 'default' => 'Pay', 'comment' => '产品类型'])
            ->addColumn('rate', 'float', ['signed' => false, 'comment' => '费率值'])
            ->addColumn('rateType', 'enum', ['values' => ['Rate', 'FixedValue'], 'default' => 'Rate', 'comment' => '费率类型'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '状态'])
        // ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道名称'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantId')
            ->save();

        $merchantChannel = $this->table('merchant_channel', ['id' => 'setId', 'comment' => '商户支付渠道配置表', 'signed' => false]);
        $merchantChannel->addColumn('merchantId', 'integer', ['comment' => '下游商户ID', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '下游商户号'])
            ->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '银行', 'null' => true])
            ->addColumn('cardType', 'enum', ['values' => [null, 'DEBIT', 'CREDIT'], 'default' => 'DEBIT', 'comment' => '银行卡类型(DEBIT=借记卡,CREDIT=信用卡)'])
            ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道名称'])
            ->addColumn('channelMerchantId', 'integer', ['comment' => '渠道商户', 'default' => 0])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '渠道商户号'])
            ->addColumn('payChannelStatus', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '支付渠道状态'])
            ->addColumn('payType', 'enum', ['values' => ['EBank', 'Quick', 'OfflineWechatQR',
                'OfflineAlipayQR', 'OnlineWechatQR', 'OnlineAlipayQR', 'OnlineWechatH5', 'OnlineAlipayH5',
                'UnionPayQR'],
                'comment' => '支付方式(EBank=网银,Quick=快捷,OfflineWechatQR=线下微信扫码,OfflineAlipayQR=线下支付宝扫码,
        OnlineWechatQR=线上微信扫码,OnlineAlipayQR=线上支付宝扫码,OnlineWechatH5=线上微信H5,OnlineAlipayH5=线上支付宝H5,
        UnionPayQR=银联扫码,D0Settlement=D0代付)'])
            ->addColumn('openTimeLimit', 'boolean', ['default' => false, 'comment' => '是否开启控制交易时间'])
            ->addColumn('beginTime', 'integer', ['default' => 0, 'comment' => '开始时间(00:00格式转整形)', 'signed' => false])
            ->addColumn('endTime', 'integer', ['default' => 0, 'comment' => '结束时间(00:00格式转整形)', 'signed' => false])
            ->addColumn('openOneAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启控制单笔金额控制'])
            ->addColumn('oneMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单笔最大金额'])
            ->addColumn('oneMinAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单笔最小金额'])
            ->addColumn('openDayAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计金额控制'])
            ->addColumn('dayAmountLimit', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '累计金额限制'])
            ->addColumn('openDayNumLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计金额控制'])
            ->addColumn('dayNumLimit', 'integer', ['default' => 0, 'comment' => '累计次数限制', 'signed' => false])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '配置状态'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantId')
            ->save();

        $merchantChannelSettlement = $this->table('merchant_channel_settlement', ['id' => 'setId', 'comment' => '商户结算渠道配置表', 'signed' => false]);
        $merchantChannelSettlement->addColumn('merchantId', 'integer', ['comment' => '下游商户ID'])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '下游商户号'])
            ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道名称'])
            ->addColumn('channelMerchantId', 'integer', ['comment' => '渠道商户'])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '渠道商户号'])
            ->addColumn('settlementChannelStatus', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '代付渠道状态'])
            ->addColumn('settlementAccountType', 'enum', ['values' => ['UsableAccount', 'T1Account'], 'comment' => '代付账户类型'])
            ->addColumn('accountBalance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '账户余额'])
            ->addColumn('accountReservedBalance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '账户最少保留金额'])
            ->addColumn('openTimeLimit', 'boolean', ['default' => false, 'comment' => '是否开启控制交易时间'])
            ->addColumn('beginTime', 'integer', ['default' => 0, 'comment' => '开始时间(00:00格式转整形)'])
            ->addColumn('endTime', 'integer', ['default' => 0, 'comment' => '结束时间(00:00格式转整形)'])
            ->addColumn('openOneAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启控制单笔金额控制'])
            ->addColumn('oneMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单笔最大金额'])
            ->addColumn('oneMinAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单笔最小金额'])
            ->addColumn('openDayAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计金额控制'])
            ->addColumn('dayAmountLimit', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '累计金额限制'])
            ->addColumn('openDayNumLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计金额控制'])
            ->addColumn('dayNumLimit', 'integer', ['default' => 0, 'comment' => '累计次数限制', 'signed' => false])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '配置状态'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantId')
            ->save();

        $channelMerchant = $this->table('channel_merchant', ['id' => 'channelMerchantId', 'comment' => '渠道商户信息管理', 'signed' => false]);
        $channelMerchant->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '支付渠道'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'comment' => '状态(正常，关闭)'])

        // ->addColumn('platformId', 'integer', ['comment' => '平台ID'])
            ->addColumn('platformNo', 'string', ['limit' => 50, 'comment' => '平台号码'])

        // ->addColumn('holidaySettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '节假日最大垫资金额', 'default' => 500000])
        // ->addColumn('holidaySettlementRate', 'float', ['signed' => false, 'comment' => '垫资比例', 'default' => 1])
        // ->addColumn('holidaySettlementType', 'enum', ['default' => 'OverplusD1Settlement', 'values' => [null, 'OverplusT1Settlement', 'OverplusD1Settlement'], 'comment' => '结算类型(剩余结算类型, 剩余T1结算, 剩余D0结算)'])

        // ->addColumn('workdaySettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '工作日最大垫资金额', 'default' => 500000])
        // ->addColumn('workdaySettlementRate', 'float', ['signed' => false, 'comment' => '垫资比例', 'default' => 1])
        // ->addColumn('workdaySettlementType', 'enum', ['default' => 'OverplusD1Settlement', 'values' => [null, 'OverplusT1Settlement', 'OverplusD1Settlement'], 'comment' => '结算类型(剩余结算类型, 剩余T1结算, 剩余DO结算)'])
            ->addColumn('settlementTime', 'integer', ['comment' => '结算时间(0-23)', 'default' => 0, 'signed' => false])
            ->addColumn('D0SettlementRate', 'float', ['comment' => 'D0结算比例', 'default' => 0, 'signed' => false])

            ->addColumn('oneSettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单卡单日最大结算金额', 'default' => 500000])
        // ->addColumn('openEntrustSettlement', 'boolean', ['comment' => '直连委托结算开关', 'default' => false])
        // ->addColumn('openHolidaySettlement', 'boolean', ['comment' => '节假日垫资结算开关', 'default' => true])
            ->addColumn('openPay', 'boolean', ['comment' => '支付开关', 'default' => true])
            ->addColumn('openQuery', 'boolean', ['comment' => '查询开关', 'default' => true])
            ->addColumn('openSettlement', 'boolean', ['comment' => '结算开关', 'default' => true])
        // ->addColumn('openWorkdaySettlement', 'boolean', ['comment' => '工作日垫资结算开关', 'default' => true])
            ->addColumn('delegateDomain', 'string', ['comment' => '代理域名', 'default' => ''])
            ->addColumn('param', 'string', ['limit' => 1500, 'comment' => '商户参数', 'default' => ''])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('channelMerchantNo', ['unique' => true])
            ->save();

        $channelMerchantRate = $this->table('channel_merchant_rate', ['id' => 'rateId', 'comment' => '渠道商户费率表', 'signed' => false]);
        $channelMerchantRate->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '银行', 'default' => ''])
            ->addColumn('cardType', 'enum', ['values' => [null, 'DEBIT', 'CREDIT'], 'default' => 'DEBIT', 'comment' => '银行卡类型(DEBIT=借记卡,CREDIT=信用卡)'])
        // ->addColumn('channel', 'string', ['comment' => '支付渠道'])
            ->addColumn('beginTime', 'date', ['comment' => '生效时间', 'null' => true])
            ->addColumn('endTime', 'date', ['comment' => '失效时间', 'null' => true])
            ->addColumn('maxServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '最大手续费'])
            ->addColumn('minServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '最小手续费'])
            ->addColumn('channelMerchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('payType', 'enum', ['values' => ['EBank', 'Quick', 'OfflineWechatQR',
                'OfflineAlipayQR', 'OnlineWechatQR', 'OnlineAlipayQR', 'OnlineWechatH5', 'OnlineAlipayH5',
                'UnionPayQR', 'D0Settlement'],
                'comment' => '支付方式(EBank=网银,Quick=快捷,OfflineWechatQR=线下微信扫码,OfflineAlipayQR=线下支付宝扫码,
            OnlineWechatQR=线上微信扫码,OnlineAlipayQR=线上支付宝扫码,OnlineWechatH5=线上微信H5,OnlineAlipayH5=线上支付宝H5,
            UnionPayQR=银联扫码,D0Settlement=D0结算)'])
            ->addColumn('productType', 'enum', ['values' => ['Pay', 'Settlement'], 'default' => 'Settlement', 'comment' => '产品类型'])
            ->addColumn('rate', 'float', ['signed' => false, 'comment' => '费率值', 'default' => 0])
            ->addColumn('rateType', 'enum', ['values' => ['Rate', 'FixedValue'], 'default' => 'Rate', 'comment' => '费率类型'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '状态'])
            ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道名称'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->save();

        // $channel = $this->table('channel', ['id' => 'channelId', 'comment' => '支付渠道类型表']);
        // $channel->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道编号'])
        //     ->addColumn('channelDesc', 'string', ['limit' => 50, 'comment' => '渠道名称'])
        //     ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addIndex('channel', ['unique' => true])
        //     ->save();

        // $platform = $this->table('platform', ['id' => 'platformId', 'comment' => '平台表']);
        // $platform->addColumn('platformNo', 'string', ['limit' => 50, 'comment' => '平台号'])
        //     ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->save();

        $amountPay = $this->table('amount_pay', ['comment' => '支付订单金额统计', 'signed' => false]);
        $amountPay->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '金额'])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('channelMerchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('accountDate', 'date', ['comment' => '财务日期'])
            ->addColumn('balance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '余额'])
            ->addColumn('serviceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '手续费'])
            ->addColumn('channelServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '上游手续费'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['merchantId', 'channelMerchantId', 'accountDate'], ['unique' => true, 'name' => 'idx_merchantId_channelMerchantId_accountDate'])
            ->addIndex('created_at')
            ->save();

        // $amountChannelMerchant = $this->table('amount_channel_merchant', ['comment' => '支付订单金额统计', 'signed' => false]);
        // $amountChannelMerchant->addColumn('payAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '金额'])
        //     ->addColumn('settlementAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '金额'])
        //     ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
        //     ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
        //     ->addColumn('channelMerchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
        //     ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
        //     ->addColumn('accountDate', 'date', ['comment' => '财务日期'])
        //     ->addColumn('balance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '账户余额'])
        //     ->addColumn('payServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '手续费'])
        //     ->addColumn('payChannelServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '上游手续费'])

        //     ->addColumn('settlementServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '手续费'])
        //     ->addColumn('settlementChannelServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '上游手续费'])

        //     ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
        //     ->addIndex(['merchantId', 'channelMerchantId', 'accountDate'], ['unique' => true, 'name' => 'idx_merchantId_channelMerchantId_accountDate'])
        //     ->addIndex('created_at')
        //     ->save();

        $amountSettlement = $this->table('amount_settlement', ['comment' => '代付订单金额统计', 'signed' => false]);
        $amountSettlement->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '金额'])
        // ->addColumn('advanceAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '垫付金额'])
        // ->addColumn('settledAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '结算金额'])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('channelMerchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('accountDate', 'date', ['comment' => '财务日期'])
            ->addColumn('serviceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '手续费'])
            ->addColumn('channelServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '上游手续费'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['merchantId', 'channelMerchantId', 'accountDate'], ['unique' => true, 'name' => 'idx_merchantId_channelMerchantId_accountDate'])
            ->addIndex('created_at')
            ->save();

        $finance = $this->table('finance', ['comment' => '财务明细', 'signed' => false, 'limit' => MysqlAdapter::INT_BIG]);
        $finance->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '金额'])
            ->addColumn('balance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '余额'])
            ->addColumn('merchantId', 'integer', ['comment' => '商户号', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
        // ->addColumn('financeNo', 'string', ['limit' => 50, 'comment' => '财务流水号'])
        // ->addColumn('transactionNo', 'string', ['limit' => 50, 'comment' => '交易流水号'])
            ->addColumn('summary', 'string', ['limit' => 50, 'comment' => '描述', 'default' => ''])
            ->addColumn('sourceDesc', 'string', ['limit' => 50, 'comment' => '来源描述'])
            ->addColumn('sourceId', 'integer', ['comment' => 'ID', 'signed' => false, 'default' => 0])
            ->addColumn('financeType', 'enum', ['values' => ['PayIn', 'PayOut'], 'default' => 'PayIn', 'comment' => '收支类型(PayIn=收入,PayOut=支出)'])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])

            ->addColumn('accountDate', 'date', ['comment' => '账务日期'])

            ->addColumn('accountType', 'enum', ['values' =>
                ['SettledAccount', 'SettlementAccount', 'AdvanceAccount', 'ServiceChargeAccount'],
                'comment' => '账户类型(SettledAccount=已结算账户,SettlementAccount=未结算账户,AdvanceAccount=垫资账户,ServiceChargeAccount=手续费账户)']
            )
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('platformOrderNo')
            ->addIndex('created_at')
            ->save();

        $platformPayOrder = $this->table('platform_pay_order', ['id' => 'orderId', 'comment' => '平台支付订单表', 'signed' => false]);
        $platformPayOrder->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('merchantOrderNo', 'string', ['limit' => 50, 'comment' => '商户订单号'])
            ->addColumn('merchantParam', 'string', ['limit' => 500, 'comment' => '回传参数'])
            ->addColumn('merchantReqTime', 'datetime', ['comment' => '商户请求时间'])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
        // ->addColumn('platformSubOrderNo', 'string', ['limit' => 50, 'comment' => '平台子订单号'])

            ->addColumn('orderStatus', 'enum', ['values' => ['Expired', 'WaitPayment', 'Success'], 'comment' => '订单状态(Expired=已过期,WaitPayment=未支付,Success=成功)'])
            ->addColumn('orderAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '订单金额'])
            ->addColumn('realOrderAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '真实订单金额'])
            ->addColumn('payType', 'enum', ['values' => ['EBank', 'Quick', 'OfflineWechatQR',
                'OfflineAlipayQR', 'OnlineWechatQR', 'OnlineAlipayQR', 'OnlineWechatH5', 'OnlineAlipayH5',
                'UnionPayQR', 'EntrustSettlement', 'AdvanceSettlement', 'HolidaySettlement'],
                'comment' => '支付方式(EBank=网银,Quick=快捷,OfflineWechatQR=线下微信扫码,OfflineAlipayQR=线下支付宝扫码,
            OnlineWechatQR=线上微信扫码,OnlineAlipayQR=线上支付宝扫码,OnlineWechatH5=线上微信H5,OnlineAlipayH5=线上支付宝H5,
            UnionPayQR=银联扫码,EntrustSettlement=委托代付,AdvanceSettlement=垫资结算,HolidaySettlement=节假日结算)'])
            ->addColumn('payModel', 'enum', ['values' => ['Direct', 'NotDirect'], 'comment' => '支付模式(Direct=直连,NotDirect=非直连)'])

            ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '上游渠道'])
            ->addColumn('channelMerchantId', 'string', ['limit' => 50, 'comment' => '上游商户ID'])
            ->addColumn('channelSetId', 'integer', ['comment' => 'ID', 'signed' => false])
            ->addColumn('idType', 'string', ['limit' => 50, 'default' => 1, 'comment' => '证件号码'])
            ->addColumn('idNum', 'string', ['limit' => 500, 'comment' => '证件号码'])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '上游商户号'])
            ->addColumn('channelNoticeTime', 'datetime', ['comment' => '支付时间(上游处理时间)', 'null' => true])
            ->addColumn('channelOrderNo', 'string', ['limit' => 50, 'comment' => '渠道订单号', 'null' => true])
            ->addColumn('pushChannelTime', 'datetime', ['comment' => '向上游推送时间', 'null' => true])
            ->addColumn('processType', 'enum', ['values' => ['Expired', 'WaitPayment', 'Success', 'ServiceQuery', 'ManualOperation'], 'comment' => '处理标识-暂定(Expired=已过期,WaitPayment=未支付,Success=成功)'])

        // ->addColumn('transactionNo', 'string', ['limit' => 50, 'comment' => '交易流水号', 'null' => true])
            ->addColumn('tradeSummary', 'string', ['limit' => 100, 'comment' => '交易摘要'])
            ->addColumn('userIp', 'string', ['comment' => '用户IP'])
            ->addColumn('userTerminal', 'enum', ['values' => ['PC', 'Phone', 'Pad'], 'comment' => '用户终端'])
            ->addColumn('thirdUserId', 'string', ['comment' => '商户平台里的付款人ID'])
            ->addColumn('frontNoticeUrl', 'string', ['comment' => '前端通知地址'])
            ->addColumn('backNoticeUrl', 'string', ['comment' => '异步通知地址'])
            ->addColumn('accountDate', 'date', ['comment' => '财务日期', 'null' => true])
            ->addColumn('timeoutTime', 'datetime', ['comment' => '订单超时时间', 'null' => true])
            ->addColumn('serviceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '手续费', 'default' => 0])
            ->addColumn('channelServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '上游手续费', 'default' => 0])
            ->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '支付银行', 'default' => ''])
            ->addColumn('cardHolderMobile', 'string', ['limit' => 500, 'comment' => '付款人手机号码'])
            ->addColumn('cardHolderName', 'string', ['limit' => 50, 'comment' => '付款人姓名'])
            ->addColumn('cardNum', 'string', ['limit' => 500, 'comment' => '银行卡号'])
            ->addColumn('cardType', 'enum', ['values' => [null, 'DEBIT', 'CREDIT'], 'comment' => '银行卡类型(DEBIT=借记卡,CREDIT=信用卡)'])
            ->addColumn('callbackLimit', 'integer', ['default' => 0, 'comment' => '回调次数', 'signed' => false])
            ->addColumn('callbackSuccess', 'boolean', ['default' => false, 'comment' => '回调成功'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantId')
            ->addIndex('platformOrderNo', ['unique' => true])
            ->addIndex('created_at')
            ->save();

        $platformSettlementOrder = $this->table('platform_settlement_order', ['id' => 'orderId', 'comment' => '平台代付订单表', 'signed' => false]);
        $platformSettlementOrder->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
            ->addColumn('merchantOrderNo', 'string', ['limit' => 50, 'comment' => '商户订单号'])
            ->addColumn('merchantParam', 'string', ['limit' => 500, 'comment' => '回传参数'])
            ->addColumn('merchantReqTime', 'datetime', ['comment' => '商户请求时间'])
            ->addColumn('orderAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '订单金额'])
            ->addColumn('realOrderAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '真实订单金额'])
            ->addColumn('serviceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '商户手续费'])
            ->addColumn('channelServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '上游手续费', 'default' => 0])
            ->addColumn('channelSetId', 'integer', ['comment' => 'ID', 'signed' => false])
        // ->addColumn('settlementAccountType', 'enum', ['values' => ['UsableAccount', 'T1Account'], 'comment' => '结算账户类型'])
        // ->addColumn('t0ServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => 'T0手续费'])
        // ->addColumn('t0SettlementAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => 'T0结算金额'])
        // ->addColumn('t1ServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => 'T1手续费'])
        // ->addColumn('t1SettlementAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => 'T1结算金额'])
        // ->addColumn('holidayServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '节假日手续费'])
        // ->addColumn('holidaySettlementAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '节假日结算金额'])
            ->addColumn('failReason', 'string', ['default' => '', 'comment' => '失败原因'])

            ->addColumn('channel', 'string', ['default' => '', 'limit' => 50, 'comment' => '上游渠道'])
            ->addColumn('channelMerchantId', 'integer', ['default' => 0, 'comment' => '上游渠道商户号', 'signed' => false])
            ->addColumn('channelMerchantNo', 'string', ['default' => '', 'limit' => 50, 'comment' => '上游渠道商户号'])
            ->addColumn('channelOrderNo', 'string', ['default' => '', 'limit' => 50, 'comment' => '上游订单号'])
            ->addColumn('channelNoticeTime', 'datetime', ['comment' => '上游通知时间(处理时间)', 'null' => true])
            ->addColumn('orderReason', 'string', ['comment' => '用途'])
            ->addColumn('orderStatus', 'enum', ['values' => ['Transfered', 'Success', 'Fail', 'Exception'], 'comment' => '订单状态(Transfered=已划款,Success=划款成功,Fail=划款失败,Exception=异常)'])
            ->addColumn('orderType', 'enum', ['values' => ['SettlementOrder'], 'default' => 'SettlementOrder', 'comment' => '支付类型'])
            ->addColumn('pushChannelTime', 'datetime', ['comment' => '向上游推送时间', 'null' => true])
            ->addColumn('backNoticeUrl', 'string', ['comment' => '异步通知地址', 'default' => ''])

            ->addColumn('bankLineNo', 'string', ['limit' => 50, 'comment' => '银行编号', 'default' => ''])
            ->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '收款银行', 'default' => ''])
            ->addColumn('bankName', 'string', ['limit' => 50, 'comment' => '开户行'])
            ->addColumn('bankAccountName', 'string', ['limit' => 50, 'comment' => '收款人姓名', 'default' => ''])
            ->addColumn('bankAccountNo', 'string', ['limit' => 500, 'comment' => '收款卡号', 'default' => ''])
            ->addColumn('city', 'string', ['limit' => 50, 'comment' => '开户行所属市', 'default' => ''])
            ->addColumn('province', 'string', ['limit' => 50, 'comment' => '开户行所属省', 'default' => ''])
            ->addColumn('userIp', 'string', ['comment' => '用户IP', 'default' => ''])
            ->addColumn('applyPerson', 'string', ['limit' => 50, 'comment' => '申请人', 'default' => ''])
            ->addColumn('applyIp', 'string', ['limit' => 50, 'comment' => '申请人IP', 'default' => ''])
            ->addColumn('accountDate', 'date', ['comment' => '财务日期', 'null' => true])
            ->addColumn('auditPerson', 'string', ['limit' => 50, 'comment' => '审核人', 'default' => ''])
            ->addColumn('auditIp', 'string', ['limit' => 50, 'comment' => '审核人IP', 'default' => ''])
            ->addColumn('auditTime', 'datetime', ['comment' => '审核时间', 'null' => true])

        // ->addColumn('transactionNo', 'string', ['limit' => 50, 'comment' => '交易流水号'])
            ->addColumn('tradeSummary', 'string', ['comment' => '交易摘要'])

            ->addColumn('processType', 'enum', ['values' => ['Expired', 'WaitPayment', 'Success', 'ServiceQuery', 'ManualOperation'], 'default' => 'WaitPayment', 'comment' => '处理标识-暂定(Expired=已过期,WaitPayment=未支付,Success=成功)'])
            ->addColumn('callbackLimit', 'integer', ['default' => 0, 'comment' => '回调次数', 'signed' => false])
            ->addColumn('callbackSuccess', 'boolean', ['default' => false, 'comment' => '回调成功'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('merchantId')
            ->addIndex('platformOrderNo', ['unique' => true])
            ->addIndex('created_at')
            ->save();

        $balanceAdjustment = $this->table('balance_adjustment', ['id' => 'adjustmentId', 'comment' => '商户余额调整', 'signed' => false]);
        $balanceAdjustment->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '金额'])
        // ->addColumn('adjustmentId', 'integer', ['comment' => 'ID'])
            ->addColumn('applyPerson', 'string', ['limit' => 50, 'comment' => '申请人'])
            ->addColumn('auditPerson', 'string', ['limit' => 50, 'comment' => '审核人'])
            ->addColumn('auditTime', 'datetime', ['comment' => '审核时间'])

            ->addColumn('bankrollDirection', 'enum', ['values' => ['Restore', 'Retrieve'], 'comment' => '资金方向(Restore=返还, Retrieve=追收)'])
            ->addColumn('bankrollType', 'enum', ['values' => ['AccountBalance', 'ServiceCharge'], 'comment' => '资金类型(AccountBalance=账户资金, ServiceCharge=手续费)'])

            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '审核状态'])
            ->addColumn('summary', 'string', ['limit' => 50, 'comment' => '摘要'])

            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('merchantNo', 'string', ['limit' => 50, 'comment' => '商户号'])
        // ->addColumn('transactionNo', 'string', ['limit' => 50, 'comment' => '交易流水号'])
            ->addColumn('platformOrderNo', 'string', ['comment' => '平台订单号'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('platformOrderNo', ['unique' => true])
            ->addIndex('created_at')
            ->save();

        $systemAccount = $this->table('system_account', ['comment' => '管理账号表', 'signed' => false]);
        $systemAccount->addColumn('userName', 'string', ['comment' => '用户名称'])
            ->addColumn('loginName', 'string', ['comment' => '登录账号'])
            ->addColumn('loginPwd', 'string', ['comment' => '密码'])
            ->addColumn('loginFailNum', 'integer', ['limit' => 50, 'comment' => '登录失败次数', 'default' => 0, 'signed' => false])
            ->addColumn('loginPwdAlterTime', 'datetime', ['comment' => '密码修改时间', 'null' => true])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'comment' => '状态', 'default' => 'Normal'])
            ->addColumn('googleAuthSecretKey', 'string', ['limit' => 500, 'comment' => '谷歌auth密钥', 'default' => ''])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('loginName', ['unique' => true])
            ->save();

        $systemAccountActionLog = $this->table('system_account_action_log', ['comment' => '管理账号操作日志', 'signed' => false]);
        $systemAccountActionLog->addColumn('action', 'enum', ['values' => ['UPDATE_PASSWORD', 'UPDATE_LOGINNAME',
            'UPDATE_CHANNEL_MERCHANT', 'CREATE_CHANNEL_MERCHANT', 'IMPORT_CHANNEL_MERCHANT_RATE', 'CREATE_MERCHANT',
            'UPDATE_MERCHANT', 'CREATE_MERCHANT_ACCOUNT', 'UPDATE_MERCHANT_ACCOUNT', 'UPDATE_MERCHANT_ACCOUNT_PASSWORD',
            'UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD', 'UPDATE_MERCHANT_SIGNKEY', "IMPORT_MERCHANT_RATE", "IMPORT_MERCHANT_CHANNEL_PAY",
            "IMPORT_MERCHANT_CHANNEL_SETTLEMENT", 'CREATE_BALANCE_ADJUSTMENT', 'UPDATE_BALANCE_ADJUSTMENT',
            'MANUAL_PLATFORMPAYORDER', 'MANUAL_PLATFORMSETTLEMENTORDER', 'BIND_GOOGLE_AUTH',
        ], 'comment' => '行为'])
            ->addColumn('actionBeforeData', 'text', ['null' => true, 'comment' => '操作前数据', 'limit' => MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('actionAfterData', 'text', ['null' => true, 'comment' => '操作后数据', 'limit' => MysqlAdapter::TEXT_MEDIUM])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '状态'])
            ->addColumn('accountId', 'integer', ['comment' => 'accountId', 'signed' => false])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('accountId')
            ->save();

        $systemAccountLoginLog = $this->table('system_account_login_log', ['comment' => '管理账号登录日志', 'signed' => false]);
        $systemAccountLoginLog->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '状态'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('accountId', 'integer', ['comment' => 'accountId', 'signed' => false])
            ->addColumn('remark', 'enum', ['values' => ['PASSWORD_ERROR', 'LOGIN_FAIL_COUNT', ''], 'comment' => 'remark(PASSWORD_ERROR=密码错误,LOGIN_FAIL_COUNT=密码登录错误次数过多)', 'default' => ''])
            ->addIndex('accountId')
            ->save();

        $merchantAccountActionLog = $this->table('merchant_account_action_log', ['comment' => '商户账号操作日志', 'signed' => false]);
        $merchantAccountActionLog->addColumn('action', 'enum', ['values' => ['UPDATE_PASSWORD', 'UPDATE_PAY_PASSWORD', "BIND_GOOGLE_AUTH"], 'comment' => '行为'])
            ->addColumn('actionBeforeData', 'text', ['null' => true, 'comment' => '操作前数据'])
            ->addColumn('actionAfterData', 'text', ['null' => true, 'comment' => '操作后数据'])
            ->addColumn('accountId', 'integer', ['comment' => 'accountId', 'signed' => false])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '状态'])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('accountId')
            ->save();

        $merchantAccountLoginLog = $this->table('merchant_account_login_log', ['comment' => '商户账号登录日志', 'signed' => false]);
        $merchantAccountLoginLog->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '状态'])
            ->addColumn('accountId', 'integer', ['comment' => 'accountId', 'signed' => false])
            ->addColumn('remark', 'enum', ['values' => ['PASSWORD_ERROR', 'LOGIN_FAIL_COUNT', ''], 'comment' => 'remark(PASSWORD_ERROR=密码错误,LOGIN_FAIL_COUNT=密码登录错误次数过多)', 'default' => ''])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('accountId')
            ->save();

        $settlementFetchTask = $this->table('settlement_fetch_task', ['comment' => '结算轮循任务', 'signed' => false]);
        $settlementFetchTask->addColumn('status', 'enum', ['values' => ['Success', 'Fail', 'Execute', 'WaitTransfer'], 'default' => 'Execute'])
            ->addColumn('retryCount', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('failReason', 'string', ['default' => ''])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['updated_at', 'status'], ['name' => 'idx_updated_at_status'])
            ->addIndex('created_at')
            ->save();

        $settlementPushTask = $this->table('settlement_push_task', ['comment' => '结算回调任务', 'signed' => false]);
        $settlementPushTask->addColumn('thirdParams', 'string', ['limit' => 2000])
            ->addColumn('standardParams', 'string', ['limit' => 2000])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail', 'Execute'], 'default' => 'Execute'])
            ->addColumn('retryCount', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('failReason', 'string', ['default' => ''])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['updated_at', 'status'], ['name' => 'idx_updated_at_status'])
            ->addIndex('created_at')
            ->save();

        $settlementNotifyTask = $this->table('settlement_notify_task', ['comment' => '结算通知任务', 'signed' => false]);
        $settlementNotifyTask->addColumn('status', 'enum', ['values' => ['Success', 'Fail', 'Execute'], 'default' => 'Execute'])
            ->addColumn('retryCount', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('failReason', 'string', ['default' => ''])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['updated_at', 'status'], ['name' => 'idx_updated_at_status'])
            ->addIndex('created_at')
            ->save();

        $settlementActiveQueryTask = $this->table('settlement_active_query_task', ['comment' => '结算查询任务', 'signed' => false]);
        $settlementActiveQueryTask->addColumn('status', 'enum', ['values' => ['Success', 'Fail', 'Execute'], 'default' => 'Execute'])
            ->addColumn('retryCount', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('failReason', 'string', ['default' => ''])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['updated_at', 'status'], ['name' => 'idx_updated_at_status'])
            ->addIndex('created_at')
            ->save();

        $payPushTask = $this->table('pay_push_task', ['comment' => '支付回调任务', 'signed' => false]);
        $payPushTask->addColumn('thirdParams', 'string', ['limit' => 2000])
            ->addColumn('standardParams', 'string', ['limit' => 2000])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail', 'Execute'], 'default' => 'Execute'])
            ->addColumn('retryCount', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('failReason', 'string', ['default' => ''])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['updated_at', 'status'], ['name' => 'idx_updated_at_status'])
            ->addIndex('created_at')
            ->save();

        $payNotifyTask = $this->table('pay_notify_task', ['comment' => '支付通知任务', 'signed' => false]);
        $payNotifyTask->addColumn('status', 'enum', ['values' => ['Success', 'Fail', 'Execute'], 'default' => 'Execute'])
            ->addColumn('retryCount', 'integer', ['default' => 0, 'signed' => false])
            ->addColumn('failReason', 'string', ['default' => ''])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['updated_at', 'status'], ['name' => 'idx_updated_at_status'])
            ->addIndex('created_at')
            ->save();

        $historyBackups = $this->table('history_backups', ['comment' => '数据迁移表', 'limit' => MysqlAdapter::INT_BIG, 'signed' => false]);
        $historyBackups->addColumn('type', 'integer', ['limit' => MysqlAdapter::INT_TINY, 'signed' => false])
            ->addColumn('text', 'text')
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->save();

    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->dropTable('merchant');
        $this->dropTable('merchant_user');
        $this->dropTable('merchant_amount');
        $this->dropTable('merchant_channel');
        $this->dropTable('merchant_channel_settlement');
        $this->dropTable('merchant_rate');
        // $this->dropTable('channel');
        $this->dropTable('channel_merchant');
        $this->dropTable('channel_merchant_rate');
        // $this->dropTable('platform');

        $this->dropTable('amount_pay');
        $this->dropTable('amount_settlement');
        $this->dropTable('finance');

        $this->dropTable('platform_pay_order');
        $this->dropTable('platform_settlement_order');

        $this->dropTable('balance_adjustment');
        $this->dropTable('system_account');
        $this->dropTable('system_account_action_log');
        $this->dropTable('system_account_login_log');
        $this->dropTable('merchant_account_action_log');
        $this->dropTable('merchant_account_login_log');

        $this->dropTable('settlement_fetch_task');
        $this->dropTable('settlement_push_task');
        $this->dropTable('settlement_notify_task');
        $this->dropTable('settlement_active_query_task');
        $this->dropTable('pay_push_task');
        $this->dropTable('pay_notify_task');
        $this->dropTable('history_backups');
    }
}
