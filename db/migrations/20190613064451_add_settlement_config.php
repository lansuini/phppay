<?php

use Phinx\Migration\AbstractMigration;

class AddSettlementConfig extends AbstractMigration
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

        $merchantChannelSettlement = $this->table('channel_settlement_config', ['id' => 'setId', 'comment' => '上游结算渠道配置表', 'signed' => false]);
        $merchantChannelSettlement->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道名称'])
            ->addColumn('channelMerchantId', 'integer', ['comment' => '渠道商户'])
            ->addColumn('channelMerchantNo', 'string', ['limit' => 50, 'comment' => '渠道商户号'])
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
            ->addColumn('openDayNumLimit', 'boolean', ['default' => false, 'comment' => '是否开启单日累计笔数控制'])
            ->addColumn('dayNumLimit', 'integer', ['default' => 0, 'comment' => '累计次数限制', 'signed' => false])
            ->addColumn('openCardDayNumLimit', 'boolean', ['default' => false, 'comment' => '是否开启单卡单日累计笔数控制'])
            ->addColumn('cardDayNumLimit', 'integer', ['default' => 0, 'comment' => '累计次数限制', 'signed' => false])
            ->addColumn('openOneSettlementMaxAmountLimit', 'boolean', ['default' => false, 'comment' => '是否开启单卡单日最大结算金额控制'])
            ->addColumn('oneSettlementMaxAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '单卡单日最大结算金额', 'default' => 500000])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '配置状态'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('channelMerchantId')
            ->addIndex('channelMerchantNo')
            ->save();

    }
}
