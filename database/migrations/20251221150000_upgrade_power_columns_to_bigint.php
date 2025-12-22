<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Migration: Upgrade Power Columns to BIGINT UNSIGNED
 * 
 * Purpose: Prevent integer overflow in combat power calculations
 * 
 * Risk Level: LOW
 * - No data loss (BIGINT is larger than INT)
 * - Values are never negative (UNSIGNED is safe)
 * - No application logic changes needed
 * 
 * Affected Columns:
 * - battle_reports.attacker_offense_power: INT → BIGINT UNSIGNED
 * - battle_reports.defender_defense_power: INT → BIGINT UNSIGNED
 * - battle_reports.defender_shield_hp: INT UNSIGNED → BIGINT UNSIGNED
 * - battle_reports.shield_damage_dealt: INT UNSIGNED → BIGINT UNSIGNED
 * 
 * Rollback: Reversible via down() method (converts back to INT)
 * WARNING: Rolling back after values exceed INT max will cause data truncation!
 */
final class UpgradePowerColumnsToBigint extends AbstractMigration
{
    /**
     * Pre-flight validation queries to run before migration:
     * 
     * -- Check for negative values (should be none)
     * SELECT MIN(attacker_offense_power) as min_atk_power,
     *        MIN(defender_defense_power) as min_def_power,
     *        MIN(defender_shield_hp) as min_shield,
     *        MIN(shield_damage_dealt) as min_damage
     * FROM battle_reports;
     * 
     * -- Check current max values (should be well under 2.1B)
     * SELECT MAX(attacker_offense_power) as max_atk_power,
     *        MAX(defender_defense_power) as max_def_power,
     *        MAX(defender_shield_hp) as max_shield,
     *        MAX(shield_damage_dealt) as max_damage
     * FROM battle_reports;
     */

    public function up(): void
    {
        $this->output->writeln('<info>Upgrading battle_reports power columns to BIGINT UNSIGNED...</info>');

        // Get the table
        $table = $this->table('battle_reports');

        // Change offense/defense power columns
        // These were originally created as signed INT, upgrade to BIGINT UNSIGNED
        $table
            ->changeColumn('attacker_offense_power', 'biginteger', [
                'signed' => false,
                'null' => false,
                'comment' => 'Attacker total offense power at battle time'
            ])
            ->changeColumn('defender_defense_power', 'biginteger', [
                'signed' => false,
                'null' => false,
                'comment' => 'Defender total defense power at battle time'
            ])
            ->update();

        $this->output->writeln('<info>Upgraded offense/defense power columns</info>');

        // Change shield columns (these were INT UNSIGNED, upgrade to BIGINT UNSIGNED)
        $table
            ->changeColumn('defender_shield_hp', 'biginteger', [
                'signed' => false,
                'default' => 0,
                'null' => false,
                'comment' => 'Defender shield HP at battle time'
            ])
            ->changeColumn('shield_damage_dealt', 'biginteger', [
                'signed' => false,
                'default' => 0,
                'null' => false,
                'comment' => 'Shield damage dealt during battle'
            ])
            ->update();

        $this->output->writeln('<info>Upgraded shield columns</info>');
        $this->output->writeln('<comment>Migration complete. Power columns now support values up to 18,446,744,073,709,551,615</comment>');
    }

    public function down(): void
    {
        $this->output->writeln('<error>WARNING: Rolling back power columns to INT</error>');
        $this->output->writeln('<error>This will TRUNCATE values exceeding 2,147,483,647!</error>');

        // Check if any values would be truncated
        $sql = "SELECT 
                    MAX(attacker_offense_power) as max_atk,
                    MAX(defender_defense_power) as max_def,
                    MAX(defender_shield_hp) as max_shield,
                    MAX(shield_damage_dealt) as max_damage
                FROM battle_reports";

        $result = $this->query($sql)->fetch(\PDO::FETCH_ASSOC);

        $willTruncate = false;
        $maxInt = 2147483647;
        $maxUnsignedInt = 4294967295;

        if ($result['max_atk'] > $maxInt || $result['max_def'] > $maxInt) {
            $this->output->writeln('<error>DANGER: Power values exceed INT max. Data will be truncated!</error>');
            $willTruncate = true;
        }

        if ($result['max_shield'] > $maxUnsignedInt || $result['max_damage'] > $maxUnsignedInt) {
            $this->output->writeln('<error>DANGER: Shield values exceed INT UNSIGNED max. Data will be truncated!</error>');
            $willTruncate = true;
        }

        if ($willTruncate) {
            throw new \RuntimeException(
                'Cannot safely rollback: values exceed INT limits. ' .
                    'Manual intervention required to preserve data.'
            );
        }

        // Safe to rollback
        $table = $this->table('battle_reports');

        $table
            ->changeColumn('attacker_offense_power', 'integer', [
                'signed' => true,
                'null' => false
            ])
            ->changeColumn('defender_defense_power', 'integer', [
                'signed' => true,
                'null' => false
            ])
            ->changeColumn('defender_shield_hp', 'integer', [
                'signed' => false,
                'default' => 0,
                'null' => false
            ])
            ->changeColumn('shield_damage_dealt', 'integer', [
                'signed' => false,
                'default' => 0,
                'null' => false
            ])
            ->update();

        $this->output->writeln('<info>Rollback complete</info>');
    }
}
