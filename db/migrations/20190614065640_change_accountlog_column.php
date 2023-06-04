<?php

use Phinx\Migration\AbstractMigration;

class ChangeAccountlogColumn extends AbstractMigration
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

        $log = $this->table('system_account_action_log');
        $log->changeColumn("action", "enum", ['values' => ['UPDATE_PASSWORD', 'UPDATE_LOGINNAME',
            'UPDATE_CHANNEL_MERCHANT', 'CREATE_CHANNEL_MERCHANT', 'IMPORT_CHANNEL_MERCHANT_RATE', 'CREATE_MERCHANT',
            'UPDATE_MERCHANT', 'CREATE_MERCHANT_ACCOUNT', 'UPDATE_MERCHANT_ACCOUNT', 'UPDATE_MERCHANT_ACCOUNT_PASSWORD',
            'UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD', 'UPDATE_MERCHANT_SIGNKEY', "IMPORT_MERCHANT_RATE", "IMPORT_MERCHANT_CHANNEL_PAY",
            "IMPORT_MERCHANT_CHANNEL_SETTLEMENT", 'CREATE_BALANCE_ADJUSTMENT', 'UPDATE_BALANCE_ADJUSTMENT',
            'MANUAL_PLATFORMPAYORDER', 'MANUAL_PLATFORMSETTLEMENTORDER', 'BIND_GOOGLE_AUTH', 'IMPORT_CHANNEL_PAY_CONFIG', 'IMPORT_CHANNEL_SETTLEMENT_CONFIG',
        ], 'comment' => '行为'])
            ->save();
    }

    public function down()
    {
        $log = $this->table('system_account_action_log');
        $log->changeColumn("action", "enum", ['values' => ['UPDATE_PASSWORD', 'UPDATE_LOGINNAME',
            'UPDATE_CHANNEL_MERCHANT', 'CREATE_CHANNEL_MERCHANT', 'IMPORT_CHANNEL_MERCHANT_RATE', 'CREATE_MERCHANT',
            'UPDATE_MERCHANT', 'CREATE_MERCHANT_ACCOUNT', 'UPDATE_MERCHANT_ACCOUNT', 'UPDATE_MERCHANT_ACCOUNT_PASSWORD',
            'UPDATE_MERCHANT_ACCOUNT_PAY_PASSWORD', 'UPDATE_MERCHANT_SIGNKEY', "IMPORT_MERCHANT_RATE", "IMPORT_MERCHANT_CHANNEL_PAY",
            "IMPORT_MERCHANT_CHANNEL_SETTLEMENT", 'CREATE_BALANCE_ADJUSTMENT', 'UPDATE_BALANCE_ADJUSTMENT',
            'MANUAL_PLATFORMPAYORDER', 'MANUAL_PLATFORMSETTLEMENTORDER', 'BIND_GOOGLE_AUTH',
        ], 'comment' => '行为'])
            ->save();
    }
}
