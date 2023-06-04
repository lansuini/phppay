<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentReport extends AbstractMigration
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

        $merchantChannel = $this->table('agent_report', ['id' => 'id', 'comment' => '代理数据报表', 'signed' => false]);
        $merchantChannel->addColumn('agentId', 'integer', ['comment' => '代理ID', 'null' => true])
            ->addColumn('agentName', 'string', ['limit' => 64, 'comment' => '代理名称'])
            ->addColumn('addMerchant', 'integer', ['default' => 0,'comment' => '新增下级'])
            ->addColumn('commCount', 'integer', ['default' => 0, 'comment' => '佣金笔数'])
            ->addColumn('commMoney', 'decimal', ['default' => 0,'precision' => 10, 'scale' => 2, 'comment' => '佣金金额'])
            ->addColumn('settCommCount', 'integer', ['default' => 0, 'comment' => '下发佣金笔数'])
            ->addColumn('settCommMoney', 'decimal', ['default' => 0,'precision' => 10, 'scale' => 2, 'comment' => '下发佣金金额'])
            ->addColumn('withdrewCount', 'integer', ['default' => 0,'comment' => '提款笔数'])
            ->addColumn('withdrewMoney', 'decimal', ['default' => 0,'precision' => 10, 'scale' => 2,  'comment' => '提款金额'])
            ->addColumn('withdrewFee', 'decimal', ['default' => 0,'precision' => 10, 'scale' => 2, 'comment' => '提款手续费'])
            ->addColumn('commWays', 'string', ['limit' => 4, 'comment' => '佣金结算方式D0 D1 D7 D30'])
            ->addColumn('accountDate', 'date', ['comment' => '财务日期'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('id')
            ->addIndex('agentId')
            ->addIndex('accountDate')
            ->create();
    }
}
