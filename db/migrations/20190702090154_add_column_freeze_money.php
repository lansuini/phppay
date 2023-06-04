<?php

use Phinx\Migration\AbstractMigration;

class AddColumnFreezeMoney extends AbstractMigration
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
        $balanceAdjustment = $this->table("balance_adjustment");
        $balanceAdjustment->changeColumn('bankrollDirection', 'enum', ['values' => ['Restore', 'Retrieve','Freeze','Unfreeze'], 'comment' => '资金方向(Restore=返还, Retrieve=追收, Freeze=冻结， Unfreeze=解冻)'])
            ->changeColumn("status",'enum',['values' => ['Success', 'Fail', 'Freeze', "Unaudit"], 'comment' => '审核状态(通过，不通过，待解冻, 待审核)'])
            ->changeColumn('auditPerson', 'string', ['limit' => 50, 'comment' => '审核人', 'null' => true])
            ->changeColumn('auditTime', 'datetime', ['comment' => '审核时间', 'null' => true])
        ->save();

        $merchantAmount = $this->table("merchant_amount");
        $merchantAmount->addColumn("freezeAmount", 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'default' => 0, 'comment' => '冻结金额', 'after'=> 'settlementAmount'])
            ->save();


    }

    public function down()
    {
        $balanceAdjustment = $this->table("balance_adjustment");
        $balanceAdjustment->changeColumn('bankrollDirection', 'enum', ['values' => ['Restore', 'Retrieve'], 'comment' => '资金方向(Restore=返还, Retrieve=追收)'])
            ->changeColumn('status', 'enum', ['values' => ['Success', 'Fail'], 'comment' => '审核状态'])
            ->changeColumn('auditPerson', 'string', ['limit' => 50, 'comment' => '审核人'])
            ->changeColumn('auditTime', 'datetime', ['comment' => '审核时间'])
        ->save();

        $merchantAmount = $this->table("merchant_amount");
        $merchantAmount->removeColumn('freezeAmount')
            ->save();
    }
}
