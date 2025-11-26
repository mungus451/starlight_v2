<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateBattleReports extends AbstractMigration
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
        // Create `battle_reports` table if it does not already exist
        if (!$this->hasTable('battle_reports')) {
            $table = $this->table('battle_reports', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('attacker_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('defender_id', 'integer', ['signed' => false, 'null' => false])
                ->addTimestamps(updatedAt: false)
                ->addColumn('attack_type', 'enum', ['values' => ['plunder', 'conquer'], 'null' => false])
                ->addColumn('attack_result', 'enum', ['values' => ['victory', 'defeat', 'stalemate'], 'null' => false])
                // Forces
                ->addColumn('soldiers_sent', 'integer', ['null' => false])
                ->addColumn('attacker_soldiers_lost', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('defender_guards_lost', 'integer', ['default' => 0, 'null' => false])
                // Spoils
                ->addColumn('credits_plundered', 'biginteger', ['default' => 0, 'null' => false])
                ->addColumn('experience_gained', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('war_prestige_gained', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('net_worth_stolen', 'biginteger', ['default' => 0, 'null' => false])
                // Battle Snapshot
                ->addColumn('attacker_offense_power', 'integer', ['null' => false])
                ->addColumn('defender_defense_power', 'integer', ['null' => false])
                ->addIndex(['attacker_id', 'created_at'], ['name' => 'idx_attacker_created'])
                ->addIndex(['defender_id', 'created_at'], ['name' => 'idx_defender_created'])
                ->addForeignKey('attacker_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_battle_attacker'
                ])
                ->addForeignKey('defender_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_battle_defender'
                ])
                ->create();
        }
    }
}
