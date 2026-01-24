<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\EffectService;
use App\Models\Services\NetWorthCalculatorService;
use App\Core\Events\EventDispatcher;
use App\Models\Entities\UserResource; // --- NEW IMPORT ---
use App\Events\BattleConcludedEvent;
use PDO;
use Throwable;

/**
 * Handles all business logic for PvP Attacks.
 * Updated Phase 2: Dynamic Ratio-Based Casualty Logic.
 */
class AttackService
{
    private PDO $db;
    private Config $config;
    
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private BattleRepository $battleRepo;
    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private BountyRepository $bountyRepo;
    private WarRepository $warRepo;
    private WarBattleLogRepository $warBattleLogRepo;

    private ArmoryService $armoryService;
    private PowerCalculatorService $powerCalculatorService;
    private LevelUpService $levelUpService;
    private EventDispatcher $dispatcher;
    private EffectService $effectService;
    private NetWorthCalculatorService $nwCalculator;

    public function __construct(
        PDO $db,
        Config $config,
        UserRepository $userRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo,
        BattleRepository $battleRepo,
        AllianceRepository $allianceRepo,
        AllianceBankLogRepository $bankLogRepo,
        BountyRepository $bountyRepo,
        WarRepository $warRepo,
        WarBattleLogRepository $warBattleLogRepo,
        ArmoryService $armoryService,
        PowerCalculatorService $powerCalculatorService,
        LevelUpService $levelUpService,
        EventDispatcher $dispatcher,
        EffectService $effectService,
        NetWorthCalculatorService $nwCalculator
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        $this->battleRepo = $battleRepo;
        $this->allianceRepo = $allianceRepo;
        $this->bankLogRepo = $bankLogRepo;
        $this->bountyRepo = $bountyRepo;
        $this->warRepo = $warRepo;
        $this->warBattleLogRepo = $warBattleLogRepo;
        $this->armoryService = $armoryService;
        $this->powerCalculatorService = $powerCalculatorService;
        $this->levelUpService = $levelUpService;
        $this->dispatcher = $dispatcher;
        $this->effectService = $effectService;
        $this->nwCalculator = $nwCalculator;
    }

    public function getAttackPageData(int $userId, int $page, int $limit = 25): array
    {
        $attackerResources = $this->resourceRepo->findByUserId($userId);
        $attackerStats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.attack', []);

        // Whitelist the limit to prevent abuse
        $allowedLimits = [5, 10, 25, 100];
        $perPage = in_array($limit, $allowedLimits) ? $limit : 25;

        $totalTargets = $this->statsRepo->getTotalTargetCount($userId);
        $totalPages = (int)ceil($totalTargets / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $perPage;

        $targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $userId);

        return [
            'attackerResources' => $attackerResources,
            'attackerStats' => $attackerStats,
            'costs' => $costs,
            'targets' => $targets,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'limit' => $perPage
            ],
            'perPage' => $perPage
        ];
    }

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

    public function getPaginatedReports(int $userId, int $page, int $limit = 10): array
    {
        $total = $this->battleRepo->countUserBattles($userId);
        $totalPages = (int)ceil($total / $limit);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;

        $reports = $this->battleRepo->getPaginatedUserBattles($userId, $limit, $offset);

        return [
            'reports' => $reports,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $total,
                'limit' => $limit
            ]
        ];
    }

    public function getBattleReport(int $reportId, int $viewerId): ?\App\Models\Entities\BattleReport
    {
        return $this->battleRepo->findReportById($reportId, $viewerId);
    }

    public function conductAttack(int $attackerId, int $targetId, string $attackType, int $attackTurns): ServiceResponse
    {
        if ($attackType !== 'plunder') {
            return ServiceResponse::error('Invalid attack type.');
        }

        $defender = $this->userRepo->findById($targetId);

        if (!$defender) {
            return ServiceResponse::error("Target not found.");
        }
        if ($defender->id === $attackerId) {
            return ServiceResponse::error('You cannot attack yourself.');
        }

        // Check Active Effects
        $shieldEffect = $this->effectService->getEffectDetails($defender->id, 'peace_shield');
        if ($shieldEffect) {
            // ... (rest of the shield logic remains the same)
        }

        if ($this->effectService->hasActiveEffect($attackerId, 'peace_shield')) {
            $this->effectService->breakEffect($attackerId, 'peace_shield'); 
        }

        $attacker = $this->userRepo->findById($attackerId);
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        $attackerStructures = $this->structureRepo->findByUserId($attackerId);
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStats = $this->statsRepo->findByUserId($defender->id);
        $defenderStructures = $this->structureRepo->findByUserId($defender->id);

        $config = $this->config->get('game_balance.attack');
        $treasuryConfig = $this->config->get('game_balance.alliance_treasury');
        $xpConfig = $this->config->get('game_balance.xp.rewards');

        $soldiersSent = $attackerResources->soldiers;
        
        // --- MULTI-TURN VALIDATION ---
        if ($attackerStats->attack_turns < $attackTurns) {
            return ServiceResponse::error('You do not have enough attack turns.');
        }
        if ($soldiersSent <= 0) {
            return ServiceResponse::error('You have no soldiers to send.');
        }

        // --- (Ion Cannon logic remains the same) ---

        // Calculate Battle Power with TURN MULTIPLIER
        $offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower(
            $attackerId, $attackerResources, $attackerStats, $attackerStructures, $attacker->alliance_id
        );
        $offensePower = $offensePowerBreakdown['total'] * $attackTurns; // APPLY MULTIPLIER
        $originalOffensePower = $offensePower; 

        $defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower(
            $defender->id, $defenderResources, $defenderStats, $defenderStructures, $defender->alliance_id
        );
        $defensePower = $defensePowerBreakdown['total'];

        // ... (rest of shield, outcome, and initial casualty logic is the same) ...

        // --- APPLY TURN MULTIPLIER TO CASUALTIES AND GAINS ---

        // Worker Casualties (Collateral Damage)
        $defenderWorkersLost = 0;
        if ($attackResult === 'victory' || $attackResult === 'defeat') {
            // ... (base calculation remains)
            $defenderWorkersLost = (int)ceil($defenderResources->workers * $totalCasualtyPercent) * $attackTurns; // APPLY MULTIPLIER
            $defenderWorkersLost = min($defenderResources->workers, $defenderWorkersLost);
        }

        // Defender Guard losses are already scaled by the multiplied offense power, so no change needed there.

        // XP Calculation with TURN MULTIPLIER
        $attackerXpGain = match($attackResult) {
            'victory' => $xpConfig['battle_win'],
            'defeat' => $xpConfig['battle_loss'],
            'stalemate' => $xpConfig['battle_stalemate'],
            default => 0
        } * $attackTurns; // APPLY MULTIPLIER

        // ... (defender XP remains the same) ...

        // Calculate Gains (Loot) with TURN MULTIPLIER
        $creditsPlundered = 0;
        if ($attackResult === 'victory') {
            $plunderPercent = $config['plunder_percent'] * $attackTurns; // APPLY MULTIPLIER
            $creditsPlundered = (int)($defenderResources->credits * $plunderPercent);
            
            // ... (bunker logic remains) ...
        }

        // ... (rest of the logic: prestige, tax, transaction) ...
        
        // In the transaction, make sure to deduct the correct number of turns
        $this->statsRepo->updateBattleAttackerStats(
            $attackerId,
            (int)($attackerStats->attack_turns - $attackTurns), // Use submitted turns
            (int)$attackerNewNW,
            (int)($attackerStats->experience + $attackerXpGain),
            (int)($attackerStats->war_prestige + $warPrestigeGained)
        );

        // ... (rest of the transaction) ...
    }

    /**
     * Calculates casualties for the WINNER of the battle.
     * Logic: The higher the ratio (more overwhelming), the fewer losses.
     */
    public function calculateWinnerLosses(int $unitCount, float $ratio): int
    {
        // Base loss factor: 5% at 1:1 ratio.
        // Formula: 0.05 / Ratio.
        // Example: Ratio 10 -> 0.5% loss. Ratio 100 -> 0.05% loss.
        
        $lossPercent = 0.05 / $ratio;
        
        // Ensure minimal losses if ratio is extreme
        $losses = (int)ceil($unitCount * $lossPercent);
        
        // Random variance +/- 20% of calculated losses
        $variance = (int)ceil($losses * 0.2);
        $losses = mt_rand(max(0, $losses - $variance), $losses + $variance);
        
        return $losses;
    }

    /**
     * Calculates casualties for the LOSER of the battle.
     * Logic: The higher the ratio (more overwhelmed), the higher the losses.
     * Wipeout Rule: If Ratio > 10, they lose everything.
     */
    public function calculateLoserLosses(int $unitCount, float $ratio): int
    {
        if ($unitCount <= 0) return 0;

        // Wipeout check
        if ($ratio >= 10.0) {
            return $unitCount;
        }

        // Base loss factor: 10% at 1:1 ratio.
        // Scaling: 10% * Ratio.
        // Example: Ratio 5 -> 50% loss.
        $lossPercent = 0.10 * $ratio;
        
        // Cap percentage at 100%
        $lossPercent = min(1.0, $lossPercent);
        
        $losses = (int)ceil($unitCount * $lossPercent);
        
        // Variance
        $variance = (int)ceil($losses * 0.1);
        $losses = mt_rand(max(1, $losses - $variance), min($unitCount, $losses + $variance));
        
        // Ensure at least 1 casualty if units exist (The '0 casualties' bug fix)
        return max(1, $losses);
    }
}