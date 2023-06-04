<?php

use Phinx\Migration\AbstractMigration;

class TableAddAgent extends AbstractMigration
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

        $merchantChannel = $this->table('agent', ['id' => 'id', 'comment' => '代理表', 'signed' => false]);
        $merchantChannel->addColumn('loginName', 'string', ['limit' => 50, 'comment' => '登陆用户名'])
            ->addColumn('nickName', 'string', ['limit' => 64, 'comment' => '代理昵称'])
            ->addColumn('loginPwd', 'string', ['limit' => 50, 'comment' => '登陆密码'])
            ->addColumn('securePwd', 'string', ['limit' => 50, 'comment' => '支付密码'])
            ->addColumn('balance', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0 , 'signed' => false, 'comment' => '余额即可提余额'])
            ->addColumn('freezeBalance', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0 , 'signed' => false, 'comment' => '冻结金额'])
            ->addColumn('bailBalance', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0 , 'signed' => false, 'comment' => '保证金'])
            ->addColumn('inferisorNum', 'integer', ['comment' => '下级商户人数', 'default' => 0])
            ->addColumn('settleAccWay', 'enum', ['values' => ['D0', 'D1', 'D7', 'D30'],
                'comment' => '结算方式(D0=笔笔结算,D1=一天结算一次,D7=7天结算一次,D30=30天结算一次)', ])
            ->addColumn('settleAccRatio', 'decimal', ['precision' => 10, 'scale' => 2, 'signed' => false, 'comment' => '结算比例百分比'])
            ->addColumn('status', 'enum', ['values' => ['Normal', 'Exception', 'Close'],
                'comment' => '状态(Normal=正常,Close=关闭,Exception=异常)', ])
            ->addColumn('loginIP', 'string', ['limit' => 50,'default' => '' ,  'comment' => '最后登陆IP'])
            ->addColumn('loginDate', 'datetime',['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('loginIpWhite', 'string', ['limit' => 512, 'default' => '' , 'comment' => '登陆IP白名，若为空不限制'])
            ->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP'])
            ->addIndex('id')
            ->addIndex('loginName',['unique' => true])
            ->create();
    }
}
