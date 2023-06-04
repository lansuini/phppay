<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentRelation extends AbstractMigration
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

        $merchantAccountActionLog = $this->table('agent_merchant_relation', ['comment' => '代理商户关系表', 'signed' => false]);
        $merchantAccountActionLog->addColumn('agentId', 'integer', ['comment' => '代理ID', 'signed' => false])
            ->addColumn('merchantId', 'integer', ['comment' => '商户ID', 'signed' => false])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('agentId')
            ->addIndex('merchantId')
            ->create();
    }
}
