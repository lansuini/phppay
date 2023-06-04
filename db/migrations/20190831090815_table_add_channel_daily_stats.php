<?php


use Phinx\Migration\AbstractMigration;

class TableAddChannelDailyStats extends AbstractMigration
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
    public function change()
    {
        $table = $this->table('channel_daily_stats', ['id' => 'dailyId', 'comment' => '上游渠道每日数据统计', 'signed' => false]);
        $table
            ->addColumn('channelMerchantId', 'integer', ['signed' => false,'comment' => '渠道ID', 'default' =>0])
            ->addColumn('channelMerchantNo', 'integer', ['signed' => false,'comment' => '渠道号', 'default' =>0])
            ->addColumn('payCount', 'integer', ['signed' => false,'comment' => '今日支付笔数', 'default' =>0])
            ->addColumn('payAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日支付金额', 'default' =>0])
            ->addColumn('payServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日支付手续费', 'default' =>0])
            ->addColumn('payChannelServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日上游支付手续费', 'default' =>0])
            ->addColumn('settlementCount', 'integer', ['signed' => false,'comment' => '今日代付笔数', 'default' =>0])
            ->addColumn('settlementAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日代付金额', 'default' =>0])
            ->addColumn('settlementServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日代付手续费', 'default' =>0])
            ->addColumn('settlementChannelServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日上游代付手续费', 'default' =>0])
            ->addColumn('chargeCount', 'integer', ['signed' => false,'comment' => '今日充值笔数', 'default' =>0])
            ->addColumn('chargeAmount', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日充值金额', 'default' =>0])
            ->addColumn('chargeServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日充值手续费', 'default' =>0])
            ->addColumn('chargeChannelServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '今日上游充值手续费', 'default' =>0])
            ->addColumn('accountDate', 'date', ['comment' => '财务日期'])
            ->addColumn('agentPayFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '代理支付手续费', 'default' =>0])
            ->addColumn('agentsettlementFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '代理代付手续费', 'default' =>0])
            ->addColumn('agentchargeFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '代理充值手续费', 'default' =>0])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['accountDate'])
            ->create();
    }
}
