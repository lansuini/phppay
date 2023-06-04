<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentFinance extends AbstractMigration
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

        $merchantChannel = $this->table('agent_finance', ['id' => 'id', 'comment' => '代理资金明细表', 'signed' => false]);
        $merchantChannel->addColumn('agentId', 'integer', ['comment' => '代理ID', 'null' => true])
            ->addColumn('agentName', 'string', ['limit' => 64, 'comment' => '代理名称'])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('dealMoney', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '操作金额'])
            ->addColumn('balance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '余额即可提余额'])
            ->addColumn('freezeBalance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '冻结金额'])
            ->addColumn('bailBalance', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '保证金'])
            ->addColumn('dealType', 'enum', ['values' => ['reduceBail', 'addBail', 'freeze', 'addFreeze', 'commission', 'extract','extractSuc','extractFail','returnFee'],
                'comment' => '交易类型(reduceBail=减少保证金,addBail=增加保证金,freeze=解结金额,addFreeze=增加冻结金额,commission=佣金提成,extract=提款冻结,extractSuc=提款成功,extractFail=提款失败,extractFee=手续费,returnFee=退还手续费)', ])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Exception', 'Close'],
                'comment' => '状态(Normal=正常,Close=关闭,Exception=异常)', ])
            ->addColumn('optId', 'string', ['default' => '','limit' => 50, 'comment' => '操作者ID'])
            ->addColumn('optAdmin', 'string', ['default' => '','limit' => 50, 'comment' => '操作者名'])
            ->addColumn('optIP', 'string', ['default' => '','limit' => 50, 'comment' => '操作者IP'])
            ->addColumn('optDesc', 'string', ['default' => '','limit' => 255, 'comment' => '操作备注'])
            ->addColumn('desc', 'string', ['default' => '','limit' => 255, 'comment' => '说明'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('id')
            ->addIndex('agentName')
            ->addIndex('dealType')
            ->create();
    }
}
