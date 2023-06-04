<?php

use Phinx\Migration\AbstractMigration;

class TableFinanceChangeColumnSummary extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('finance');
        $table->changeColumn('summary', 'string', ['limit' => 500, 'comment' => '描述', 'default' => ''])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('finance');
        $table->changeColumn('summary', 'string', ['limit' => 50, 'comment' => '描述', 'default' => ''])
            ->save();
    }
}
