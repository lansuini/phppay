<?php

use Phinx\Migration\AbstractMigration;

class AddSettlementTransfertimes extends AbstractMigration
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
        $amountSettlement = $this->table('amount_settlement');
        $amountSettlement->addColumn('transferTimes','integer',['default'=>0,'comment'=>'代付次数','after'=>'accountDate']);
        $amountSettlement->save();

        $pOrder = $this->table('platform_settlement_order');
        $pOrder->addIndex('accountDate');
        $pOrder->save();
    }

    public function down()
    {

    }
}
