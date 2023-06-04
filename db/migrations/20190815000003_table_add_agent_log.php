<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentLog extends AbstractMigration
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

        $merchantAccountActionLog = $this->table('agent_log', ['comment' => '代理日志', 'signed' => false]);
        $merchantAccountActionLog->addColumn('action', 'string', ['limit' => 50, 'comment' => '行为，类型定义在Agent模型中'])
            ->addColumn('actionBeforeData', 'text', ['null' => true, 'comment' => '操作前数据'])
            ->addColumn('actionAfterData', 'text', ['null' => true, 'comment' => '操作后数据'])
            ->addColumn('optId', 'integer', ['comment' => '操作者ID', 'signed' => false])
            ->addColumn('optName', 'string', ['limit' => 50, 'comment' => '操作者名'])
            ->addColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '状态'])
            ->addColumn('desc', 'string', ['limit' => 255, 'comment' => '描述备注'])
            ->addColumn('ipDesc', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('ip', 'string', ['limit' => 50, 'comment' => 'ip'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('optName')
            ->addIndex('ip')
            ->create();
    }
}
