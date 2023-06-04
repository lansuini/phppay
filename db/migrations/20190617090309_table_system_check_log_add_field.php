<?php

use Phinx\Migration\AbstractMigration;

class TableSystemCheckLogAddField extends AbstractMigration
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
    public function up()
    {
        $merchant = $this->table('system_check_log');
        $merchant->addColumn('relevance', 'string', ['limit' => 200, 'comment' => '关联字段','default' => '','null' => true,])
            ->addColumn('check_time', 'string', ['limit' => 50, 'comment' => '审核时间','default' => '','null' => true,])
            ->addColumn('check_ip', 'string', ['limit' => 50, 'comment' => '审核人ip','default' => '','null' => true,])
            ->update();
    }
}
