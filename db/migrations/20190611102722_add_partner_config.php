<?php

use Phinx\Migration\AbstractMigration;

class AddPartnerConfig extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up()
    {

        $merchantChannel = $this->table('channel_pay_config', ['id' => 'setId', 'comment' => '上游支付渠道配置表', 'signed' => false]);
        $merchantChannel->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '银行', 'null' => true])
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
        UnionPayQR=银联扫码,D0Settlement=D0代付)', ])
            ->addColumn('openTimeLimit', 'boolean', ['default' => false, 'comment' => '是否开启控制交易时间'])
            ->addColumn('beginTime', 'integer', ['default' => 0, 'comment' => '开始时间(00:00格式转整形)', 'signed' => false])
            ->addColumn('endTime', 'integer', ['default' => 0, 'comment' => '结束时间(00:00格式转整形)', 'signed' => false])
            ->addColumn('openOneAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启控制单笔金额控制'])
            ->addColumn('oneMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单笔最大金额'])
            ->addColumn('oneMinAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单笔最小金额'])
            ->addColumn('openDayAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计金额控制'])
            ->addColumn('dayAmountLimit', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '累计金额限制'])
            ->addColumn('openDayNumLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计笔数控制'])
            ->addColumn('dayNumLimit', 'integer', ['default' => 0, 'comment' => '累计次数限制', 'signed' => false])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '配置状态'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('channelMerchantId')
            ->addIndex('channelMerchantNo')
            ->save();
    }
}
