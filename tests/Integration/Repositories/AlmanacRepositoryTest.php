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

    public function testPvpAggregate()
    {
        $p1 = $this->createUser('PlayerOne');
        $p2 = $this->createUser('PlayerTwo');

        // P1 Attacks P2: 2 wins, 1 loss
        $this->createBattle($p1, $p2, 'victory', 100, 200, 5000); // P1 wins
        $this->createBattle($p1, $p2, 'victory', 50, 150, 3000);  // P1 wins
        $this->createBattle($p1, $p2, 'defeat', 300, 50, 0);     // P1 loses

        // P2 Attacks P1: 1 win
        $this->createBattle($p2, $p1, 'victory', 80, 120, 2000); // P2 wins

        // Spies
        $this->createSpy($p1, $p2, 'success');
        $this->createSpy($p1, $p2, 'failure');
        $this->createSpy($p2, $p1, 'success');

        $stats = $this->repo->getPvpAggregate($p1, $p2);

        $this->assertEquals($p1, $stats->player1Id);
        $this->assertEquals($p2, $stats->player2Id);
        
        // Wins
        $this->assertEquals(2, $stats->battlesWonByP1);
        $this->assertEquals(1, $stats->battlesWonByP2);
        $this->assertEquals(4, $stats->totalBattles);

        // Resources
        $this->assertEquals(8000, $stats->resourcesPlunderedByP1); // 5000 + 3000
        $this->assertEquals(2000, $stats->resourcesPlunderedByP2);

        // Units Killed by P1 (Guards killed when attacking P2 + Soldiers killed when defending against P2)
        // P1 attacking P2: killed 200 + 150 + 50 = 400 guards
        // P1 defending vs P2: killed 80 soldiers (P2's loss)
        // Total P1 Kills = 480
        $this->assertEquals(480, $stats->unitsKilledByP1);

        // Units Killed by P2 (Guards killed when attacking P1 + Soldiers killed when defending against P1)
        // P2 attacking P1: killed 120 guards
        // P2 defending vs P1: killed 100 + 50 + 300 = 450 soldiers
        // Total P2 Kills = 570
        $this->assertEquals(570, $stats->unitsKilledByP2);

        // Spies
        $this->assertEquals(2, $stats->spyAttemptsByP1);
        $this->assertEquals(1, $stats->spySuccessesByP1);
        $this->assertEquals(1, $stats->spyAttemptsByP2);
        $this->assertEquals(1, $stats->spySuccessesByP2);
    }

    public function testAllianceAggregate()
    {
        $p1 = $this->createUser('AllyLeaderOne');
        $p2 = $this->createUser('AllyLeaderTwo');

        $a1 = $this->createAlliance('Alpha Team', $p1);
        $a2 = $this->createAlliance('Beta Squad', $p2);

        // War 1: A1 declares on A2, A1 wins
        $this->createWar($a1, $a2, 'concluded', $a1, 1000, 500);

        // War 2: A2 declares on A1, A2 wins
        $this->createWar($a2, $a1, 'concluded', $a2, 2000, 100);

        // War 3: A1 declares on A2, Active
        $this->createWar($a1, $a2, 'active', null, 50, 50);

        $stats = $this->repo->getAllianceAggregate($a1, $a2);

        $this->assertEquals($a1, $stats->alliance1Id);
        $this->assertEquals($a2, $stats->alliance2Id);

        $this->assertEquals(1, $stats->warsWonByA1);
        $this->assertEquals(1, $stats->warsWonByA2);
        $this->assertEquals(1, $stats->activeConflicts);

        // Damage (Scores)
        // A1 Score: 1000 (War 1 Declarer) + 100 (War 2 Defender) + 50 (War 3 Declarer) = 1150
        // A2 Score: 500 (War 1 Defender) + 2000 (War 2 Declarer) + 50 (War 3 Defender) = 2550
        $this->assertEquals(1150, $stats->totalDamageDealtByA1);
        $this->assertEquals(2550, $stats->totalDamageDealtByA2);
    }
}
