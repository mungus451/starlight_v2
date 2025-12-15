<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUserEdictsTable extends AbstractMigration
{
    public function up(): void
    {
        // 1. Cleanup old tables from previous attempt
        if ($this->hasTable('user_edicts')) {
            $this->table('user_edicts')->drop()->save();
        }
        if ($this->hasTable('edict_definitions')) {
            $this->table('edict_definitions')->drop()->save();
        }

        // 2. Create correct User Edicts table
        // Tracks which edicts are currently ACTIVE for a user by key.
        if (!$this->hasTable('user_edicts')) {
            $table = $this->table('user_edicts');
            $table->addColumn('user_id', 'integer', ['signed' => false])
                  ->addColumn('edict_key', 'string', ['limit' => 50])
                  ->addTimestamps()
                  ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  // Ensure a user cannot active the same edict twice
                  ->addIndex(['user_id', 'edict_key'], ['unique' => true])
                  ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('user_edicts')) {
            $this->table('user_edicts')->drop()->save();
        }
    }
}
