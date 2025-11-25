<?php

namespace App\Models\Services;

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
use PDO;
use Throwable;

/**
 * Handles all business logic for Espionage.
 * * Refactored Phase 2.1: View Logic cleanup.
 * * Calculates costs internally before sending to view.
 */
class SpyService
{
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

    public function __construct(
        PDO $db,
        Config $config,
        UserRepository $userRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo,
        SpyRepository $spyRepo,
        ArmoryService $armoryService,
        PowerCalculatorService $powerCalculatorService,
        LevelUpService $levelUpService,
        NotificationService $notificationService
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
    }

    /**
     * Gets all data needed to render the main spy page.
     * Calculates operation costs based on current spy count.
     *
     * @param int $userId
     * @param int $page
     * @return array
     */
    public function getSpyData(int $userId, int $page): array
    {
        $resources = $this->resourceRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.spy', []);

        // --- LOGIC MOVED FROM VIEW TO SERVICE ---
        $spiesToSend = $resources->spies;
        $totalCreditCost = $costs['cost_per_spy'] * $spiesToSend;
        $turnCost = $costs['attack_turn_cost'];
        // ----------------------------------------

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
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages
            ],
            'perPage' => $perPage,
            // Pre-calculated values for the view
            'operation' => [
                'spies_to_send' => $spiesToSend,
                'total_credit_cost' => $totalCreditCost,
                'turn_cost' => $turnCost
            ]
        ];
    }

    /**
     * Gets the list of spy reports for the user (offensive and defensive).
     */
    public function getSpyReports(int $userId): array
    {
        $offensiveReports = $this->spyRepo->findReportsByAttackerId($userId);
        $defensiveReports = $this->spyRepo->findReportsByDefenderId($userId);

        $allReports = array_merge($offensiveReports, $defensiveReports);

        usort($allReports, function($a, $b) {
            return $b->created_at <=> $a->created_at;
        });

        return $allReports;
    }

    /**
     * Gets a single, specific spy report, ensuring the user owns it.
     */
    public function getSpyReport(int $reportId, int $viewerId): ?\App\Models\Entities\SpyReport
    {
        return $this->spyRepo->findReportById($reportId, $viewerId);
    }

    /**
     * Conducts an "all-in" espionage operation.
     */
    public function conductOperation(int $attackerId, string $targetName): ServiceResponse
    {
        // 1. Validation (Target)
        if (empty(trim($targetName))) {
            return ServiceResponse::error('You must enter a target.');
        }

        $defender = $this->userRepo->findByCharacterName($targetName);

        if (!$defender) {
            return ServiceResponse::error("Character '{$targetName}' not found.");
        }

        if ($defender->id === $attackerId) {
            return ServiceResponse::error('You cannot spy on yourself.');
        }

        // 2. Get All Data
        $attacker = $this->userRepo->findById($attackerId);
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        $attackerStructures = $this->structureRepo->findByUserId($attackerId);
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStructures = $this->structureRepo->findByUserId($defender->id);
        
        $config = $this->config->get('game_balance.spy');
        $xpConfig = $this->config->get('game_balance.xp.rewards');

        // 3. Check Costs & Availability
        $spiesSent = $attackerResources->spies;
        $creditCost = $config['cost_per_spy'] * $spiesSent;
        $turnCost = $config['attack_turn_cost'];

        if ($spiesSent <= 0) {
            return ServiceResponse::error('You have no spies to send.');
        }
        if ($attackerResources->credits < $creditCost) {
            return ServiceResponse::error('You do not have enough credits to send all your spies.');
        }
        if ($attackerStats->attack_turns < $turnCost) {
            return ServiceResponse::error('You do not have enough attack turns.');
        }

        // 4. Calculate Spy Roll
        $offenseBreakdown = $this->powerCalculatorService->calculateSpyPower(
            $attackerId,
            $attackerResources,
            $attackerStructures
        );
        $offense = $offenseBreakdown['total'];

        $defenseBreakdown = $this->powerCalculatorService->calculateSentryPower(
            $defender->id,
            $defenderResources,
            $defenderStructures
        );
        $defense = $defenseBreakdown['total'];
        
        $totalPower = $offense + $defense;

        $successChance = $totalPower > 0 ? ($offense / $totalPower) * $config['base_success_multiplier'] : 1;
        $successChance = max(min($successChance, $config['base_success_chance_cap']), $config['base_success_chance_floor']);
        $isSuccess = (mt_rand(1, 1000) / 1000) <= $successChance;

        // 5. Calculate Counter-Spy (Caught/Not Caught)
        $counterChance = $totalPower > 0 ? ($defense / $totalPower) * $config['base_counter_spy_multiplier'] : 0;
        $counterChance = min($counterChance, $config['base_counter_spy_chance_cap']);
        $isCaught = (mt_rand(1, 1000) / 1000) <= $counterChance;

        // 6. Calculate Losses & XP
        $spiesLost = $isCaught ? (int)mt_rand($spiesSent * $config['spies_lost_percent_min'], $spiesSent * $config['spies_lost_percent_max']) : 0;
        $sentriesLost = $isCaught ? (int)mt_rand($defenderResources->sentries * $config['sentries_lost_percent_min'], $defenderResources->sentries * $config['sentries_lost_percent_max']) : 0;

        $attackerXp = 0;
        $defenderXp = 0;
        
        if ($isSuccess) {
            $attackerXp = $xpConfig['spy_success'] ?? 100;
        } else {
            $attackerXp = $isCaught ? ($xpConfig['spy_caught'] ?? 10) : ($xpConfig['spy_fail_survived'] ?? 25);
        }
        
        if ($isCaught) {
            $defenderXp = $xpConfig['defense_caught_spy'] ?? 75;
        }

        // 7. Gather Intel & Determine Result
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

        // 8. Execute Transaction
        $this->db->beginTransaction();
        try {
            // 8a. Update Attacker
            $newCredits = $attackerResources->credits - $creditCost;
            $newSpies = $attackerResources->spies - $spiesLost;
            $this->resourceRepo->updateSpyAttacker($attackerId, $newCredits, $newSpies);
            
            $newAttackTurns = $attackerStats->attack_turns - $turnCost;
            $this->statsRepo->updateAttackTurns($attackerId, $newAttackTurns);
            
            $this->levelUpService->grantExperience($attackerId, $attackerXp);

            // 8b. Update Defender & Notify
            if ($isCaught) {
                if ($sentriesLost > 0) {
                    $newSentries = max(0, $defenderResources->sentries - $sentriesLost);
                    $this->resourceRepo->updateSpyDefender($defender->id, $newSentries);
                }
                $this->levelUpService->grantExperience($defender->id, $defenderXp);
            }

            // 8c. Create Spy Report
            $reportId = $this->spyRepo->createReport(
                $attackerId, $defender->id, $operation_result, $spiesSent, $spiesLost, $sentriesLost,
                $intel_credits, $intel_gemstones, $intel_workers, $intel_soldiers, $intel_guards, $intel_spies, $intel_sentries,
                $intel_fortLevel, $intel_offenseLevel, $intel_defenseLevel, $intel_spyLevel, $intel_econLevel, $intel_popLevel, $intel_armoryLevel
            );

            // 8d. Send Notification (If Caught)
            if ($isCaught) {
                $notifTitle = "Security Alert: Spy Neutralized";
                $notifMsg = "An enemy spy from {$attacker->characterName} was intercepted by your sentries.";
                if ($sentriesLost > 0) {
                    $notifMsg .= " You lost {$sentriesLost} sentries during the operation.";
                }
                $notifMsg .= " XP Gained: +{$defenderXp}.";

                $this->notificationService->sendNotification(
                    $defender->id,
                    'spy',
                    $notifTitle,
                    $notifMsg,
                    "/spy/report/{$reportId}"
                );
            }

            $this->db->commit();
            
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Spy Operation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred. The operation was cancelled.');
        }

        // 9. Construct Success Message
        $message = "Operation {$operation_result}. XP Gained: +{$attackerXp}.";
        if ($isCaught && $sentriesLost > 0) {
            $message .= " You destroyed {$sentriesLost} enemy sentries.";
        }
        
        return ServiceResponse::success($message);
    }
}