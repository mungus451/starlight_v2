<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService; // Import new service
use PDO;
use Throwable;

/**
 * Handles all business logic for PvP Attacks.
 */
class AttackService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private BattleRepository $battleRepo;
    private ArmoryService $armoryService;
    private PowerCalculatorService $powerCalculatorService; // Add new service property

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        
        $this->userRepo = new UserRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->structureRepo = new StructureRepository($this->db);
        $this->statsRepo = new StatsRepository($this->db);
        $this->battleRepo = new BattleRepository($this->db);
        
        // Instantiate services
        $this->armoryService = new ArmoryService();
        $this->powerCalculatorService = new PowerCalculatorService(); // Instantiate new service
    }

    /**
     * Gets all data needed for the main Battle page.
     */
    public function getAttackPageData(int $userId, int $page): array
    {
        // 1. Get Attacker's info (unchanged)
        $attackerResources = $this->resourceRepo->findByUserId($userId);
        $attackerStats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.attack', []);

        // 2. Get Pagination config
        $perPage = $this->config->get('app.leaderboard.per_page', 25);
        
        $totalTargets = $this->statsRepo->getTotalTargetCount($userId);
        $totalPages = (int)ceil($totalTargets / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $perPage;

        // 3. Get Player Target List
        $targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $userId);

        return [
            'attackerResources' => $attackerResources,
            'attackerStats' => $attackerStats,
            'costs' => $costs,
            'targets' => $targets,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages
            ],
            'perPage' => $perPage
        ];
    }

    /**
     * Gets a unified list of battle reports (offensive and defensive) for the user.
     */
    public function getBattleReports(int $userId): array
    {
        // 1. Get both sets of reports
        $offensiveReports = $this->battleRepo->findReportsByAttackerId($userId);
        $defensiveReports = $this->battleRepo->findReportsByDefenderId($userId);
        
        // 2. Merge them into a single array
        $allReports = array_merge($offensiveReports, $defensiveReports);
        
        // 3. Sort the combined array by date, descending
        usort($allReports, function($a, $b) {
            return $b->created_at <=> $a->created_at;
        });
        
        return $allReports;
    }

    /**
     * Gets a single, specific battle report, ensuring the viewer was involved.
     */
    public function getBattleReport(int $reportId, int $viewerId): ?\App\Models\Entities\BattleReport
    {
        return $this->battleRepo->findReportById($reportId, $viewerId);
    }

    /**
     * Conducts an "all-in" PvP Attack.
     *
     * @param int $attackerId
     * @param string $targetName
     * @param string $attackType
     * @return bool True on success
     */
    public function conductAttack(int $attackerId, string $targetName, string $attackType): bool
    {
        // --- 1. Validation (Input) ---
        if (empty(trim($targetName))) {
            $this->session->setFlash('error', 'You must enter a target.');
            return false;
        }
        if ($attackType !== 'plunder') {
            $this->session->setFlash('error', 'Invalid attack type.');
            return false;
        }

        $defender = $this->userRepo->findByCharacterName($targetName);

        if (!$defender) {
            $this->session->setFlash('error', "Character '{$targetName}' not found.");
            return false;
        }
        if ($defender->id === $attackerId) {
            $this->session->setFlash('error', 'You cannot attack yourself.');
            return false;
        }

        // --- 2. Get All 6 Data Objects + Config ---
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        $attackerStructures = $this->structureRepo->findByUserId($attackerId);
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStats = $this->statsRepo->findByUserId($defender->id);
        $defenderStructures = $this->structureRepo->findByUserId($defender->id);
        $config = $this->config->get('game_balance.attack');

        // --- 3. Check Costs & Availability (ALL-IN LOGIC) ---
        $soldiersSent = $attackerResources->soldiers; // ALL-IN
        $turnCost = $config['attack_turn_cost'];

        if ($soldiersSent <= 0) {
            $this->session->setFlash('error', 'You have no soldiers to send.');
            return false;
        }
        if ($attackerStats->attack_turns < $turnCost) {
            $this->session->setFlash('error', 'You do not have enough attack turns.');
            return false;
        }

        // --- 4. Calculate Battle Power (REFACTORED) ---

        // 4a. Attacker Offense Power
        $offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower(
            $attackerId,
            $attackerResources,
            $attackerStats,
            $attackerStructures
        );
        $offensePower = $offensePowerBreakdown['total'];
        
        // 4b. Defender Defense Power
        $defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower(
            $defender->id,
            $defenderResources,
            $defenderStats,
            $defenderStructures
        );
        $defensePower = $defensePowerBreakdown['total'];

        // --- 5. Determine Outcome ---
        $attackResult = 'defeat'; // Default
        if ($offensePower > $defensePower) {
            $attackResult = 'victory';
        } elseif ($offensePower == $defensePower) {
            $attackResult = 'stalemate';
        }

        // --- 6. Calculate Losses (FIX: Cast floats to int for mt_rand) ---
        $attackerSoldiersLost = 0;
        $defenderGuardsLost = 0;
        if ($attackResult === 'victory') {
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['winner_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['loser_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
        } elseif ($attackResult === 'defeat') {
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['loser_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['winner_loss_percent_max']));
        } else { // Stalemate
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
        }
        $attackerSoldiersLost = min($soldiersSent, $attackerSoldiersLost);
        $defenderGuardsLost = min($defenderResources->guards, $defenderGuardsLost);

        // --- 7. Calculate Gains (if 'victory') ---
        $creditsPlundered = 0;
        $netWorthStolen = 0;
        $experienceGained = 0;
        $warPrestigeGained = 0;

        if ($attackResult === 'victory') {
            $creditsPlundered = (int)($defenderResources->credits * $config['plunder_percent']);
            $netWorthStolen = (int)($defenderStats->net_worth * $config['net_worth_steal_percent']);
            $experienceGained = $config['experience_gain_base'];
            $warPrestigeGained = $config['war_prestige_gain_base'];
        }

        // --- 8. Execute 4-Table Transaction ---
        $this->db->beginTransaction();
        try {
            // 8a. Update Attacker Resources
            $attackerNewCredits = $attackerResources->credits + $creditsPlundered;
            $attackerNewSoldiers = $attackerResources->soldiers - $attackerSoldiersLost;
            $this->resourceRepo->updateBattleAttacker($attackerId, $attackerNewCredits, $attackerNewSoldiers);

            // 8b. Update Attacker Stats
            $attackerNewAttackTurns = $attackerStats->attack_turns - $turnCost;
            $attackerNewNetWorth = $attackerStats->net_worth + $netWorthStolen;
            $attackerNewExperience = $attackerStats->experience + $experienceGained;
            $attackerNewPrestige = $attackerStats->war_prestige + $warPrestigeGained;
            $this->statsRepo->updateBattleAttackerStats($attackerId, $attackerNewAttackTurns, $attackerNewNetWorth, $attackerNewExperience, $attackerNewPrestige);

            // 8c. Update Defender Resources
            $defenderNewCredits = max(0, $defenderResources->credits - $creditsPlundered);
            $defenderNewGuards = max(0, $defenderResources->guards - $defenderGuardsLost);
            // --- FIX ---
            $this->resourceRepo->updateBattleDefender($defender->id, $defenderNewCredits, $defenderNewGuards);

            // 8d. Update Defender Stats
            $defenderNewNetWorth = max(0, $defenderStats->net_worth - $netWorthStolen);
            $this->statsRepo->updateBattleDefenderStats($defender->id, $defenderNewNetWorth);

            // 8e. Create Battle Report
            $this->battleRepo->createReport(
                $attackerId, $defender->id, $attackType, $attackResult, $soldiersSent,
                $attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
                $experienceGained, $warPrestigeGained, $netWorthStolen,
                (int)$offensePower, (int)$defensePower
            );

            // 8f. Commit
            $this->db->commit();
            
        } catch (Throwable $e) {
            // 8g. Rollback on failure
            // --- THIS IS THE FIX ---
            $this->db->rollBack();
            error_log('Attack Operation Error: '. $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. The attack was cancelled.');
            return false;
        }

        // --- 9. Set Flash Message ---
        $message = "Attack Complete: {$attackResult}!";
        if ($attackResult === 'victory') {
            $message .= " You plundered " . number_format($creditsPlundered) . " credits and lost " . number_format($attackerSoldiersLost) . " soldiers.";
        } else {
            $message .= " You lost " . number_format($attackerSoldiersLost) . " soldiers.";
        }
        $this->session->setFlash('success', $message);
        return true;
    }
}