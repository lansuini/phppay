<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentBank extends AbstractMigration
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

        $table = $this->table('agent_bank_card', ['comment' => '代理后台提现银行卡信息', 'signed' => false]);
        $table->addColumn('bankCode', 'string', ['limit' => 50, 'default' => '', 'comment' => '所属银行'])
            ->addColumn('bankName', 'string', ['limit' => 50, 'default' => '', 'comment' => '开户行'])
            ->addColumn('province', 'string', ['limit' => 50, 'default' => '', 'comment' => '开户行所属省'])
            ->addColumn('city', 'string', ['limit' => 50, 'default' => '', 'comment' => '开户行所属市'])
            ->addColumn('district', 'string', ['limit' => 50, 'default' => '', 'comment' => '开户行所属区'])
            ->addColumn('accountName', 'string', ['limit' => 50, 'default' => '', 'comment' => '持卡人姓名'])
            ->addColumn('accountNo', 'string', ['limit' => 500, 'default' => '', 'comment' => '银行卡号'])
            ->addColumn('agentId', 'string', ['limit' => 500, 'default' => '', 'comment' => '商户号ID'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Deleted'], 'default' => 'Normal', 'comment' => '状态(正常、删除)'])
            ->addIndex('agentId')
            ->create();
    }
}
