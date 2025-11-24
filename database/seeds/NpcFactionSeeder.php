<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class NpcFactionSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Creates The Void Syndicate NPC alliance with 4 NPC members
     */
    public function run(): void
    {
        $adapter = $this->getAdapter();

        // Define NPCs
        $npcs = [
            'Leonardo' => ['email' => 'leo@npc.void', 'role' => 'Leader'],
            'Michelangelo' => ['email' => 'mikey@npc.void', 'role' => 'Member'],
            'Donatello' => ['email' => 'donnie@npc.void', 'role' => 'Member'],
            'Raphael' => ['email' => 'raph@npc.void', 'role' => 'Member'],
        ];

        $npcIds = [];

        // Create NPC users
        foreach ($npcs as $name => $data) {
            // Check if NPC exists
            $stmt = $adapter->query("SELECT id FROM users WHERE email = '{$data['email']}'");
            $existing = $stmt->fetch();

            if ($existing) {
                $npcIds[$name] = $existing['id'];
                continue;
            }

            // Create NPC user
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $this->table('users')->insert([
                'email' => $data['email'],
                'character_name' => $name,
                'password_hash' => $hash,
                'is_npc' => 1
            ])->saveData();
            $userId = $adapter->getConnection()->lastInsertId();
            $npcIds[$name] = $userId;

            // Initialize resources
            $this->table('user_resources')->insert([
                'user_id' => $userId,
                'credits' => 50000000,
                'untrained_citizens' => 1000,
                'banked_credits' => 100000000
            ])->saveData();

            // Initialize stats
            $this->table('user_stats')->insert([
                'user_id' => $userId,
                'level' => 10,
                'net_worth' => 100000,
                'attack_turns' => 50
            ])->saveData();

            // Initialize structures
            $this->table('user_structures')->insert([
                'user_id' => $userId,
                'economy_upgrade_level' => 5,
                'fortification_level' => 5,
                'armory_level' => 1
            ])->saveData();
        }

        // Create The Void Syndicate alliance
        $allianceName = 'The Void Syndicate';
        $stmt = $adapter->query("SELECT id FROM alliances WHERE name = '{$allianceName}'");
        $alliance = $stmt->fetch();

        if (!$alliance) {
            $this->table('alliances')->insert([
                'name' => $allianceName,
                'tag' => 'VOID',
                'leader_id' => $npcIds['Leonardo'],
                'description' => 'Shadows in the starlight. The masters of the void.',
                'is_joinable' => 0,
                'bank_credits' => 500000000
            ])->saveData();
            $allianceId = $adapter->getConnection()->lastInsertId();

            // Create alliance roles
            $this->table('alliance_roles')->insert([
                'alliance_id' => $allianceId,
                'name' => 'Leader',
                'sort_order' => 1,
                'can_edit_profile' => 1,
                'can_manage_applications' => 1,
                'can_invite_members' => 1,
                'can_kick_members' => 1,
                'can_manage_roles' => 1,
                'can_see_private_board' => 1,
                'can_manage_forum' => 1,
                'can_manage_bank' => 1,
                'can_manage_structures' => 1,
                'can_manage_diplomacy' => 1,
                'can_declare_war' => 1
            ])->saveData();
            $leaderRoleId = $adapter->getConnection()->lastInsertId();

            $this->table('alliance_roles')->insert([
                'alliance_id' => $allianceId,
                'name' => 'Member',
                'sort_order' => 9
            ])->saveData();
            $memberRoleId = $adapter->getConnection()->lastInsertId();

            // Assign NPCs to alliance
            foreach ($npcs as $name => $data) {
                $roleId = ($data['role'] === 'Leader') ? $leaderRoleId : $memberRoleId;
                $adapter->execute("UPDATE users SET alliance_id = {$allianceId}, alliance_role_id = {$roleId} WHERE id = {$npcIds[$name]}");
            }
        }
    }
}
