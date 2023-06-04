<?php

use Phinx\Migration\AbstractMigration;

class TableUpdateBalanceIssue extends AbstractMigration
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
        $table = $this->table('channel_balance_issue');
        $table
            ->addColumn('type', 'string', ['limit' => 64, 'default'=>'system', 'comment' => '使用场景/类型'])
            ->addColumn('classOpt', 'string', ['limit' => 255, 'default'=>'', 'comment' => '查询结果之后需要调用的方法处理后续逻辑JSON格式'])
            ->addColumn('foreign_id', 'integer', ['default'=>0, 'comment' => '对应场景的ID'])
            ->update();
    }
}
