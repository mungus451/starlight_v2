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

        return [
            'battles_won' => $wins,
            'battles_lost' => $losses,
            'total_battles' => $wins + $losses,
            'units_killed' => $unitsKilled,
            'units_lost' => $unitsLostAttacking + $unitsLostDefending,
            'units_lost_attacking' => $unitsLostAttacking,
            'units_lost_defending' => $unitsLostDefending,
            'largest_plunder' => (int)($records['largest_plunder'] ?? 0),
            'deadliest_attack' => (int)($records['deadliest_attack'] ?? 0)
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
