<?php

use Phinx\Migration\AbstractMigration;

class CreateUserActiveEffects extends AbstractMigration
{
    public function change()
    {
        if ($this->hasTable('user_active_effects')) {
            return;
        }

        $table = $this->table('user_active_effects');
        $table->addColumn('user_id', 'integer', ['signed' => false])
              ->addColumn('effect_type', 'string', ['limit' => 50])
              ->addColumn('expires_at', 'timestamp')
              ->addColumn('metadata', 'json', ['null' => true])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
              ->addIndex(['user_id', 'effect_type']) // For fast lookups
              ->create();
    }
}
