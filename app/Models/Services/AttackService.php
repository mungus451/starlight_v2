<?php

namespace App\Models\Services;

// ... imports remain the same
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Core\Events\EventDispatcher;
use App\Events\BattleConcludedEvent;
use PDO;
use Throwable;

class AttackService
{
    // ... Constructor and dependencies remain the same ...
    private PDO $db;
    private Config $config;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private BattleRepository $battleRepo;
    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private ArmoryService $armoryService;
    private PowerCalculatorService $powerCalculatorService;
    private LevelUpService $levelUpService;
    private EventDispatcher $dispatcher;

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
        ArmoryService $armoryService,
        PowerCalculatorService $powerCalculatorService,
        LevelUpService $levelUpService,
        EventDispatcher $dispatcher
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
        $this->armoryService = $armoryService;
        $this->powerCalculatorService = $powerCalculatorService;
        $this->levelUpService = $levelUpService;
        $this->dispatcher = $dispatcher;
    }

    // ... getAttackPageData, getBattleReports, getBattleReport methods remain unchanged ...
    public function getAttackPageData(int $userId, int $page): array { 
        // ... (keep existing implementation)
        $attackerResources = $this->resourceRepo->findByUserId($userId);
        $attackerStats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.attack', []);

        $perPage = $this->config->get('app.leaderboard.per_page', 25);
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
                'totalPages' => $totalPages
            ],
            'perPage' => $perPage
        ];
    }
    
    public function getBattleReports(int $userId): array {
        $offensiveReports = $this->battleRepo->findReportsByAttackerId($userId);
        $defensiveReports = $this->battleRepo->findReportsByDefenderId($userId);
        
        $allReports = array_merge($offensiveReports, $defensiveReports);
        
        usort($allReports, function($a, $b) {
            return $b->created_at <=> $a->created_at;
        });
        
        return $allReports;
    }

    public function getBattleReport(int $reportId, int $viewerId): ?\App\Models\Entities\BattleReport {
        return $this->battleRepo->findReportById($reportId, $viewerId);
    }

    public function conductAttack(int $attackerId, string $targetName, string $attackType): ServiceResponse
    {
        // ... Validation & Setup (Identical to previous) ...
        if (empty(trim($targetName))) return ServiceResponse::error('You must enter a target.');
        if ($attackType !== 'plunder') return ServiceResponse::error('Invalid attack type.');

        $defender = $this->userRepo->findByCharacterName($targetName);
        if (!$defender) return ServiceResponse::error("Character '{$targetName}' not found.");
        if ($defender->id === $attackerId) return ServiceResponse::error('You cannot attack yourself.');

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

        if ($soldiersSent <= 0) return ServiceResponse::error('You have no soldiers to send.');
        if ($attackerStats->attack_turns < $turnCost) return ServiceResponse::error('You do not have enough attack turns.');

        // Power Calc
        $offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower($attackerId, $attackerResources, $attackerStats, $attackerStructures, $attacker->alliance_id);
        $offensePower = $offensePowerBreakdown['total'];
        
        $defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower($defender->id, $defenderResources, $defenderStats, $defenderStructures, $defender->alliance_id);
        $defensePower = $defensePowerBreakdown['total'];

        // Outcome
        $attackResult = 'defeat';
        if ($offensePower > $defensePower) $attackResult = 'victory';
        elseif ($offensePower == $defensePower) $attackResult = 'stalemate';

        // ... Casualties & Gains Calculation (Identical to previous) ...
        $attackerSoldiersLost = 0;
        $defenderGuardsLost = 0;
        $attackerXpGain = 0;
        $defenderXpGain = 0;
        
        $casualtyScalar = $config['global_casualty_scalar'] ?? 1.0;

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
        } else {
            $attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
            $defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
            $attackerXpGain = $xpConfig['battle_stalemate'] ?? 100;
            $defenderXpGain = $xpConfig['battle_defense_win'] ?? 100;
        }
        
        $attackerSoldiersLost = (int)ceil($attackerSoldiersLost * $casualtyScalar);
        $defenderGuardsLost = (int)ceil($defenderGuardsLost * $casualtyScalar);
        
        $attackerSoldiersLost = min($soldiersSent, $attackerSoldiersLost);
        $defenderGuardsLost = min($defenderResources->guards, $defenderGuardsLost);

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

        // Transaction
        $transactionStartedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStartedByMe = true;
        }

        try {
            $this->resourceRepo->updateBattleAttacker($attackerId, $attackerResources->credits + $attackerCreditGain, $attackerResources->soldiers - $attackerSoldiersLost);
            $this->levelUpService->grantExperience($attackerId, $attackerXpGain);
            $this->statsRepo->updateBattleAttackerStats($attackerId, $attackerStats->attack_turns - $turnCost, $attackerStats->net_worth + $netWorthStolen, $attackerStats->experience + $attackerXpGain, $attackerStats->war_prestige + $warPrestigeGained);
            
            // --- NEW: Increment Battle Stats ---
            if ($attackResult === 'victory') {
                $this->statsRepo->incrementBattleStats($attackerId, true);
            } elseif ($attackResult === 'defeat') {
                $this->statsRepo->incrementBattleStats($attackerId, false);
            }
            // -----------------------------------

            $this->resourceRepo->updateBattleDefender($defender->id, max(0, $defenderResources->credits - $creditsPlundered), max(0, $defenderResources->guards - $defenderGuardsLost));
            $this->levelUpService->grantExperience($defender->id, $defenderXpGain);
            $this->statsRepo->updateBattleDefenderStats($defender->id, max(0, $defenderStats->net_worth - $netWorthStolen));

            $battleReportId = $this->battleRepo->createReport(
                $attackerId, $defender->id, $attackType, $attackResult, $soldiersSent,
                $attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
                $attackerXpGain, $warPrestigeGained, $netWorthStolen,
                (int)$offensePower, (int)$defensePower
            );
            
            if ($totalTaxAmount > 0 && $attacker->alliance_id !== null) {
                $this->allianceRepo->updateBankCreditsRelative($attacker->alliance_id, $totalTaxAmount);
                if ($battleTaxAmount > 0) $this->bankLogRepo->createLog($attacker->alliance_id, $attackerId, 'battle_tax', $battleTaxAmount, "Battle tax from victory vs " . $defender->characterName);
                if ($tributeTaxAmount > 0) $this->bankLogRepo->createLog($attacker->alliance_id, $attackerId, 'tribute_tax', $tributeTaxAmount, "Tribute from victory vs " . $defender->characterName);
            }
            
            $event = new BattleConcludedEvent($battleReportId, $attacker, $defender, $attackResult, $warPrestigeGained, $defenderGuardsLost, $creditsPlundered);
            $this->dispatcher->dispatch($event);

            if ($transactionStartedByMe) $this->db->commit();
            
        } catch (Throwable $e) {
            if ($transactionStartedByMe && $this->db->inTransaction()) $this->db->rollBack();
            error_log('Attack Operation Error: '. $e->getMessage());
            if (!$transactionStartedByMe) throw $e; 
            return ServiceResponse::error('A database error occurred. The attack was cancelled.');
        }

        $message = "Attack Complete: {$attackResult}!";
        if ($attackResult === 'victory') $message .= " You plundered " . number_format($creditsPlundered) . " credits.";
        $message .= " XP Gained: +{$attackerXpGain}.";
        
        return ServiceResponse::success($message);
    }
}