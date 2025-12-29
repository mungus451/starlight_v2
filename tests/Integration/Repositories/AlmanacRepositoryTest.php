<?php

namespace Tests\Integration\Repositories;

use App\Core\Database;
use App\Models\Repositories\AlmanacRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class AlmanacRepositoryTest extends TestCase
{
    private PDO $db;
    private AlmanacRepository $repo;
    private array $userIds = [];
    private array $allianceIds = [];

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->repo = new AlmanacRepository($this->db);
    }

    protected function tearDown(): void
    {
        // Cleanup Foreign Keys cascade usually, but being explicit helps avoid orphans if schema changes
        if (!empty($this->userIds)) {
            $ids = implode(',', $this->userIds);
            // Wars delete cascade from alliances usually, but check schema. 
            // Users delete cascade battle_reports and spy_reports.
            // Alliance leader deletion might be restricted (RESTRICT).
            // So we delete alliances first, then users.
            
            if (!empty($this->allianceIds)) {
                $aIds = implode(',', $this->allianceIds);
                $this->db->exec("DELETE FROM wars WHERE declarer_alliance_id IN ($aIds) OR declared_against_alliance_id IN ($aIds)");
                $this->db->exec("DELETE FROM alliances WHERE id IN ($aIds)");
            }

            $this->db->exec("DELETE FROM battle_reports WHERE attacker_id IN ($ids) OR defender_id IN ($ids)");
            $this->db->exec("DELETE FROM spy_reports WHERE attacker_id IN ($ids) OR defender_id IN ($ids)");
            $this->db->exec("DELETE FROM users WHERE id IN ($ids)");
        }
    }

    private function createUser(string $name): int
    {
        $email = strtolower($name) . uniqid() . '@test.com';
        $stmt = $this->db->prepare("INSERT INTO users (email, password_hash, character_name) VALUES (?, 'hash', ?)");
        $stmt->execute([$email, $name]);
        $id = (int)$this->db->lastInsertId();
        $this->userIds[] = $id;
        return $id;
    }

    private function createAlliance(string $name, int $leaderId): int
    {
        $tag = substr($name, 0, 3) . rand(10, 99);
        $stmt = $this->db->prepare("
            INSERT INTO alliances (name, tag, leader_id, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $tag, $leaderId]);
        $id = (int)$this->db->lastInsertId();
        $this->allianceIds[] = $id;
        return $id;
    }

    private function createBattle(int $atkId, int $defId, string $result, int $atkLost, int $defLost, int $plunder)
    {
        $sql = "
            INSERT INTO battle_reports 
            (attacker_id, defender_id, attack_type, attack_result, soldiers_sent, 
             attacker_soldiers_lost, defender_guards_lost, credits_plundered, 
             experience_gained, war_prestige_gained, net_worth_stolen, 
             attacker_offense_power, defender_defense_power, defender_total_guards, created_at)
            VALUES (?, ?, 'plunder', ?, 1000, ?, ?, ?, 0, 0, 0, 1000, 1000, 500, NOW())
        ";
        $this->db->prepare($sql)->execute([
            $atkId, $defId, $result, $atkLost, $defLost, $plunder
        ]);
    }

    private function createSpy(int $atkId, int $defId, string $result)
    {
        $sql = "
            INSERT INTO spy_reports 
            (attacker_id, defender_id, operation_result, spies_sent, spies_lost_attacker, sentries_lost_defender, created_at)
            VALUES (?, ?, ?, 10, 0, 0, NOW())
        ";
        $this->db->prepare($sql)->execute([
            $atkId, $defId, $result
        ]);
    }

    private function createWar(int $declarerId, int $defenderId, string $status, ?int $winnerId = null, int $dScore = 0, int $defScore = 0)
    {
        $sql = "
            INSERT INTO wars 
            (name, declarer_alliance_id, declared_against_alliance_id, status, 
             winner_alliance_id, declarer_score, defender_score, goal_key, goal_threshold)
            VALUES ('Test War', ?, ?, ?, ?, ?, ?, 'credits_plundered', 100000)
        ";
        $this->db->prepare($sql)->execute([
            $declarerId, $defenderId, $status, $winnerId, $dScore, $defScore
        ]);
    }

    public function testGetPlayerDossier()
    {
        $p1 = $this->createUser('PlayerOne');
        $p2 = $this->createUser('PlayerTwo');

        // P1 Attacks P2: 2 wins (P1), 1 loss (P1)
        $this->createBattle($p1, $p2, 'victory', 100, 200, 5000); // P1 wins, plunder 5000, 200 guards killed
        $this->createBattle($p1, $p2, 'victory', 50, 150, 3000);  // P1 wins, plunder 3000, 150 guards killed
        $this->createBattle($p1, $p2, 'defeat', 300, 50, 0);     // P1 loses, 300 soldiers lost

        // P2 Attacks P1: 1 win (P2), meaning P1 lost (defending)
        // P2 attacks P1, P2 wins. P1 loses 120 guards (defending casualties)
        // P1 kills 80 soldiers (defending kills)
        $this->createBattle($p2, $p1, 'victory', 80, 120, 2000); 

        // P1 Stats Expected:
        // Wins: 2 (Attacking)
        // Losses: 1 (Attacking) + 1 (Defending) = 2
        // Total Battles: 4
        
        // Kills: 
        //  - Attacking: 200 + 150 = 350 guards
        //  - Defending: 80 soldiers
        //  - Total Kills: 430
        
        // Casualties (Units Lost):
        //  - Attacking: 100 + 50 + 300 = 450 soldiers
        //  - Defending: 120 guards
        //  - Total Lost: 570
        
        // Largest Plunder: 5000 (from first battle)
        // Deadliest Attack: 200 kills (from first battle)

        $dossier = $this->repo->getPlayerDossier($p1);

        $this->assertEquals(2, $dossier['battles_won']);
        $this->assertEquals(2, $dossier['battles_lost']);
        $this->assertEquals(4, $dossier['total_battles']);
        
        $this->assertEquals(480, $dossier['units_killed']);
        $this->assertEquals(570, $dossier['units_lost']);
        
        $this->assertEquals(5000, $dossier['largest_plunder']);
        $this->assertEquals(200, $dossier['deadliest_attack']);
    }

    public function testGetAllianceDossier()
    {
        $p1 = $this->createUser('AllyLeaderOne');
        $p2 = $this->createUser('AllyLeaderTwo');
        $p3 = $this->createUser('AllyMemberOne');

        $a1 = $this->createAlliance('Alpha Team', $p1);
        $a2 = $this->createAlliance('Beta Squad', $p2);

        // Assign P1 (Leader) to A1
        $this->db->prepare("UPDATE users SET alliance_id = ? WHERE id = ?")->execute([$a1, $p1]);
        
        // Add P3 to A1
        $this->db->prepare("UPDATE users SET alliance_id = ? WHERE id = ?")->execute([$a1, $p3]);

        // Battle 1: P1 (A1 Leader) attacks P2 (A2 Leader) - P1 Wins
        $this->createBattle($p1, $p2, 'victory', 0, 0, 5000);

        // Battle 2: P3 (A1 Member) attacks P2 (A2 Leader) - P3 Wins
        $this->createBattle($p3, $p2, 'victory', 0, 0, 2000);

        // Battle 3: P2 (A2 Leader) attacks P3 (A1 Member) - P2 Wins (P3 Loses)
        $this->createBattle($p2, $p3, 'victory', 0, 0, 1000);

        // War 1: A1 vs A2 (Active)
        $this->createWar($a1, $a2, 'active', null, 0, 0);

        // A1 Stats Expected:
        // Members: P1, P3
        // Total Wins: 2 (P1 attack win + P3 attack win)
        // Total Losses: 1 (P3 defense loss)
        // Total Plundered: 5000 (P1) + 2000 (P3) = 7000
        // Wars Participated: 1

        $dossier = $this->repo->getAllianceDossier($a1);

        $this->assertEquals(2, $dossier['member_count']); // Leader + 1 Member
        $this->assertEquals(2, $dossier['total_wins']);
        $this->assertEquals(1, $dossier['total_losses']);
        $this->assertEquals(7000, $dossier['total_plundered']);
        $this->assertEquals(1, $dossier['wars_participated']);
    }
}
