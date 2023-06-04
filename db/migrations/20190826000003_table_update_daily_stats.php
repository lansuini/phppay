<?php

use Phinx\Migration\AbstractMigration;

class TableUpdateDailyStats extends AbstractMigration
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
        $table
            ->addColumn('channelMerchantId', 'integer', ['signed' => false,'comment' => '渠道ID'])
            ->addColumn('channelMerchantNo', 'integer', ['signed' => false,'comment' => '渠道号'])
            ->addColumn('agentPayFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '代理支付手续费', 'default' =>0])
            ->addColumn('agentsettlementFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '代理代付手续费', 'default' =>0])
            ->addColumn('agentchargeFees', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false,'comment' => '代理充值手续费', 'default' =>0])
            ->update();
    }
}
