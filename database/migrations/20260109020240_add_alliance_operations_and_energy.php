<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddAllianceOperationsAndEnergy extends AbstractMigration
{
    public function change(): void
    {
        // 1. Update `alliances` table with Energy (AE) columns
        $this->table('alliances')
            ->addColumn('alliance_energy', 'integer', ['signed' => false, 'default' => 0, 'after' => 'bank_credits'])
            ->addColumn('energy_cap', 'integer', ['signed' => false, 'default' => 10000, 'after' => 'alliance_energy'])
            ->update();

        // 2. Create `alliance_operations` table
        // Stores active missions/ops (e.g., "Resource Drive", "Combat Headhunt")
        $this->table('alliance_operations')
            ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('type', 'string', ['limit' => 32, 'null' => false]) // e.g., 'resource_drive', 'combat_kills'
            ->addColumn('target_value', 'biginteger', ['signed' => false, 'null' => false]) // e.g., 5000 kills
            ->addColumn('current_value', 'biginteger', ['signed' => false, 'default' => 0])
            ->addColumn('deadline', 'datetime', ['null' => false])
            ->addColumn('status', 'string', ['limit' => 16, 'default' => 'active']) // active, completed, failed
            ->addColumn('reward_buff', 'string', ['limit' => 32, 'null' => true]) // e.g., 'mining_boost', 'attack_boost'
            ->addTimestamps()
            ->addForeignKey('alliance_id', 'alliances', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        // 3. Create `alliance_energy_logs` table
        // Audit trail for AE usage (donations, tactical spending)
        $this->table('alliance_energy_logs')
            ->addColumn('alliance_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => true]) // Nullable for system events
            ->addColumn('type', 'string', ['limit' => 32, 'null' => false]) // donation, tactical_siphon, tactical_sabotage
            ->addColumn('amount', 'integer', ['signed' => true, 'null' => false]) // Positive (add) or Negative (spend)
            ->addColumn('details', 'string', ['limit' => 255, 'null' => true]) // Optional details (e.g., "Targeted Red Star")
            ->addTimestamps(updatedAt: false) // Only created_at
            ->addForeignKey('alliance_id', 'alliances', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->create();
            
        // 4. Create `alliance_op_contributions` table
        // Tracks how much each user has contributed to a specific Op
        $this->table('alliance_op_contributions')
            ->addColumn('operation_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('user_id', 'integer', ['signed' => false, 'null' => false])
            ->addColumn('amount', 'biginteger', ['signed' => false, 'default' => 0])
            ->addTimestamps()
            ->addForeignKey('operation_id', 'alliance_operations', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addIndex(['operation_id', 'user_id'], ['unique' => true]) // One record per user per op
            ->create();
    }
}