<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentWithdraw extends AbstractMigration
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

        $merchantChannel = $this->table('agent_withdraw_order', ['id' => 'id', 'comment' => '代理提现表', 'signed' => false]);
        $merchantChannel->addColumn('agentId', 'integer', ['comment' => '代理ID', 'null' => true])
            ->addColumn('agentName', 'string', ['limit' => 64, 'comment' => '代理名称'])
            ->addColumn('bankId', 'integer', ['comment' => '代理银行卡ID', 'null' => true])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('dealMoney', 'decimal', ['default' => 0, 'precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '申请提现金额'])
            ->addColumn('realMoney', 'decimal', ['default' => 0, 'precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '实际到账金额'])
            ->addColumn('fee', 'decimal', ['default' => 0, 'precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '提现手续费'])
            ->addColumn('status', 'enum', ['default' => 'Apply', 'values' => ['Apply', 'Adopt', 'Refute', 'Complete'],
                'comment' => '状态(Apply=申请中,Adopt=通过,Refute=拒绝,Complete=成功打款/完成)',])
            ->addColumn('optId', 'string', ['default' => '', 'limit' => 50, 'comment' => '操作者ID'])
            ->addColumn('optAdmin', 'string', ['default' => '', 'limit' => 50, 'comment' => '操作者名'])
            ->addColumn('optIP', 'string', ['default' => '', 'limit' => 50, 'comment' => '操作者IP'])
            ->addColumn('optDesc', 'string', ['default' => '', 'limit' => 255, 'comment' => '操作备注'])
            ->addColumn('appIP', 'string', ['default' => '', 'limit' => 50, 'comment' => '申请者IP'])
            ->addColumn('appDesc', 'string', ['default' => '', 'limit' => 255, 'comment' => '申请者备注'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('id')
            ->addIndex('platformOrderNo')
            ->create();
    }
}
