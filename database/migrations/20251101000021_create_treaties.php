<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTreaties extends AbstractMigration
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
        // Create `treaties` table if it does not already exist
        if (!$this->hasTable('treaties')) {
            $table = $this->table('treaties', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('alliance1_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'Proposing alliance'])
                ->addColumn('alliance2_id', 'integer', ['signed' => false, 'null' => false, 'comment' => 'Target alliance'])
                ->addColumn('treaty_type', 'enum', ['values' => ['peace', 'non_aggression', 'mutual_defense'], 'null' => false])
                ->addColumn('status', 'enum', ['values' => ['proposed', 'active', 'expired', 'broken', 'declined'], 'default' => 'proposed', 'null' => false])
                ->addColumn('terms', 'text', ['null' => true, 'default' => null])
                ->addColumn('expiration_date', 'timestamp', ['null' => true, 'default' => null])
                ->addTimestamps()
                ->addIndex(['alliance1_id', 'status'], ['name' => 'idx_alliance1_treaties'])
                ->addIndex(['alliance2_id', 'status'], ['name' => 'idx_alliance2_treaties'])
                ->addForeignKey('alliance1_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_treaty_alliance1'
                ])
                ->addForeignKey('alliance2_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_treaty_alliance2'
                ])
                ->create();
        }
    }
}
