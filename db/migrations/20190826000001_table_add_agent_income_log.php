<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentIncomeLog extends AbstractMigration
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

        $merchantChannel = $this->table('agent_income_log', ['id' => 'id', 'comment' => '代理收入手续费结算日志表', 'signed' => false]);
        $merchantChannel->addColumn('agentId', 'integer', ['comment' => '代理ID', 'null' => true])
            ->addColumn('platformOrderNo', 'string', ['limit' => 21, 'comment' => '平台订单号'])
            ->addColumn('orderMoney', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '订单收费金额'])
            ->addColumn('fee', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '手续费收入'])
            ->addColumn('ways', 'string', ['limit' => 64, 'comment' => '计算方式json格式'])
            ->addColumn('type', 'enum', ['values' => ['pay', 'recharge', 'settlement'],
                'comment' => '交易类型(pay=支付,recharge=充值,settlement=代付', ])
            ->addColumn('typeSub', 'string', ['default' => '','limit' => 32, 'comment' => '具体的方式，如支付下的支付宝，微信等，代付无可为空'])
            ->addColumn('bankCode', 'string', ['default' => '','limit' => 32, 'comment' => '主要针对银行支付'])
            ->addColumn('isSettle', 'integer', ['comment' => '是否结算（1已结算 ，0未结算）'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('id')
            ->addIndex('agentId')
            ->addIndex('type')
            ->addIndex('platformOrderNo')
            ->create();
    }
}
