<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEdictTables extends AbstractMigration
{
    public function up(): void
    {
        // 1. Edict Definitions Table
        if (!$this->hasTable('edict_definitions')) {
            $table = $this->table('edict_definitions');
            $table->addColumn('name', 'string', ['limit' => 100])
                  ->addColumn('description', 'text')
                  ->addColumn('lore', 'text', ['null' => true])
                  ->addColumn('type', 'enum', ['values' => ['economic', 'military', 'espionage', 'special']])
                  ->addColumn('modifiers', 'json', ['comment' => 'JSON payload of effects'])
                  ->addColumn('upkeep_cost', 'integer', ['default' => 0, 'comment' => 'Cost per turn'])
                  ->addColumn('upkeep_resource', 'string', ['limit' => 50, 'default' => 'credits', 'comment' => 'Resource type for upkeep'])
                  ->addTimestamps()
                  ->addIndex(['type'])
                  ->create();
        }

        // 2. User Edicts Table
        if (!$this->hasTable('user_edicts')) {
            $table = $this->table('user_edicts');
            $table->addColumn('user_id', 'integer', ['signed' => false])
                  ->addColumn('edict_id', 'integer', ['signed' => true]) 
                  ->addTimestamps()
                  ->addForeignKey('user_id', 'users', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->addForeignKey('edict_id', 'edict_definitions', 'id', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                  ->addIndex(['user_id', 'edict_id'], ['unique' => true])
                  ->create();
        }
    }

    public function down(): void
    {
        if ($this->hasTable('user_edicts')) {
            $this->table('user_edicts')->drop()->save();
        }
        if ($this->hasTable('edict_definitions')) {
            $this->table('edict_definitions')->drop()->save();
        }
    }
}
