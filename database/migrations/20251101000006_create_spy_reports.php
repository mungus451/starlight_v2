<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSpyReports extends AbstractMigration
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
        // Create `spy_reports` table if it does not already exist
        if (!$this->hasTable('spy_reports')) {
            $table = $this->table('spy_reports', [
                'id' => 'id',
                'signed' => false,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ]);

            $table
                ->addColumn('attacker_id', 'integer', ['signed' => false, 'null' => false])
                ->addColumn('defender_id', 'integer', ['signed' => false, 'null' => false])
                ->addTimestamps(updatedAt: false)
                ->addColumn('operation_result', 'enum', ['values' => ['success', 'failure'], 'null' => false])
                ->addColumn('spies_sent', 'integer', ['null' => false])
                ->addColumn('spies_lost_attacker', 'integer', ['default' => 0, 'null' => false])
                ->addColumn('sentries_lost_defender', 'integer', ['default' => 0, 'null' => false])
                // Intel Payload (all nullable)
                ->addColumn('credits_seen', 'biginteger', ['null' => true, 'default' => null])
                ->addColumn('gemstones_seen', 'biginteger', ['null' => true, 'default' => null])
                ->addColumn('workers_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('soldiers_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('guards_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('spies_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('sentries_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('fortification_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('offense_upgrade_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('defense_upgrade_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('spy_upgrade_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('economy_upgrade_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('population_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addColumn('armory_level_seen', 'integer', ['null' => true, 'default' => null])
                ->addIndex(['attacker_id', 'created_at'], ['name' => 'idx_attacker_created'])
                ->addForeignKey('attacker_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_report_attacker'
                ])
                ->addForeignKey('defender_id', 'users', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION',
                    'constraint' => 'fk_report_defender'
                ])
                ->create();
        }
    }
}
