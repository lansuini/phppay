<?php


use Phinx\Migration\AbstractMigration;

class TableUpdateMerchantDailyStats extends AbstractMigration
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
        $table = $this->table('merchant_daily_stats');
        $table->addColumn('payChannelServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '今日上游支付手续费', 'default' =>0, 'after'=>'payServiceFees'])
            ->addColumn('settlementChannelServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '今日上游代付手续费', 'default' =>0, 'after'=>'settlementServiceFees'])
            ->addColumn('chargeChannelServiceFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '今日上游充值手续费', 'default' =>0, 'after'=>'chargeServiceFees'])
            ->update();
    }
}
