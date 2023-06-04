<?php

use Phinx\Migration\AbstractMigration;

class TableChannelMerchantChangeColumnParamMigration extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('channel_merchant');
        $table->changeColumn('param', 'string', ['limit' => 6000, 'comment' => '商户参数', 'default' => ''])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('channel_merchant');
        $table->changeColumn('param', 'string', ['limit' => 1500, 'comment' => '商户参数', 'default' => ''])
            ->save();
    }
}
