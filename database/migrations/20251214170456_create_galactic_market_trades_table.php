<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateGalacticMarketTradesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $table = $this->table('galactic_market_trades');
        $table->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('resource_type', 'string', ['limit' => 255])
              ->addColumn('trade_type', 'enum', ['values' => ['buy', 'sell']])
              ->addColumn('amount', 'integer')
              ->addColumn('price', 'integer')
              ->addTimestamps()
              ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addIndex(['user_id'])
              ->addIndex(['resource_type'])
              ->create();
    }

    public function down(): void
    {
        $this->table('galactic_market_trades')->drop()->save();
    }
}
