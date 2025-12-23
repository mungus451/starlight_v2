<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FixGeneralsTable extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('generals');

        // Remove unique constraint on user_id (allow multiple generals)
        if ($table->hasIndex('user_id')) {
            $table->removeIndex('user_id');
        }
        $table->addIndex('user_id', ['unique' => false]); // Non-unique index

        // Change weapon_slot_1 to string (for config keys)
        // Note: phinx changeColumn can be tricky across DBs, but for MySQL/SQLite it works.
        $table->changeColumn('weapon_slot_1', 'string', ['limit' => 50, 'null' => true]);

        $table->update();
    }

    public function down(): void
    {
        $table = $this->table('generals');
        
        // Restore unique index (DATA LOSS RISK if duplicates exist)
        // In dev, we accept this.
        if ($table->hasIndex('user_id')) {
            $table->removeIndex('user_id');
        }
        $table->addIndex('user_id', ['unique' => true]);
        
        // Restore integer column
        // This will fail if string data exists, but 'down' is best effort in dev.
        $table->changeColumn('weapon_slot_1', 'integer', ['null' => true]);
        
        $table->update();
    }
}