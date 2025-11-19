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
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Services\WarService;
use App\Models\Repositories\WarRepository;
use App\Models\Services\LevelUpService; // --- NEW IMPORT ---
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
    private PowerCalculatorService $powerCalculatorService;
    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private WarService $warService;
    private WarRepository $warRepo;
    private LevelUpService $levelUpService; // --- NEW PROPERTY ---

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
        
        $this->armoryService = new ArmoryService();
        $this->powerCalculatorService = new PowerCalculatorService();
        
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->bankLogRepo = new AllianceBankLogRepository($this->db);
        
        $this->warService = new WarService();
        $this->warRepo = new WarRepository($this->db);
        
        // --- NEW: Instantiate LevelUpService ---
        $this->levelUpService = new LevelUpService();
    }

    /**
     * Gets all data needed for the main Battle page.
     */
    public function getAttackPageData(int $userId, int $page): array
    {
        // 1. Get Attacker's info
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
        $offensiveReports = $this->battleRepo->findReportsByAttackerId($userId);
        $defensiveReports = $this->battleRepo->findReportsByDefenderId($userId);
        
        $allReports = array_merge($offensiveReports, $defensiveReports);
        
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

        // --- 2. Get All Data ---
        $attacker = $this->userRepo->findById($attackerId);
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        $attackerStructures = $this->structureRepo->findByUserId($attackerId);
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStats = $this->statsRepo->findByUserId($defender->id);
        $defenderStructures = $this->structureRepo->findByUserId($defender->id);
        
        $config = $this->config->get('game_balance.attack');
        $treasuryConfig = $this->config->get('game_balance.alliance_treasury');
        $xpConfig = $this->config->get('game_balance.xp.rewards'); // --- NEW ---

        // --- 3. Check Costs & Availability ---
        $soldiersSent = $attackerResources->soldiers;
        $turnCost = $config['attack_turn_cost'];

        if ($soldiersSent <= 0) {
            $this->session->setFlash('error', 'You have no soldiers to send.');
            return false;
        }
        if ($attackerStats->attack_turns < $turnCost) {
            $this->session->setFlash('error', 'You do not have enough attack turns.');
            return false;
        }

        // --- 4. Calculate Battle Power ---
        $offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower(
            $attackerId, $attackerResources, $attackerStats, $attackerStructures
        );
        $offensePower = $offensePowerBreakdown['total'];
        
        $defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower(
            $defender->id, $defenderResources, $defenderStats, $defenderStructures
        );
        $defensePower = $defensePowerBreakdown['total'];

        // --- 5. Determine Outcome ---
        $attackResult = 'defeat';
        if ($offensePower > $defensePower) {
            $attackResult = 'victory';
        } elseif ($offensePower == $defensePower) {
            $attackResult = 'stalemate';
        }

        // --- 6. Calculate Losses & XP ---
        $attackerSoldiersLost = 0;
        $defenderGuardsLost = 0;
        $attackerXpGain = 0;
        $defenderXpGain = 0;

        if ($attackResult === 'victory') {
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['winner_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['loser_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
            
            $attackerXpGain = $xpConfig['battle_win'] ?? 250;
            $defenderXpGain = $xpConfig['battle_defense_loss'] ?? 25;
            
        } elseif ($attackResult === 'defeat') {
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['loser_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['winner_loss_percent_max']));
            
            $attackerXpGain = $xpConfig['battle_loss'] ?? 50;
            $defenderXpGain = $xpConfig['battle_defense_win'] ?? 150;

        } else { // Stalemate
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
            
            $attackerXpGain = $xpConfig['battle_stalemate'] ?? 100;
            $defenderXpGain = $xpConfig['battle_defense_win'] ?? 100; // Treat stalemate as defense success? Or add stalemate config.
        }
        
        $attackerSoldiersLost = min($soldiersSent, $attackerSoldiersLost);
        $defenderGuardsLost = min($defenderResources->guards, $defenderGuardsLost);

        // --- 7. Calculate Gains (if 'victory') ---
        $creditsPlundered = 0;
        $netWorthStolen = 0;
        $warPrestigeGained = 0;
        $battleTaxAmount = 0;
        $tributeTaxAmount = 0;
        $totalTaxAmount = 0;

        if ($attackResult === 'victory') {
            $creditsPlundered = (int)($defenderResources->credits * $config['plunder_percent']);
            $netWorthStolen = (int)($defenderStats->net_worth * $config['net_worth_steal_percent']);
            $warPrestigeGained = $config['war_prestige_gain_base'];
            
            if ($attacker->alliance_id !== null && $creditsPlundered > 0) {
                $battleTaxAmount = (int)floor($creditsPlundered * $treasuryConfig['battle_tax_rate']);
                $tributeTaxAmount = (int)floor($creditsPlundered * $treasuryConfig['tribute_tax_rate']);
                $totalTaxAmount = $battleTaxAmount + $tributeTaxAmount;
            }
        }
        
        $attackerCreditGain = $creditsPlundered - $totalTaxAmount;

        // --- 8. Execute Transaction ---
        $this->db->beginTransaction();
        try {
            // 8a. Update Attacker Resources
            $attackerNewCredits = $attackerResources->credits + $attackerCreditGain;
            $attackerNewSoldiers = $attackerResources->soldiers - $attackerSoldiersLost;
            $this->resourceRepo->updateBattleAttacker($attackerId, $attackerNewCredits, $attackerNewSoldiers);

            // 8b. Update Attacker Stats (Legacy + new XP)
            // Note: grantExperience will handle level ups, but we update experience here too for redundancy/consistency
            // in the statsRepo call.
            $this->levelUpService->grantExperience($attackerId, $attackerXpGain);
            
            $attackerNewAttackTurns = $attackerStats->attack_turns - $turnCost;
            $attackerNewNetWorth = $attackerStats->net_worth + $netWorthStolen;
            $attackerNewPrestige = $attackerStats->war_prestige + $warPrestigeGained;
            $attackerNewExperience = $attackerStats->experience + $attackerXpGain;
            
            $this->statsRepo->updateBattleAttackerStats($attackerId, $attackerNewAttackTurns, $attackerNewNetWorth, $attackerNewExperience, $attackerNewPrestige);

            // 8c. Update Defender Resources
            $defenderNewCredits = max(0, $defenderResources->credits - $creditsPlundered);
            $defenderNewGuards = max(0, $defenderResources->guards - $defenderGuardsLost);
            $this->resourceRepo->updateBattleDefender($defender->id, $defenderNewCredits, $defenderNewGuards);

            // 8d. Update Defender Stats & XP
            $this->levelUpService->grantExperience($defender->id, $defenderXpGain);
            
            $defenderNewNetWorth = max(0, $defenderStats->net_worth - $netWorthStolen);
            $this->statsRepo->updateBattleDefenderStats($defender->id, $defenderNewNetWorth);

            // 8e. Create Battle Report
            // We pass 0 for experience_gained in report for now, or update repo to accept it.
            // The BattleRepository::createReport signature has $experienceGained.
            $battleReportId = $this->battleRepo->createReport(
                $attackerId, $defender->id, $attackType, $attackResult, $soldiersSent,
                $attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
                $attackerXpGain, $warPrestigeGained, $netWorthStolen,
                (int)$offensePower, (int)$defensePower
            );
            
            // 8f. Update Alliance Bank & Logs
            if ($totalTaxAmount > 0 && $attacker->alliance_id !== null) {
                $this->allianceRepo->updateBankCreditsRelative($attacker->alliance_id, $totalTaxAmount);
                if ($battleTaxAmount > 0) {
                    $taxMsg = "Battle tax (" . ($treasuryConfig['battle_tax_rate'] * 100) . "%) from victory against " . $defender->characterName;
                    $this->bankLogRepo->createLog($attacker->alliance_id, $attackerId, 'battle_tax', $battleTaxAmount, $taxMsg);
                }
                if ($tributeTaxAmount > 0) {
                    $tribMsg = "Tribute (" . ($treasuryConfig['tribute_tax_rate'] * 100) . "%) from victory against " . $defender->characterName;
                    $this->bankLogRepo->createLog($attacker->alliance_id, $attackerId, 'tribute_tax', $tributeTaxAmount, $tribMsg);
                }
            }
            
            // 8g. Log War Battle
            $this->warService->logBattle(
                $battleReportId,
                $attacker,
                $defender,
                $attackResult,
                $warPrestigeGained,
                $defenderGuardsLost,
                $creditsPlundered
            );
            
            $this->db->commit();
            
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Attack Operation Error: '. $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. The attack was cancelled.');
            return false;
        }

        // --- 9. Set Flash Message ---
        $message = "Attack Complete: {$attackResult}!";
        if ($attackResult === 'victory') {
            $message .= " You plundered " . number_format($creditsPlundered) . " credits.";
        }
        $message .= " XP Gained: +{$attackerXpGain}.";
        
        $this->session->setFlash('success', $message);
        return true;
    }
}