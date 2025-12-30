<?php

declare(strict_types=1);

namespace App\Models\Repositories;

use PDO;

/**
 * Handles aggregation of historical data for the Almanac (Dossier View).
 */
class AlmanacRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Retrieves the full historical dossier for a single player.
     */
    public function getPlayerDossier(int $userId): array
    {
        // 1. Battle Counts (Wins/Losses)
        // Wins: Attacker Victory + Defender Victory
        $sqlWins = "
            SELECT COUNT(*) FROM battle_reports 
            WHERE (attacker_id = ? AND attack_result = 'victory') 
               OR (defender_id = ? AND attack_result = 'defeat')
        ";
        $stmt = $this->db->prepare($sqlWins);
        $stmt->execute([$userId, $userId]);
        $wins = (int)$stmt->fetchColumn();

        // Losses: Attacker Defeat + Defender Victory (Defender won means Attacker lost)
        // Wait, if I am Attacker and result is 'defeat', I lost.
        // If I am Defender and result is 'victory', I won (Attacker lost).
        // If I am Defender and result is 'defeat', I lost (Attacker won).
        
        $sqlLosses = "
            SELECT COUNT(*) FROM battle_reports 
            WHERE (attacker_id = ? AND attack_result = 'defeat') 
               OR (defender_id = ? AND attack_result = 'victory')
        ";
        $stmt = $this->db->prepare($sqlLosses);
        $stmt->execute([$userId, $userId]);
        $losses = (int)$stmt->fetchColumn();

        // 2. Unit Statistics (Kills vs Deaths)
        // Kills: Guards I killed when Attacking + Soldiers I killed when Defending
        $sqlKills = "
            SELECT 
                (SELECT COALESCE(SUM(defender_guards_lost), 0) FROM battle_reports WHERE attacker_id = ?) +
                (SELECT COALESCE(SUM(attacker_soldiers_lost), 0) FROM battle_reports WHERE defender_id = ?)
        ";
        $stmt = $this->db->prepare($sqlKills);
        $stmt->execute([$userId, $userId]);
        $unitsKilled = (int)$stmt->fetchColumn();

        // Casualties Split
        // Attacking: Soldiers lost
        $sqlLostAttacking = "SELECT COALESCE(SUM(attacker_soldiers_lost), 0) FROM battle_reports WHERE attacker_id = ?";
        $stmt = $this->db->prepare($sqlLostAttacking);
        $stmt->execute([$userId]);
        $unitsLostAttacking = (int)$stmt->fetchColumn();

        // Defending: Guards lost (Citizens killed)
        $sqlLostDefending = "SELECT COALESCE(SUM(defender_guards_lost), 0) FROM battle_reports WHERE defender_id = ?";
        $stmt = $this->db->prepare($sqlLostDefending);
        $stmt->execute([$userId]);
        $unitsLostDefending = (int)$stmt->fetchColumn();

        // 3. Records (Max Plunder, Deadliest Attack)
        $sqlRecords = "
            SELECT 
                MAX(credits_plundered) as largest_plunder,
                MAX(defender_guards_lost) as deadliest_attack
            FROM battle_reports
            WHERE attacker_id = ? AND attack_result = 'victory'
        ";
        $stmt = $this->db->prepare($sqlRecords);
        $stmt->execute([$userId]);
        $records = $stmt->fetch(PDO::FETCH_ASSOC);

        // 4. Espionage Statistics (NEW)
        // Missions I launched
        $sqlSpyMissions = "
            SELECT 
                COUNT(*) as total_missions,
                SUM(CASE WHEN operation_result = 'success' THEN 1 ELSE 0 END) as successful_missions,
                SUM(CASE WHEN operation_result = 'failure' THEN 1 ELSE 0 END) as failed_missions,
                COALESCE(SUM(spies_lost_attacker), 0) as spies_lost_attacking,
                COALESCE(SUM(sentries_lost_defender), 0) as enemy_sentries_killed
            FROM spy_reports
            WHERE attacker_id = ?
        ";
        $stmt = $this->db->prepare($sqlSpyMissions);
        $stmt->execute([$userId]);
        $spyStatsAttacker = $stmt->fetch(PDO::FETCH_ASSOC);

        // Missions against me
        $sqlSpyDefense = "
            SELECT 
                COUNT(*) as total_defenses,
                SUM(CASE WHEN operation_result = 'failure' THEN 1 ELSE 0 END) as intercepted_missions,
                COALESCE(SUM(sentries_lost_defender), 0) as sentries_lost_defending,
                COALESCE(SUM(spies_lost_attacker), 0) as enemy_spies_caught
            FROM spy_reports
            WHERE defender_id = ?
        ";
        $stmt = $this->db->prepare($sqlSpyDefense);
        $stmt->execute([$userId]);
        $spyStatsDefender = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'battles_won' => $wins,
            'battles_lost' => $losses,
            'total_battles' => $wins + $losses,
            'units_killed' => $unitsKilled,
            'units_lost' => $unitsLostAttacking + $unitsLostDefending,
            'units_lost_attacking' => $unitsLostAttacking,
            'units_lost_defending' => $unitsLostDefending,
            'largest_plunder' => (int)($records['largest_plunder'] ?? 0),
            'deadliest_attack' => (int)($records['deadliest_attack'] ?? 0),
            // Espionage
            'spy_missions_total' => (int)($spyStatsAttacker['total_missions'] ?? 0),
            'spy_missions_success' => (int)($spyStatsAttacker['successful_missions'] ?? 0),
            'spy_missions_failed' => (int)($spyStatsAttacker['failed_missions'] ?? 0),
            'spies_lost' => (int)($spyStatsAttacker['spies_lost_attacking'] ?? 0),
            'enemy_sentries_killed' => (int)($spyStatsAttacker['enemy_sentries_killed'] ?? 0),
            'spy_defenses_total' => (int)($spyStatsDefender['total_defenses'] ?? 0),
            'spy_defenses_intercepted' => (int)($spyStatsDefender['intercepted_missions'] ?? 0),
            'sentries_lost' => (int)($spyStatsDefender['sentries_lost_defending'] ?? 0),
            'enemy_spies_caught' => (int)($spyStatsDefender['enemy_spies_caught'] ?? 0)
        ];
    }

    /**
     * Retrieves the historical dossier for an entire alliance.
     * Aggregates data from all CURRENT members.
     */
    public function getAllianceDossier(int $allianceId): array
    {
        // 1. Get all current member IDs
        $stmt = $this->db->prepare("SELECT id FROM users WHERE alliance_id = ?");
        $stmt->execute([$allianceId]);
        $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($memberIds)) {
            return [
                'member_count' => 0,
                'total_wins' => 0,
                'total_losses' => 0,
                'total_plundered' => 0,
                'wars_participated' => 0
            ];
        }

        $idsStr = implode(',', array_map('intval', $memberIds));

        // 2. Aggregate Member Battle Stats
        // Wins (Members Attacking & Winning + Members Defending & Winning)
        $sqlWins = "
            SELECT COUNT(*) FROM battle_reports 
            WHERE (attacker_id IN ($idsStr) AND attack_result = 'victory') 
               OR (defender_id IN ($idsStr) AND attack_result = 'defeat')
        ";
        $wins = (int)$this->db->query($sqlWins)->fetchColumn();

        // Losses (Members Attacking & Losing + Members Defending & Losing)
        $sqlLosses = "
            SELECT COUNT(*) FROM battle_reports 
            WHERE (attacker_id IN ($idsStr) AND attack_result = 'defeat') 
               OR (defender_id IN ($idsStr) AND attack_result = 'victory')
        ";
        $losses = (int)$this->db->query($sqlLosses)->fetchColumn();

        // Total Plundered by Members (Attacking only)
        $sqlPlunder = "
            SELECT SUM(credits_plundered) FROM battle_reports
            WHERE attacker_id IN ($idsStr) AND attack_result = 'victory'
        ";
        $plundered = (int)$this->db->query($sqlPlunder)->fetchColumn();

        // 3. War Participation (Alliance level)
        $sqlWars = "
            SELECT COUNT(*) FROM wars 
            WHERE declarer_alliance_id = ? OR declared_against_alliance_id = ?
        ";
        $stmt = $this->db->prepare($sqlWars);
        $stmt->execute([$allianceId, $allianceId]);
        $wars = (int)$stmt->fetchColumn();

        return [
            'member_count' => count($memberIds),
            'total_wins' => $wins,
            'total_losses' => $losses,
            'total_plundered' => $plundered,
            'wars_participated' => $wars
        ];
    }
}
