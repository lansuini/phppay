<?php

use Phinx\Migration\AbstractMigration;

class TableSystemCheckLog extends AbstractMigration
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
        $merchantAudit = $this->table('system_check_log', ['id' => 'id', 'comment' => '审核表', 'signed' => false]);
        $merchantAudit->addColumn('admin_id', 'integer', ['limit' => 50,'default' => 0, 'comment' => '审核人uid'])
            ->addColumn('commiter_id', 'integer', ['default' => 0, 'comment' => '提审人uid'])
            ->addColumn('status', 'enum', ['values' => ['0', '1', '2'], 'default' => '0', 'comment' => '状态2审核不通过1审核通过0待审核'])
            ->addColumn('content',  'text', ['null' => true, 'comment' => '记录'])
            ->addColumn('desc', 'text', ['null' => true, 'comment' => '备注，记录修改内容'])
            ->addColumn('type', 'string', ['default' => '', 'comment' => '类型'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->save();
    }
}
