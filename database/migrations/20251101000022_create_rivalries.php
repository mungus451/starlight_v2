<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRivalries extends AbstractMigration
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
        // Create `rivalries` table if it does not already exist
        if (!$this->hasTable('rivalries')) {
            $table = $this->table('rivalries', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance_a_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'The alliance with the lower ID'])
                ->addColumn('alliance_b_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'The alliance with the higher ID'])
                ->addColumn('heat_level', 'integer', ['default' => 1, 'null' => false])
                ->addColumn('last_attack_date', 'timestamp', ['null' => true, 'default' => null])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['alliance_a_id', 'alliance_b_id'], ['unique' => true, 'name' => 'uk_alliance_pair'])
                ->addForeignKey('alliance_a_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rivalry_alliance_a'
                ])
                ->addForeignKey('alliance_b_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_rivalry_alliance_b'
                ])
                ->create();
        }
    }
}
