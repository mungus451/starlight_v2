<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDetailedStatsTracking extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('user_stats');

        // 1. Add new tracking columns
        if (!$table->hasColumn('battles_won')) {
            $table->addColumn('battles_won', 'integer', ['default' => 0, 'null' => false, 'after' => 'last_deposit_at'])
                  ->addColumn('battles_lost', 'integer', ['default' => 0, 'null' => false, 'after' => 'battles_won'])
                  ->addColumn('spy_successes', 'integer', ['default' => 0, 'null' => false, 'after' => 'battles_lost'])
                  ->addColumn('spy_failures', 'integer', ['default' => 0, 'null' => false, 'after' => 'spy_successes'])
                  ->update();
        }

        // 2. Backfill Data from existing Reports (Pure SQL)
        
        // Backfill Battles Won (As Attacker)
        $this->execute("
            UPDATE user_stats s
            SET battles_won = (
                SELECT COUNT(*) FROM battle_reports b 
                WHERE b.attacker_id = s.user_id AND b.attack_result = 'victory'
            )
        ");

        // Backfill Battles Lost (As Attacker)
        $this->execute("
            UPDATE user_stats s
            SET battles_lost = (
                SELECT COUNT(*) FROM battle_reports b 
                WHERE b.attacker_id = s.user_id AND b.attack_result = 'defeat'
            )
        ");

        // Backfill Spy Successes (As Attacker)
        $this->execute("
            UPDATE user_stats s
            SET spy_successes = (
                SELECT COUNT(*) FROM spy_reports r 
                WHERE r.attacker_id = s.user_id AND r.operation_result = 'success'
            )
        ");

        // Backfill Spy Failures (As Attacker)
        $this->execute("
            UPDATE user_stats s
            SET spy_failures = (
                SELECT COUNT(*) FROM spy_reports r 
                WHERE r.attacker_id = s.user_id AND r.operation_result = 'failure'
            )
        ");
    }

    public function down(): void
    {
        $table = $this->table('user_stats');
        $table->removeColumn('battles_won')
              ->removeColumn('battles_lost')
              ->removeColumn('spy_successes')
              ->removeColumn('spy_failures')
              ->update();
    }
}