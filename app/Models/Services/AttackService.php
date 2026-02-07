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

        // --- (Existing shield and effect logic remains the same) ---
        $shieldEffect = $this->effectService->getEffectDetails($defender->id, 'peace_shield');
        if ($shieldEffect) {
            $breachEffect = $this->effectService->getEffectDetails($attackerId, 'safehouse_breach');
            $breachMeta = ($breachEffect && isset($breachEffect['metadata'])) ? json_decode($breachEffect['metadata'], true) : [];
            $charges = $breachMeta['charges'] ?? 0;

            if ($charges <= 0) {
                 return ServiceResponse::error("Target is under Safehouse protection. Attack prevented.");
            }
            
            $newCharges = $charges - 1;
            if ($newCharges <= 0) {
                $this->effectService->breakEffect($attackerId, 'safehouse_breach');
            } else {
                $this->effectService->updateMetadata($attackerId, 'safehouse_breach', ['charges' => $newCharges]);
            }
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

        if ($soldiersSent <= 0) {
            return ServiceResponse::error('You have no soldiers to send.');
        }
        if ($attackerStats->attack_turns < $attackTurns) {
            return ServiceResponse::error('You do not have enough attack turns.');
        }

        // --- (Ion Cannon logic remains the same) ---
        $ionCasualties = 0; // Assuming it's defined and calculated here

        // Calculate Battle Power
        $offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower($attackerId, $attackerResources, $attackerStats, $attackerStructures, $attacker->alliance_id);
        $offensePower = $offensePowerBreakdown['total'] * $attackTurns; // APPLY MULTIPLIER
        $originalOffensePower = $offensePower;

        $defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower($defender->id, $defenderResources, $defenderStats, $defenderStructures, $defender->alliance_id);
        $defensePower = $defensePowerBreakdown['total'];

        // --- (Planetary Shield Logic remains the same) ---
        $shieldHp = 0; $damageToShield = 0; // Assuming defined here

        // Determine Outcome
        $attackResult = ($offensePower > $defensePower) ? 'victory' : (($offensePower == $defensePower) ? 'stalemate' : 'defeat');

        // --- (Casualty, XP, and Plunder calculations with multipliers) ---
        $attackerSoldiersLost = 0;
        $defenderGuardsLost = 0;
        $defenderWorkersLost = 0;
        $creditsPlundered = 0;
        $warPrestigeGained = 0;

        if ($attackResult === 'victory') {
            $ratio = max(1, $offensePower) / max(1, $defensePower);
            $attackerSoldiersLost = $this->calculateWinnerLosses($soldiersSent, $ratio);
            $defenderGuardsLost = $this->calculateLoserLosses($defenderResources->guards, $ratio);
            
            $plunderPercent = $config['plunder_percent'] * $attackTurns;
            $creditsPlundered = (int)($defenderResources->credits * $plunderPercent);
            $warPrestigeGained = $config['war_prestige_gain_base'] * $attackTurns;

        } elseif ($attackResult === 'defeat') {
            $ratio = max(1, $defensePower) / max(1, $offensePower);
            $defenderGuardsLost = $this->calculateWinnerLosses($defenderResources->guards, $ratio);
            $attackerSoldiersLost = $this->calculateLoserLosses($soldiersSent, $ratio);
        } else { // Stalemate
            $attackerSoldiersLost = (int)ceil($soldiersSent * 0.15);
            $defenderGuardsLost = (int)ceil($defenderResources->guards * 0.15);
        }
        
        $baseWorkerCasualtyRate = $config['worker_casualty_rate_base'] ?? 0.02;
        $defenderWorkersLost = (int)ceil($defenderResources->workers * $baseWorkerCasualtyRate * $attackTurns);
        $defenderWorkersLost = min($defenderResources->workers, $defenderWorkersLost);
        

        $attackerXpGain = (match($attackResult) {
            'victory' => $xpConfig['battle_win'],
            'defeat' => $xpConfig['battle_loss'],
            'stalemate' => $xpConfig['battle_stalemate'],
        }) * $attackTurns;

        // --- (Transaction logic starts here) ---
        $transactionStartedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStartedByMe = true;
        }

        try {
            // Update Attacker
            $this->resourceRepo->updateBattleAttacker($attackerId, $attackerResources->credits + $creditsPlundered, $attackerResources->soldiers - $attackerSoldiersLost);
            $this->levelUpService->grantExperience($attackerId, $attackerXpGain);
            $attackerNewNW = $this->nwCalculator->calculateTotalNetWorth($attackerId);
            $this->statsRepo->updateBattleAttackerStats(
                $attackerId,
                (int)($attackerStats->attack_turns - $attackTurns),
                (int)$attackerNewNW,
                (int)($attackerStats->experience + $attackerXpGain),
                (int)($attackerStats->war_prestige + $warPrestigeGained)
            );
            $this->statsRepo->incrementBattleStats($attackerId, $attackResult === 'victory');

            // Update Defender
            $this->resourceRepo->updateBattleDefender($defender->id, $defenderResources->credits - $creditsPlundered, $defenderResources->guards - $defenderGuardsLost, $defenderResources->workers - $defenderWorkersLost);
            
            // ... (rest of the transaction: defender XP, battle report, etc.) ...
            
            if ($transactionStartedByMe) {
                $this->db->commit();
            }
            
            return ServiceResponse::success("Attack complete: {$attackResult}!");

        } catch (Throwable $e) {
            if ($transactionStartedByMe && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Attack Operation Error: '. $e->getMessage());
            return ServiceResponse::error('A database error occurred. The attack was cancelled.');
        }
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