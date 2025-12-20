<?php

use Phinx\Migration\AbstractMigration;

/**
 * Add race field to users table
 * 
 * This migration adds a nullable race field to allow existing players
 * to select their race upon next login if they don't have one.
 */
class AddRaceToUsers extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');
        $table->addColumn('race', 'string', [
            'limit' => 50,
            'null' => true,
            'after' => 'character_name',
            'comment' => 'Player race selection (human, cyborg, android, alien)'
        ])
        ->update();
    }
}
