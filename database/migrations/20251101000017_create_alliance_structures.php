<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateAllianceStructures extends AbstractMigration
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
        // Create `alliance_structures` table if it does not already exist
        if (!$this->hasTable('alliance_structures')) {
            $table = $this->table('alliance_structures', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('structure_key', 'string', ['limit' => 50, 'null' => false])
                ->addColumn('level', 'integer', ['signed' => false, 'default' => 1, 'null' => false])
                ->addTimestamps()
                ->addIndex(['alliance_id', 'structure_key'], ['unique' => true, 'name' => 'uk_alliance_structure'])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_as_alliance'
                ])
                ->addForeignKey('structure_key', 'alliance_structures_definitions', 'structure_key', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_as_structure_def'
                ])
                ->create();
        }
    }
}
