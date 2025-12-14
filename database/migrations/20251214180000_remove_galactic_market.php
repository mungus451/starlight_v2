<?php

use Phinx\Migration\AbstractMigration;

class RemoveGalacticMarket extends AbstractMigration
{
    public function up()
    {
        // Drop the galactic_market_trades table if it exists
        if ($this->hasTable('galactic_market_trades')) {
            $this->table('galactic_market_trades')->drop()->save();
        }

        // Remove the galactic_market_level column from user_structures
        $table = $this->table('user_structures');
        if ($table->hasColumn('galactic_market_level')) {
            $table->removeColumn('galactic_market_level')
                  ->save();
        }
    }

    public function down()
    {
        // Recreate the galactic_market_trades table
        $table = $this->table('galactic_market_trades');
        $table->addColumn('seller_id', 'integer')
              ->addColumn('resource_type', 'string', ['limit' => 50])
              ->addColumn('amount', 'integer')
              ->addColumn('price', 'integer')
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->create();

        // Re-add the galactic_market_level column to user_structures
        $this->table('user_structures')
             ->addColumn('galactic_market_level', 'integer', ['default' => 0, 'null' => false])
             ->save();
    }
}
