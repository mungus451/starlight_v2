<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWars extends AbstractMigration
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
        // Create `wars` table if it does not already exist
        if (!$this->hasTable('wars')) {
            $table = $this->table('wars', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('name', 'string', ['limit' => 255, 'null' => false])
                ->addColumn('war_type', 'enum', ['values' => ['skirmish', 'war'], 'default' => 'war', 'null' => false])
                ->addColumn('declarer_alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('declared_against_alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('casus_belli', 'text', ['null' => true, 'default' => null])
                ->addColumn('status', 'enum', ['values' => ['active', 'concluded'], 'default' => 'active', 'null' => false])
                ->addColumn('goal_key', 'string', ['limit' => 100, 'null' => false, 'comment' => "e.g., 'credits_plundered', 'units_killed'"])
                ->addColumn('goal_threshold', 'biginteger', ['signed' => false, 'null' => false])
                ->addColumn('declarer_score', 'biginteger', ['signed' => false, 'default' => 0, 'null' => false])
                ->addColumn('defender_score', 'biginteger', ['signed' => false, 'default' => 0, 'null' => false])
                ->addColumn('winner_alliance_id', 'integer', ['signed' => false, 'null' => true, 'default' => null])
                ->addColumn('start_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false])
                ->addColumn('end_time', 'timestamp', ['null' => true, 'default' => null])
                ->addIndex(['status', 'start_time'], ['name' => 'idx_active_wars'])
                ->addIndex(['declarer_alliance_id', 'status'], ['name' => 'idx_declarer_wars'])
                ->addIndex(['declared_against_alliance_id', 'status'], ['name' => 'idx_defender_wars'])
                ->addForeignKey('declarer_alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_war_declarer'
                ])
                ->addForeignKey('declared_against_alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_war_defender'
                ])
                ->addForeignKey('winner_alliance_id', 'alliances', 'id', [
                    'delete' => 'SET_NULL',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_war_winner'
                ])
                ->create();
        }
    }
}
