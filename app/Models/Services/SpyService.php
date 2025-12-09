<?php

namespace App\Models\Services;

// ... imports same as before ...
use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\SpyRepository;
use App\Models\Services\ArmoryService; 
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\NotificationService;
use App\Models\Services\EffectService; // --- NEW ---
use PDO;
use Throwable;

class SpyService
{
    // ... Constructor and properties same as before ...
    private PDO $db;
    private Config $config;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private SpyRepository $spyRepo;
    private ArmoryService $armoryService;
    private PowerCalculatorService $powerCalculatorService;
    private LevelUpService $levelUpService;
    private NotificationService $notificationService;
    private EffectService $effectService; // --- NEW ---

    public function __construct(
        PDO $db, Config $config, UserRepository $userRepo, ResourceRepository $resourceRepo,
        StructureRepository $structureRepo, StatsRepository $statsRepo, SpyRepository $spyRepo,
        ArmoryService $armoryService, PowerCalculatorService $powerCalculatorService,
        LevelUpService $levelUpService, NotificationService $notificationService,
        EffectService $effectService // --- NEW ---
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        $this->spyRepo = $spyRepo;
        $this->armoryService = $armoryService;
        $this->powerCalculatorService = $powerCalculatorService;
        $this->levelUpService = $levelUpService;
        $this->notificationService = $notificationService;
        $this->effectService = $effectService;
    }
    
    // ... getSpyData, getSpyReports, getSpyReport same ...
    public function getSpyData(int $userId, int $page): array {
        // ... (keep existing implementation)
        $resources = $this->resourceRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.spy', []);
        $spiesToSend = $resources->spies;
        $totalCreditCost = $costs['cost_per_spy'] * $spiesToSend;
        $turnCost = $costs['attack_turn_cost'];
        $perPage = $this->config->get('app.leaderboard.per_page', 25);
        $totalTargets = $this->statsRepo->getTotalTargetCount($userId);
        $totalPages = (int)ceil($totalTargets / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $perPage;
        $targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $userId);

        return [
            'resources' => $resources,
            'stats' => $stats,
            'costs' => $costs,
            'targets' => $targets,
            'pagination' => ['currentPage' => $page, 'totalPages' => $totalPages],
            'perPage' => $perPage,
            'operation' => ['spies_to_send' => $spiesToSend, 'total_credit_cost' => $totalCreditCost, 'turn_cost' => $turnCost]
        ];
    }

    public function getSpyReports(int $userId): array {
        $offensiveReports = $this->spyRepo->findReportsByAttackerId($userId);
        $defensiveReports = $this->spyRepo->findReportsByDefenderId($userId);
        $allReports = array_merge($offensiveReports, $defensiveReports);
        usort($allReports, function($a, $b) { return $b->created_at <=> $a->created_at; });
        return $allReports;
    }

    public function getSpyReport(int $reportId, int $viewerId): ?\App\Models\Entities\SpyReport {
        return $this->spyRepo->findReportById($reportId, $viewerId);
    }

    public function conductOperation(int $attackerId, string $targetName): ServiceResponse
    {
        // ... Validation & Calc (Same as before) ...
        if (empty(trim($targetName))) return ServiceResponse::error('You must enter a target.');
        $defender = $this->userRepo->findByCharacterName($targetName);
        if (!$defender) return ServiceResponse::error("Character '{$targetName}' not found.");
        if ($defender->id === $attackerId) return ServiceResponse::error('You cannot spy on yourself.');

        $attacker = $this->userRepo->findById($attackerId);
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        $attackerStructures = $this->structureRepo->findByUserId($attackerId);
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStructures = $this->structureRepo->findByUserId($defender->id);
        
        $config = $this->config->get('game_balance.spy');
        $xpConfig = $this->config->get('game_balance.xp.rewards');

        $spiesSent = $attackerResources->spies;
        $creditCost = $config['cost_per_spy'] * $spiesSent;
        $turnCost = $config['attack_turn_cost'];

        if ($spiesSent <= 0) return ServiceResponse::error('You have no spies to send.');
        if ($attackerResources->credits < $creditCost) return ServiceResponse::error('You do not have enough credits.');
        if ($attackerStats->attack_turns < $turnCost) return ServiceResponse::error('You do not have enough attack turns.');

        // --- Check for Radar Jamming ---
        if ($this->effectService->hasActiveEffect($defender->id, 'jamming')) {
            // Deduct costs (The price of failure)
            $this->db->beginTransaction();
            try {
                $this->resourceRepo->updateSpyAttacker($attackerId, $attackerResources->credits - $creditCost, $attackerResources->spies); // No spies lost, just cost
                $this->statsRepo->updateAttackTurns($attackerId, $attackerStats->attack_turns - $turnCost);
                $this->db->commit();
            } catch (Throwable $e) {
                $this->db->rollBack();
            }
            return ServiceResponse::success("CRITICAL FAILURE: Target signal is jammed. Operation failed, resources consumed.");
        }
        // -------------------------------

        // Rolls
        $offenseBreakdown = $this->powerCalculatorService->calculateSpyPower($attackerId, $attackerResources, $attackerStructures);
        $offense = $offenseBreakdown['total'];
        $defenseBreakdown = $this->powerCalculatorService->calculateSentryPower($defender->id, $defenderResources, $defenderStructures);
        $defense = $defenseBreakdown['total'];
        $totalPower = $offense + $defense;

        $successChance = $totalPower > 0 ? ($offense / $totalPower) * $config['base_success_multiplier'] : 1;
        $successChance = max(min($successChance, $config['base_success_chance_cap']), $config['base_success_chance_floor']);
        $isSuccess = (mt_rand(1, 1000) / 1000) <= $successChance;

        $counterChance = $totalPower > 0 ? ($defense / $totalPower) * $config['base_counter_spy_multiplier'] : 0;
        $counterChance = min($counterChance, $config['base_counter_spy_chance_cap']);
        $isCaught = (mt_rand(1, 1000) / 1000) <= $counterChance;

        // Losses & XP
        $spiesLost = $isCaught ? (int)mt_rand($spiesSent * $config['spies_lost_percent_min'], $spiesSent * $config['spies_lost_percent_max']) : 0;
        $sentriesLost = $isCaught ? (int)mt_rand($defenderResources->sentries * $config['sentries_lost_percent_min'], $defenderResources->sentries * $config['sentries_lost_percent_max']) : 0;

        $attackerXp = $isSuccess ? ($xpConfig['spy_success'] ?? 100) : ($isCaught ? ($xpConfig['spy_caught'] ?? 10) : ($xpConfig['spy_fail_survived'] ?? 25));
        $defenderXp = $isCaught ? ($xpConfig['defense_caught_spy'] ?? 75) : 0;

        // Intel
        $operation_result = $isSuccess ? 'success' : 'failure';
        
        $intel_credits = $isSuccess ? $defenderResources->credits : null;
        $intel_gemstones = $isSuccess ? $defenderResources->gemstones : null;
        $intel_workers = $isSuccess ? $defenderResources->workers : null;
        $intel_soldiers = $isSuccess ? $defenderResources->soldiers : null;
        $intel_guards = $isSuccess ? $defenderResources->guards : null;
        $intel_spies = $isSuccess ? $defenderResources->spies : null;
        $intel_sentries = $isSuccess ? $defenderResources->sentries : null;
        $intel_fortLevel = $isSuccess ? $defenderStructures->fortification_level : null;
        $intel_offenseLevel = $isSuccess ? $defenderStructures->offense_upgrade_level : null;
        $intel_defenseLevel = $isSuccess ? $defenderStructures->defense_upgrade_level : null;
        $intel_spyLevel = $isSuccess ? $defenderStructures->spy_upgrade_level : null;
        $intel_econLevel = $isSuccess ? $defenderStructures->economy_upgrade_level : null;
        $intel_popLevel = $isSuccess ? $defenderStructures->population_level : null;
        $intel_armoryLevel = $isSuccess ? $defenderStructures->armory_level : null;

        // Transaction
        $this->db->beginTransaction();
        try {
            $this->resourceRepo->updateSpyAttacker($attackerId, $attackerResources->credits - $creditCost, $attackerResources->spies - $spiesLost);
            $this->statsRepo->updateAttackTurns($attackerId, $attackerStats->attack_turns - $turnCost);
            $this->levelUpService->grantExperience($attackerId, $attackerXp);
            
            // --- NEW: Increment Spy Stats ---
            $this->statsRepo->incrementSpyStats($attackerId, $isSuccess);
            // --------------------------------

            if ($isCaught) {
                if ($sentriesLost > 0) $this->resourceRepo->updateSpyDefender($defender->id, max(0, $defenderResources->sentries - $sentriesLost));
                $this->levelUpService->grantExperience($defender->id, $defenderXp);
            }

            $reportId = $this->spyRepo->createReport(
                $attackerId, $defender->id, $operation_result, $spiesSent, $spiesLost, $sentriesLost,
                $intel_credits, $intel_gemstones, $intel_workers, $intel_soldiers, $intel_guards, $intel_spies, $intel_sentries,
                $intel_fortLevel, $intel_offenseLevel, $intel_defenseLevel, $intel_spyLevel, $intel_econLevel, $intel_popLevel, $intel_armoryLevel
            );

            if ($isCaught) {
                $notifTitle = "Security Alert: Spy Neutralized";
                $notifMsg = "An enemy spy from {$attacker->characterName} was intercepted.";
                if ($sentriesLost > 0) $notifMsg .= " You lost {$sentriesLost} sentries.";
                $notifMsg .= " XP Gained: +{$defenderXp}.";
                $this->notificationService->sendNotification($defender->id, 'spy', $notifTitle, $notifMsg, "/spy/report/{$reportId}");
            }

            $this->db->commit();
            
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Spy Operation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }

        $message = "Operation {$operation_result}. XP Gained: +{$attackerXp}.";
        if ($isCaught && $sentriesLost > 0) $message .= " You destroyed {$sentriesLost} enemy sentries.";
        
        return ServiceResponse::success($message);
    }
}