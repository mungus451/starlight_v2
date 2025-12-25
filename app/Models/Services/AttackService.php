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
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\EffectService;
use App\Core\Events\EventDispatcher;
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

    private ArmoryService $armoryService;
    private PowerCalculatorService $powerCalculatorService;
    private LevelUpService $levelUpService;
    private EventDispatcher $dispatcher;
    private EffectService $effectService;

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
        ArmoryService $armoryService,
        PowerCalculatorService $powerCalculatorService,
        LevelUpService $levelUpService,
        EventDispatcher $dispatcher,
        EffectService $effectService
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
        $this->armoryService = $armoryService;
        $this->powerCalculatorService = $powerCalculatorService;
        $this->levelUpService = $levelUpService;
        $this->dispatcher = $dispatcher;
        $this->effectService = $effectService;
    }

    public function getAttackPageData(int $userId, int $page, ?int $limit = null): array
    {
        $attackerResources = $this->resourceRepo->findByUserId($userId);
        $attackerStats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.attack', []);

        $perPage = $limit ?? $this->config->get('app.leaderboard.per_page', 25);
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

    public function getBattleReport(int $reportId, int $viewerId): ?\App\Models\Entities\BattleReport
    {
        return $this->battleRepo->findReportById($reportId, $viewerId);
    }

    public function conductAttack(int $attackerId, string $targetName, string $attackType, bool $isShadowContract = false): ServiceResponse
    {
        if (empty(trim($targetName))) {
            return ServiceResponse::error('You must enter a target.');
        }
        if ($attackType !== 'plunder') {
            return ServiceResponse::error('Invalid attack type.');
        }

        $defender = $this->userRepo->findByCharacterName($targetName);

        if (!$defender) {
            return ServiceResponse::error("Character '{$targetName}' not found.");
        }
        if ($defender->id === $attackerId) {
            return ServiceResponse::error('You cannot attack yourself.');
        }

        // Check Active Effects
        if ($this->effectService->hasActiveEffect($defender->id, 'peace_shield')) {
            return ServiceResponse::error("Target is under Safehouse protection. Attack prevented.");
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
        $turnCost = $config['attack_turn_cost'];

        if ($soldiersSent <= 0) {
            return ServiceResponse::error('You have no soldiers to send.');
        }
        if ($attackerStats->attack_turns < $turnCost) {
            return ServiceResponse::error('You do not have enough attack turns.');
        }

        // Calculate Battle Power
        $offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower(
            $attackerId, $attackerResources, $attackerStats, $attackerStructures, $attacker->alliance_id
        );
        $offensePower = $offensePowerBreakdown['total'];
        $originalOffensePower = $offensePower; // Store original for report

        $defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower(
            $defender->id, $defenderResources, $defenderStats, $defenderStructures, $defender->alliance_id
        );
        $defensePower = $defensePowerBreakdown['total'];

        // --- NEW: Planetary Shield Logic ---
        $shieldPowerBreakdown = $this->powerCalculatorService->calculateShieldPower($defenderStructures);
        $shieldHp = $shieldPowerBreakdown['total_shield_hp'];
        $damageToShield = 0;

        if ($shieldHp > 0) {
            $damageToShield = min($shieldHp, $offensePower);
            $offensePower -= $damageToShield;
        }

        // Determine Outcome
        $attackResult = 'defeat';
        if ($offensePower > $defensePower) {
            $attackResult = 'victory';
        } elseif ($offensePower == $defensePower) {
            $attackResult = 'stalemate';
        }

        // If the shield absorbed everything, it's an automatic defeat with no defender losses
        if ($shieldHp > 0 && $offensePower <= 0) {
            $attackResult = 'defeat';
        }

        // --- NEW: RATIO BASED CASUALTY LOGIC ---
        // Prevent division by zero
        $safeOffense = max(1, $offensePower);
        $safeDefense = max(1, $defensePower);
        $ratio = 1.0;

        if ($attackResult === 'victory') {
            $ratio = $safeOffense / $safeDefense;
            // Attacker is Winner
            $attackerSoldiersLost = $this->calculateWinnerLosses($soldiersSent, $ratio);
            // Defender is Loser
            $defenderGuardsLost = $this->calculateLoserLosses($defenderResources->guards, $ratio);
        } elseif ($attackResult === 'defeat') {
            // If shield absorbed all damage, no one loses anything
            if ($shieldHp > 0 && $offensePower <= 0) {
                $attackerSoldiersLost = 0;
                $defenderGuardsLost = 0;
            } else {
                $ratio = $safeDefense / $safeOffense;
                // Defender is Winner (losses applied to their guards)
                $defenderGuardsLost = $this->calculateWinnerLosses($defenderResources->guards, $ratio);
                // Attacker is Loser
                $attackerSoldiersLost = $this->calculateLoserLosses($soldiersSent, $ratio);
            }
        } else {
            // Stalemate (High casualties for both)
            $attackerSoldiersLost = (int)ceil($soldiersSent * 0.15); // Flat 15%
            $defenderGuardsLost = (int)ceil($defenderResources->guards * 0.15);
        }

        // Apply Casualty Scalar (Global Tuning)
        $casualtyScalar = $config['global_casualty_scalar'] ?? 1.0;
        $attackerSoldiersLost = (int)ceil($attackerSoldiersLost * $casualtyScalar);
        $defenderGuardsLost = (int)ceil($defenderGuardsLost * $casualtyScalar);

        // Hard Caps (Cannot lose more than you have)
        $attackerSoldiersLost = min($soldiersSent, $attackerSoldiersLost);
        $defenderGuardsLost = min($defenderResources->guards, $defenderGuardsLost);

        // --- NEW: Nanite Forge Casualty Reduction (for Defender) ---
        $naniteReductionPercent = 0.0;
        if ($attackResult === 'victory' && $defenderStructures->nanite_forge_level > 0) {
            $naniteReductionPerLevel = $this->config->get('game_balance.attack.nanite_casualty_reduction_per_level', 0.0);
            $maxNaniteReduction = $this->config->get('game_balance.attack.max_nanite_casualty_reduction', 0.0);
            
            $rawReduction = $defenderStructures->nanite_forge_level * $naniteReductionPerLevel;
            $naniteReductionPercent = min($rawReduction, $maxNaniteReduction);
            
            $defenderGuardsLost = (int)ceil($defenderGuardsLost * (1 - $naniteReductionPercent));
        }

        // XP Calculation
        $attackerXpGain = match($attackResult) {
            'victory' => $xpConfig['battle_win'],
            'defeat' => $xpConfig['battle_loss'],
            'stalemate' => $xpConfig['battle_stalemate'],
            default => 0
        };
        $defenderXpGain = match($attackResult) {
            'victory' => $xpConfig['battle_defense_loss'],
            'defeat' => $xpConfig['battle_defense_win'],
            'stalemate' => $xpConfig['battle_defense_win'],
            default => 0
        };

        // Calculate Gains (Loot)
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

        // Snapshot for Narrative
        $defenderTotalGuardsSnapshot = $defenderResources->guards;

        $transactionStartedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStartedByMe = true;
        }

        try {
            // Update Attacker Resources
            $this->resourceRepo->updateBattleAttacker(
                $attackerId,
                $attackerResources->credits + $attackerCreditGain,
                $attackerResources->soldiers - $attackerSoldiersLost
            );

            // Update Attacker Stats
            $this->levelUpService->grantExperience($attackerId, $attackerXpGain);
            $this->statsRepo->updateBattleAttackerStats(
                $attackerId,
                $attackerStats->attack_turns - $turnCost,
                $attackerStats->net_worth + $netWorthStolen,
                $attackerStats->experience + $attackerXpGain,
                $attackerStats->war_prestige + $warPrestigeGained
            );

            if ($attackResult === 'victory') {
                $this->statsRepo->incrementBattleStats($attackerId, true);
            } elseif ($attackResult === 'defeat') {
                $this->statsRepo->incrementBattleStats($attackerId, false);
            }

            // Update Defender Resources
            $this->resourceRepo->updateBattleDefender(
                $defender->id,
                max(0, $defenderResources->credits - $creditsPlundered),
                max(0, $defenderResources->guards - $defenderGuardsLost)
            );

            // Update Defender Stats
            $this->levelUpService->grantExperience($defender->id, $defenderXpGain);
            $this->statsRepo->updateBattleDefenderStats(
                $defender->id,
                max(0, $defenderStats->net_worth - $netWorthStolen)
            );

            // Create Battle Report
            $battleReportId = $this->battleRepo->createReport(
                $attackerId, $defender->id, $attackType, $attackResult, $soldiersSent,
                $attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
                $attackerXpGain, $warPrestigeGained, $netWorthStolen,
                (int)$originalOffensePower, (int)$defensePower, 
                $defenderTotalGuardsSnapshot,
                $isShadowContract,
                $shieldHp, // NEW
                $damageToShield // NEW
            );

            // Alliance Bank
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

            // Bounty Check
            $bountyMsg = "";
            if ($attackResult === 'victory') {
                $activeBounty = $this->bountyRepo->findActiveByTargetId($defender->id);
                if ($activeBounty) {
                    $amount = (float)$activeBounty['amount'];
                    $this->resourceRepo->updateResources($attackerId, 0, $amount);
                    $this->bountyRepo->claimBounty($activeBounty['id'], $attackerId);
                    $bountyMsg = " Bounty Claimed! " . number_format($amount) . " Crystals acquired.";
                }
            }

            // Events
            $event = new BattleConcludedEvent(
                $battleReportId,
                $attacker,
                $defender,
                $attackResult,
                $warPrestigeGained,
                $defenderGuardsLost,
                $creditsPlundered
            );
            $this->dispatcher->dispatch($event);

            if ($transactionStartedByMe) {
                $this->db->commit();
            }

        } catch (Throwable $e) {
            if ($transactionStartedByMe && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Attack Operation Error: '. $e->getMessage());

            if (!$transactionStartedByMe) {
                throw $e;
            }
            return ServiceResponse::error('A database error occurred. The attack was cancelled.');
        }

        $message = "Attack Complete: {$attackResult}!";
        if ($attackResult === 'victory') {
            $message .= " You plundered " . number_format($creditsPlundered) . " credits.";
        }
        $message .= " XP Gained: +{$attackerXpGain}.{$bountyMsg}";

        return ServiceResponse::success($message);
    }

    /**
     * Calculates casualties for the WINNER of the battle.
     * Logic: The higher the ratio (more overwhelming), the fewer losses.
     */
    private function calculateWinnerLosses(int $unitCount, float $ratio): int
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
    private function calculateLoserLosses(int $unitCount, float $ratio): int
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