<?php

use Phinx\Migration\AbstractMigration;

class TableFinanceAddField extends AbstractMigration
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
        $merchant = $this->table('finance');
        $merchant->addColumn('merchantOrderNo', 'string', ['limit' => 50, 'comment' => '商户订单号','default' => '','null' => true,])
            ->addColumn('operateSource', 'enum', ['values' => ['ports', 'merchant','admin'], 'default' => 'ports','comment' => '操作来源(ports:接口,merchant:商户后台,admin:管理后台)'])
            ->update();
            
    }
}
