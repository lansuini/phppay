<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgentRate extends AbstractMigration
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

        $merchantRate = $this->table('agent_rate', ['id' => 'rateId', 'comment' => '商户费率表', 'signed' => false]);
        $merchantRate->addColumn('bankCode', 'string', ['limit' => 50, 'comment' => '银行', 'default' => ''])
            ->addColumn('cardType', 'enum', ['values' => [null, 'DEBIT', 'CREDIT'], 'comment' => '银行卡类型(DEBIT=借记卡,CREDIT=信用卡)'])
            // ->addColumn('channel', 'string', ['comment' => '支付密码'])
            ->addColumn('beginTime', 'date', ['comment' => '生效时间', 'null' => true])
            ->addColumn('endTime', 'date', ['comment' => '失效时间', 'null' => true])
            ->addColumn('maxServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '最大手续费', 'default' => 0])
            ->addColumn('minServiceCharge', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '最小手续费', 'default' => 0])
            ->addColumn('agentId', 'integer', ['comment' => '代理商户ID', 'signed' => false])
            ->addColumn('agentLoginName', 'string', ['limit' => 50, 'comment' => '代理商户号'])
            ->addColumn('payType', 'enum', ['values' => ['EBank', 'Quick', 'OfflineWechatQR',
                'OfflineAlipayQR', 'OnlineWechatQR', 'OnlineAlipayQR', 'OnlineWechatH5', 'OnlineAlipayH5',
                'UnionPayQR', 'D0Settlement'],
                'comment' => '支付方式(EBank=网银,Quick=快捷,OfflineWechatQR=线下微信扫码,OfflineAlipayQR=线下支付宝扫码,
            OnlineWechatQR=线上微信扫码,OnlineAlipayQR=线上支付宝扫码,OnlineWechatH5=线上微信H5,OnlineAlipayH5=线上支付宝H5,
            UnionPayQR=银联扫码,D0Settlement=D0结算)'])
            ->addColumn('productType', 'enum', ['values' => ['Pay','Recharge' ,'Settlement'], 'default' => 'Pay', 'comment' => '产品类型'])
            ->addColumn('rate', 'float', ['signed' => false, 'comment' => '费率值'])
            ->addColumn('fixed', 'float', ['signed' => false, 'comment' => '固定费率值', 'default' => 0])
            ->addColumn('rateType', 'enum', ['values' => ['Rate','Mixed', 'FixedValue'], 'default' => 'Rate', 'comment' => '费率类型'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Close'], 'default' => 'Normal', 'comment' => '状态'])
            // ->addColumn('channel', 'string', ['limit' => 50, 'comment' => '渠道名称'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('agentId')
            ->create();
    }
}
