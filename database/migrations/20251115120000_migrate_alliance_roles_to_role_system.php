<?php

use Phinx\Migration\AbstractMigration;

/**
 * Alliance Roles Migration
 * 
 * Migrates existing alliances to use the new alliance_roles system.
 * Creates default roles (Leader, Member, Recruit) for each alliance
 * and assigns the leader to the Leader role.
 * 
 * This is a data migration that should be run after the alliance_roles
 * table structure exists.
 * 
 * Original: migrations/13.1_migrate_roles.php
 */
class MigrateAllianceRolesToRoleSystem extends AbstractMigration
{
    /**
     * Up Migration - Create default roles and assign leaders
     */
    public function up(): void
    {
        $this->output->writeln('<info>Starting alliance role migration...</info>');
        
        $pdo = $this->getAdapter()->getConnection();
        
        // Get all existing alliances
        $stmt = $pdo->query("SELECT id, leader_id FROM alliances");
        $alliances = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($alliances)) {
            $this->output->writeln('<comment>No existing alliances found. No migration needed.</comment>');
            return;
        }
        
        $migratedCount = 0;
        
        // Loop through each alliance and create its roles
        foreach ($alliances as $alliance) {
            $allianceId = $alliance['id'];
            $leaderId = $alliance['leader_id'];
            
            $this->output->writeln("  • Migrating Alliance ID: {$allianceId}");
            
            // Check if roles already exist for this alliance
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM alliance_roles WHERE alliance_id = ?");
            $stmt->execute([$allianceId]);
            $existingRoles = $stmt->fetchColumn();
            
            if ($existingRoles > 0) {
                $this->output->writeln("    ⊘ Roles already exist, skipping");
                continue;
            }
            
            // Create the 3 default roles for this alliance
            
            // a. Create 'Leader' role (all permissions)
            $stmt = $pdo->prepare("
                INSERT INTO alliance_roles (
                    alliance_id, name, sort_order, can_edit_profile, 
                    can_manage_applications, can_invite_members, can_kick_members, 
                    can_manage_roles, can_see_private_board, can_manage_forum, 
                    can_manage_bank, can_manage_structures
                ) 
                VALUES (?, 'Leader', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
            ");
            $stmt->execute([$allianceId]);
            $leaderRoleId = $pdo->lastInsertId();
            
            // b. Create 'Member' role (no permissions)
            $stmt = $pdo->prepare("
                INSERT INTO alliance_roles (alliance_id, name, sort_order) 
                VALUES (?, 'Member', 9)
            ");
            $stmt->execute([$allianceId]);
            
            // c. Create 'Recruit' role (only invite permission)
            $stmt = $pdo->prepare("
                INSERT INTO alliance_roles (alliance_id, name, sort_order, can_invite_members) 
                VALUES (?, 'Recruit', 10, 1)
            ");
            $stmt->execute([$allianceId]);
            
            // Assign the original leader to the new 'Leader' role
            $stmt = $pdo->prepare("UPDATE users SET alliance_role_id = ? WHERE id = ?");
            $stmt->execute([$leaderRoleId, $leaderId]);
            
            $migratedCount++;
        }
        
        $this->output->writeln("<info>✓ Migration complete! Migrated {$migratedCount} alliances.</info>");
    }
    
    /**
     * Down Migration - Remove created roles
     * 
     * WARNING: This will delete all alliance roles created by this migration.
     * User role assignments will be set to NULL.
     */
    public function down(): void
    {
        $this->output->writeln('<comment>Rolling back alliance role migration...</comment>');
        
        $pdo = $this->getAdapter()->getConnection();
        
        // Get all alliances
        $stmt = $pdo->query("SELECT id FROM alliances");
        $alliances = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($alliances)) {
            $this->output->writeln('<comment>No alliances found.</comment>');
            return;
        }
        
        $removedCount = 0;
        
        foreach ($alliances as $allianceId) {
            // Clear user role assignments for this alliance
            $stmt = $pdo->prepare("
                UPDATE users 
                SET alliance_role_id = NULL 
                WHERE alliance_id = ?
            ");
            $stmt->execute([$allianceId]);
            
            // Delete the default roles
            $stmt = $pdo->prepare("
                DELETE FROM alliance_roles 
                WHERE alliance_id = ? 
                  AND name IN ('Leader', 'Member', 'Recruit')
            ");
            $stmt->execute([$allianceId]);
            $removed = $stmt->rowCount();
            
            if ($removed > 0) {
                $removedCount++;
                $this->output->writeln("  • Removed {$removed} roles for Alliance ID: {$allianceId}");
            }
        }
        
        $this->output->writeln("<info>✓ Rollback complete! Removed roles from {$removedCount} alliances.</info>");
    }
}
