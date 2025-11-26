<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceStructuresDefinitions extends AbstractMigration
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
    public function change(): void
    {
        // Create `alliance_structures_definitions` table if it does not already exist
        if (!$this->hasTable('alliance_structures_definitions')) {
            $table = $this->table('alliance_structures_definitions', [
                'id' => false,
                'primary_key' => ['structure_key'],
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('structure_key', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('name', 'string', ['limit' => 100, 'null' => false])
                ->addColumn('description', 'text', ['null' => false])
                ->addColumn('base_cost', 'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('cost_multiplier', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 1.5, 'null' => false])
                ->addColumn('bonus_text', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('bonuses_json', 'json', ['null' => false])
                ->create();
        }
    }
}
