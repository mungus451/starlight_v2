<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWarBattleLogs extends AbstractMigration
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
        // Create `war_battle_logs` table if it does not already exist
        if (!$this->hasTable('war_battle_logs')) {
            $table = $this->table('war_battle_logs', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('war_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('battle_report_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('prestige_gained', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('units_killed', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('credits_plundered', 'biginteger', ['default' => 0, 'null' => false])
                ->addColumn('structure_damage', 'integer', ['default' => 0, 'null' => false])
                ->addTimestamps(updatedAt: false)
                ->addIndex(['battle_report_id'], ['unique' => true, 'name' => 'uk_battle_report'])
                ->addIndex(['war_id', 'alliance_id'], ['name' => 'idx_war_logs'])
                ->addForeignKey('war_id', 'wars', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_wbl_war'
                ])
                ->addForeignKey('battle_report_id', 'battle_reports', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_wbl_battle_report'
                ])
                ->addForeignKey('user_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_wbl_user'
                ])
                ->addForeignKey('alliance_id', 'alliances', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_wbl_alliance'
                ])
                ->create();
        }
    }
}
