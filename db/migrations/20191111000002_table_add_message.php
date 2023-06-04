<?php

use Phinx\Migration\AbstractMigration;

class TableAddMessage extends AbstractMigration
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

        $merchantChannel = $this->table('message', ['id' => 'id', 'comment' => '留言信息表', 'signed' => false]);
        $merchantChannel
            ->addColumn('nickName', 'string', ['limit' => 64, 'comment' => '留言姓名'])
            ->addColumn('whatAPP', 'string', ['limit' => 50, 'comment' => 'whatAPP账号'])
            ->addColumn('telegram', 'string', ['limit' => 50, 'comment' => 'telegram账号'])
            ->addColumn('email', 'string', ['limit' => 50, 'comment' => '电子邮箱'])
            ->addColumn('skype', 'string', ['limit' => 50, 'comment' => 'skype'])
            ->addColumn('message', 'string', ['limit' => 2000, 'comment' => '留言内容'])
            ->addColumn('remarks', 'string', ['limit' => 1000, 'comment' => '备注'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('id')
            ->create();
    }
}
