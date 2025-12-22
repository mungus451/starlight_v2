<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Upgrade Unit Count Columns to BIGINT (Optional Phase 2)
 * 
 * Purpose: Prevent integer overflow in unit counts for extreme late-game scenarios
 * 
 * Risk Level: MEDIUM
 * - No data loss (BIGINT is larger than INT)
 * - Values can be negative in resources table (use signed BIGINT)
 * - Application logic may need updates for display formatting
 * 
 * Affected Tables:
 * - user_resources: soldiers, guards, spies, sentries, workers, untrained_citizens
 * - spy_reports: soldiers_seen, guards_seen, spies_seen, sentries_seen, workers_seen, defender_total_sentries
 * - battle_reports: soldiers_sent, attacker_soldiers_lost, defender_guards_lost, defender_total_guards
 * 
 * Decision Criteria:
 * - Run this migration if game balance allows players to reach >1B units
 * - Skip if game caps keep units under 500M (safe INT margin)
 * 
 * Rollback: Reversible but will truncate values exceeding INT limits
 */
final class UpgradeUnitCountsToBigint extends AbstractMigration
{
    /**
     * Pre-flight validation:
     * 
     * -- Check current max unit counts
     * SELECT MAX(soldiers) as max_soldiers,
     *        MAX(guards) as max_guards,
     *        MAX(spies) as max_spies,
     *        MAX(sentries) as max_sentries
     * FROM user_resources;
     * 
     * -- Check if any negative values (for rollback safety)
     * SELECT MIN(soldiers), MIN(guards), MIN(spies), MIN(sentries)
     * FROM user_resources;
     */

    public function up(): void
    {
        $this->output->writeln('<info>Upgrading user_resources unit count columns to BIGINT...</info>');

        // User Resources: Use signed BIGINT (values can go negative temporarily)
        $userResourcesTable = $this->table('user_resources');
        $userResourcesTable
            ->changeColumn('untrained_citizens', 'biginteger', [
                'signed' => true,
                'default' => 250,
                'null' => false,
                'comment' => 'Citizens not assigned to any role'
            ])
            ->changeColumn('workers', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Workers generating income'
            ])
            ->changeColumn('soldiers', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Offensive military units'
            ])
            ->changeColumn('guards', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Defensive military units'
            ])
            ->changeColumn('spies', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Offensive espionage units'
            ])
            ->changeColumn('sentries', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Defensive espionage units'
            ])
            ->update();

        $this->output->writeln('<info>Upgraded user_resources unit counts</info>');

        // Spy Reports: Use signed BIGINT for consistency (values are snapshots)
        $this->output->writeln('<info>Upgrading spy_reports unit count columns to BIGINT...</info>');

        $spyReportsTable = $this->table('spy_reports');
        $spyReportsTable
            ->changeColumn('spies_sent', 'biginteger', [
                'signed' => true,
                'null' => false,
                'comment' => 'Number of spies sent on mission'
            ])
            ->changeColumn('spies_lost_attacker', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Attacker spies lost in operation'
            ])
            ->changeColumn('sentries_lost_defender', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Defender sentries lost in operation'
            ])
            ->changeColumn('workers_seen', 'biginteger', [
                'signed' => true,
                'null' => true,
                'default' => null,
                'comment' => 'Worker count observed'
            ])
            ->changeColumn('soldiers_seen', 'biginteger', [
                'signed' => true,
                'null' => true,
                'default' => null,
                'comment' => 'Soldier count observed'
            ])
            ->changeColumn('guards_seen', 'biginteger', [
                'signed' => true,
                'null' => true,
                'default' => null,
                'comment' => 'Guard count observed'
            ])
            ->changeColumn('spies_seen', 'biginteger', [
                'signed' => true,
                'null' => true,
                'default' => null,
                'comment' => 'Spy count observed'
            ])
            ->changeColumn('sentries_seen', 'biginteger', [
                'signed' => true,
                'null' => true,
                'default' => null,
                'comment' => 'Sentry count observed'
            ])
            ->update();

        $this->output->writeln('<info>Upgraded spy_reports unit counts</info>');

        // Battle Reports: Use signed BIGINT for unit counts
        $this->output->writeln('<info>Upgrading battle_reports unit count columns to BIGINT...</info>');

        $battleReportsTable = $this->table('battle_reports');
        $battleReportsTable
            ->changeColumn('soldiers_sent', 'biginteger', [
                'signed' => true,
                'null' => false,
                'comment' => 'Number of soldiers sent in attack'
            ])
            ->changeColumn('attacker_soldiers_lost', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Attacker casualties'
            ])
            ->changeColumn('defender_guards_lost', 'biginteger', [
                'signed' => true,
                'default' => 0,
                'null' => false,
                'comment' => 'Defender casualties'
            ])
            ->update();

        // Check if defender_total_guards column exists (added in migration 20251210212500)
        if ($battleReportsTable->hasColumn('defender_total_guards')) {
            $battleReportsTable
                ->changeColumn('defender_total_guards', 'biginteger', [
                    'signed' => true,
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Snapshot of defender total guards at battle time'
                ])
                ->update();
            $this->output->writeln('<info>Upgraded defender_total_guards column</info>');
        }

        $this->output->writeln('<info>Upgraded battle_reports unit counts</info>');

        // Check if defender_total_sentries exists on spy_reports (added in migration 20251210224000)
        if ($spyReportsTable->hasColumn('defender_total_sentries')) {
            $spyReportsTable
                ->changeColumn('defender_total_sentries', 'biginteger', [
                    'signed' => true,
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Snapshot of defender total sentries at start of operation'
                ])
                ->update();
            $this->output->writeln('<info>Upgraded defender_total_sentries column on spy_reports</info>');
        }
        $this->output->writeln('<comment>Migration complete. Unit counts now support values up to ±9,223,372,036,854,775,807</comment>');
    }

    public function down(): void
    {
        $this->output->writeln('<error>WARNING: Rolling back unit counts to INT</error>');
        $this->output->writeln('<error>This will TRUNCATE values exceeding ±2,147,483,647!</error>');

        // Check for overflow in user_resources
        $sql = "SELECT 
                    MAX(soldiers) as max_soldiers,
                    MAX(guards) as max_guards,
                    MAX(spies) as max_spies,
                    MAX(sentries) as max_sentries,
                    MAX(workers) as max_workers,
                    MAX(untrained_citizens) as max_citizens,
                    MIN(soldiers) as min_soldiers,
                    MIN(guards) as min_guards,
                    MIN(spies) as min_spies,
                    MIN(sentries) as min_sentries,
                    MIN(workers) as min_workers,
                    MIN(untrained_citizens) as min_citizens
                FROM user_resources";

        $result = $this->query($sql)->fetch(\PDO::FETCH_ASSOC);

        $maxInt = 2147483647;
        $minInt = -2147483648;

        foreach ($result as $key => $value) {
            if ($value > $maxInt || $value < $minInt) {
                throw new \RuntimeException(
                    "Cannot safely rollback: {$key} value {$value} exceeds INT limits. " .
                        'Manual intervention required.'
                );
            }
        }

        // Safe to rollback - revert all columns
        $userResourcesTable = $this->table('user_resources');
        $userResourcesTable
            ->changeColumn('untrained_citizens', 'integer', ['signed' => true, 'default' => 250, 'null' => false])
            ->changeColumn('workers', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('soldiers', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('guards', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('spies', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('sentries', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->update();

        $spyReportsTable = $this->table('spy_reports');
        $spyReportsTable
            ->changeColumn('spies_sent', 'integer', ['signed' => true, 'null' => false])
            ->changeColumn('spies_lost_attacker', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('sentries_lost_defender', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('workers_seen', 'integer', ['signed' => true, 'null' => true, 'default' => null])
            ->changeColumn('soldiers_seen', 'integer', ['signed' => true, 'null' => true, 'default' => null])
            ->changeColumn('guards_seen', 'integer', ['signed' => true, 'null' => true, 'default' => null])
            ->changeColumn('spies_seen', 'integer', ['signed' => true, 'null' => true, 'default' => null])
            ->changeColumn('sentries_seen', 'integer', ['signed' => true, 'null' => true, 'default' => null])
            ->update();

        $battleReportsTable = $this->table('battle_reports');
        $battleReportsTable
            ->changeColumn('soldiers_sent', 'integer', ['signed' => true, 'null' => false])
            ->changeColumn('attacker_soldiers_lost', 'integer', ['signed' => true, 'default' => 0, 'null' => false])
            ->changeColumn('defender_guards_lost', 'integer', ['signed' => true, 'default' => 0, 'null' => false]);

        if ($battleReportsTable->hasColumn('defender_total_guards')) {
            $battleReportsTable->changeColumn('defender_total_guards', 'integer', [
                'signed' => true,
                'default' => 0,
                'null' => false
            ]);
        }

        $battleReportsTable->update();

        if ($spyReportsTable->hasColumn('defender_total_sentries')) {
            $spyReportsTable->changeColumn('defender_total_sentries', 'integer', [
                'signed' => true,
                'default' => 0,
                'null' => false
            ])
            ->update();
        }

        $this->output->writeln('<info>Rollback complete</info>');
    }
}
