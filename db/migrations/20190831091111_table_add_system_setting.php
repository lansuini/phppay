<?php


use Phinx\Migration\AbstractMigration;

class TableAddSystemSetting extends AbstractMigration
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
        $table = $this->table('system_config', ['id' => 'id', 'comment' => '', 'signed' => false]);
        $table
            ->addColumn('module', 'string', ['limit' => 64, 'comment' => '模块名'])
            ->addColumn('name', 'string', ['limit' => 64, 'comment' => ''])
            ->addColumn('type', 'string', ['limit' => 10, 'comment' => '字段类型int,bool,string,json'])
            ->addColumn('key', 'string', ['limit' => 64, 'comment' => ''])
            ->addColumn('value', 'string', ['limit' => 255, 'comment' => ''])
            ->addColumn('desc', 'string', ['limit' => 255, 'comment' => ''])
            ->addColumn('state', 'enum', ['values' => ['enabled', 'disabled']])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['module'])
            ->create();
    }
}
