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
use App\Models\Services\EffectService;
use App\Models\Entities\UserStructure;
use PDO;
use Throwable;

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
    private EffectService $effectService;

    public function __construct(
        PDO $db, Config $config, UserRepository $userRepo, ResourceRepository $resourceRepo,
        StructureRepository $structureRepo, StatsRepository $statsRepo, SpyRepository $spyRepo,
        ArmoryService $armoryService, PowerCalculatorService $powerCalculatorService,
        LevelUpService $levelUpService, NotificationService $notificationService,
        EffectService $effectService
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
    
    // ... getSpyData, getSpyReports, getSpyReport (Keep existing) ...
    public function getSpyData(int $userId, int $page): array {
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
        if (empty(trim($targetName))) return ServiceResponse::error('You must enter a target.');
        $defender = $this->userRepo->findByCharacterName($targetName);
        if (!$defender) return ServiceResponse::error("Character '{$targetName}' not found.");
        if ($defender->id === $attackerId) return ServiceResponse::error('You cannot spy on yourself.');

        // --- Active Effects Check ---
        if ($this->effectService->hasActiveEffect($defender->id, 'peace_shield')) {
            return ServiceResponse::error("Target is under Safehouse protection. Operation prevented.");
        }
        if ($this->effectService->hasActiveEffect($attackerId, 'peace_shield')) {
            $this->effectService->breakEffect($attackerId, 'peace_shield'); 
        }

        $attacker = $this->userRepo->findById($attackerId);
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        
        // --- SAFE LOADING: Structures ---
        $attackerStructures = $this->getOrInitStructures($attackerId);
        
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStructures = $this->getOrInitStructures($defender->id);
        
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
            $this->safeTransaction(function() use ($attackerId, $attackerResources, $attackerStats, $creditCost, $turnCost, $spiesSent) {
                $this->resourceRepo->updateSpyAttacker($attackerId, $attackerResources->credits - $creditCost, $spiesSent);
                $this->statsRepo->updateAttackTurns($attackerId, $attackerStats->attack_turns - $turnCost);
            });
            return ServiceResponse::success("CRITICAL FAILURE: Target signal is jammed. Operation failed, resources consumed.");
        }

        // --- 1. Calculate Power ---
        $offenseBreakdown = $this->powerCalculatorService->calculateSpyPower($attackerId, $attackerResources, $attackerStructures);
        $offense = $offenseBreakdown['total'];
        
        $defenseBreakdown = $this->powerCalculatorService->calculateSentryPower($defender->id, $defenderResources, $defenderStructures);
        $defense = $defenseBreakdown['total'];
        
        $totalPower = $offense + $defense;

        // --- 2. Determine Success (Intel Gathered?) ---
        $successChance = $totalPower > 0 ? ($offense / $totalPower) * $config['base_success_multiplier'] : 1;
        $successChance = max(min($successChance, $config['base_success_chance_cap']), $config['base_success_chance_floor']);
        $isSuccess = (mt_rand(1, 1000) / 1000) <= $successChance;

        // --- 3. Determine Caught (Combat Engaged?) ---
        $counterChance = $totalPower > 0 ? ($defense / $totalPower) * $config['base_counter_spy_multiplier'] : 0;
        $counterChance = min($counterChance, $config['base_counter_spy_chance_cap']);
        $isCaught = (mt_rand(1, 1000) / 1000) <= $counterChance;

        // --- 4. Calculate Casualties (Ratio Based) ---
        $spiesLost = 0;
        $sentriesLost = 0;

        if ($isCaught) {
            $safeOffense = max(1, $offense);
            $safeDefense = max(1, $defense);
            
            if ($offense > $defense) {
                // Spies overpower Sentries
                $ratio = $safeOffense / $safeDefense;
                $spiesLost = $this->calculateWinnerLosses($spiesSent, $ratio);
                $sentriesLost = $this->calculateLoserLosses($defenderResources->sentries, $ratio);
            } else {
                // Sentries overpower Spies
                $ratio = $safeDefense / $safeOffense;
                $spiesLost = $this->calculateLoserLosses($spiesSent, $ratio);
                $sentriesLost = $this->calculateWinnerLosses($defenderResources->sentries, $ratio);
            }

            // Casualties are generally higher/squishier for spies
            $casualtyScalar = 0.2; 
            
            $spiesLost = (int)ceil($spiesLost * $casualtyScalar);
            $sentriesLost = (int)ceil($sentriesLost * $casualtyScalar);

            $spiesLost = min($spiesSent, $spiesLost);
            $sentriesLost = min($defenderResources->sentries, $sentriesLost);
        }

        // --- 5. XP & Intel ---
        $attackerXp = $isSuccess ? ($xpConfig['spy_success'] ?? 100) : ($isCaught ? ($xpConfig['spy_caught'] ?? 10) : ($xpConfig['spy_fail_survived'] ?? 25));
        $defenderXp = $isCaught ? ($xpConfig['defense_caught_spy'] ?? 75) : 0;
        $operation_result = $isSuccess ? 'success' : 'failure';
        
        $stolen_naquadah = $isSuccess ? ($defenderResources->naquadah_crystals * ($config['crystal_steal_rate'] ?? 0.05)) : 0;
        $stolen_dark_matter = $isSuccess ? (int)floor($defenderResources->dark_matter * ($config['dark_matter_steal_rate'] ?? 0.02)) : 0;
        $stolen_protoform = $isSuccess ? ($defenderResources->protoform * ($config['protoform_steal_rate'] ?? 0.01)) : 0;

        $intel_credits = $isSuccess ? $defenderResources->credits : null;
        $intel_naquadah = $isSuccess ? $defenderResources->naquadah_crystals : null;
        $intel_dark_matter = $isSuccess ? $defenderResources->dark_matter : null;
        $intel_protoform = $isSuccess ? $defenderResources->protoform : null;
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

        $defenderTotalSentriesSnapshot = $defenderResources->sentries;

        $reportId = null; // Initialize variable

        // --- Transaction ---
        $this->safeTransaction(function() use (
            $attackerId, $defender, $attackerResources, $attackerStats, $defenderResources,
            $creditCost, $turnCost, $spiesLost, $sentriesLost, $attackerXp, $defenderXp, $isSuccess, $isCaught,
            $spiesSent, $operation_result, $defenderTotalSentriesSnapshot, $attacker,
            $stolen_naquadah, $stolen_dark_matter, $stolen_protoform,
            $intel_credits, $intel_naquadah, $intel_dark_matter, $intel_protoform, $intel_gemstones, $intel_workers, $intel_soldiers, $intel_guards, $intel_spies, $intel_sentries,
            $intel_fortLevel, $intel_offenseLevel, $intel_defenseLevel, $intel_spyLevel, $intel_econLevel, $intel_popLevel, $intel_armoryLevel,
            &$reportId // Pass by reference
        ) {
            $this->resourceRepo->updateSpyAttacker($attackerId, $attackerResources->credits - $creditCost, $attackerResources->spies - $spiesLost);
            $this->statsRepo->updateAttackTurns($attackerId, $attackerStats->attack_turns - $turnCost);
            $this->levelUpService->grantExperience($attackerId, $attackerXp);
            $this->statsRepo->incrementSpyStats($attackerId, $isSuccess);

            if ($isSuccess && ($stolen_naquadah > 0 || $stolen_dark_matter > 0 || $stolen_protoform > 0)) {
                $this->resourceRepo->updateResources($attackerId, 0, $stolen_naquadah, $stolen_dark_matter, $stolen_protoform);
                $this->resourceRepo->updateResources($defender->id, 0, -$stolen_naquadah, -$stolen_dark_matter, -$stolen_protoform);
            }

            if ($isCaught) {
                if ($sentriesLost > 0) $this->resourceRepo->updateSpyDefender($defender->id, max(0, $defenderResources->sentries - $sentriesLost));
                $this->levelUpService->grantExperience($defender->id, $defenderXp);
            }

            $reportId = $this->spyRepo->createReport(
                $attackerId, $defender->id, $operation_result, $spiesSent, $spiesLost, $sentriesLost,
                $defenderTotalSentriesSnapshot,
                $intel_credits, 
                $intel_gemstones, $intel_workers, $intel_soldiers, $intel_guards, $intel_spies, $intel_sentries,
                $intel_fortLevel, $intel_offenseLevel, $intel_defenseLevel, $intel_spyLevel, $intel_econLevel, $intel_popLevel, $intel_armoryLevel,
                $stolen_naquadah, $stolen_dark_matter, $intel_naquadah, $intel_dark_matter,
                $stolen_protoform, $intel_protoform
            );

            if ($isCaught) {
                $notifTitle = "Security Alert: Spy Neutralized";
                $notifMsg = "An enemy spy from {$attacker->characterName} was intercepted.";
                if ($sentriesLost > 0) $notifMsg .= " You lost {$sentriesLost} sentries.";
                $notifMsg .= " XP Gained: +{$defenderXp}.";
                $this->notificationService->sendNotification($defender->id, 'spy', $notifTitle, $notifMsg, "/spy/report/{$reportId}");
            }
        });

        $message = "Operation {$operation_result}. XP Gained: +{$attackerXp}.";
        if ($isSuccess) {
            if ($stolen_naquadah > 0 || $stolen_dark_matter > 0 || $stolen_protoform > 0) {
                $message .= " Your spies managed to steal ";
                $stolenParts = [];
                if ($stolen_naquadah > 0) $stolenParts[] = number_format($stolen_naquadah, 2) . " crystals";
                if ($stolen_dark_matter > 0) $stolenParts[] = $stolen_dark_matter . " dark matter";
                if ($stolen_protoform > 0) $stolenParts[] = number_format($stolen_protoform, 2) . " protoform";
                $message .= implode(", ", $stolenParts) . ".";
            }
        }
        if ($isCaught && $sentriesLost > 0) $message .= " You destroyed {$sentriesLost} enemy sentries.";
        
        return ServiceResponse::success($message, ['report_id' => $reportId, 'result' => $operation_result]);
    }

    /**
     * Safety wrapper for transactions to handle nested/test scenarios.
     */
    private function safeTransaction(callable $callback): void
    {
        $startedByMe = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $startedByMe = true;
        }

        try {
            $callback();
            if ($startedByMe) $this->db->commit();
        } catch (Throwable $e) {
            if ($startedByMe && $this->db->inTransaction()) $this->db->rollBack();
            error_log('Spy Transaction Error: ' . $e->getMessage());
            throw $e; // Re-throw to controller/test
        }
    }

    /**
     * Safely load structures, creating defaults if missing.
     */
    private function getOrInitStructures(int $userId): UserStructure
    {
        $struct = $this->structureRepo->findByUserId($userId);
        if (!$struct) {
            // Self-Heal: Create missing row
            $this->structureRepo->createDefaults($userId);
            $struct = $this->structureRepo->findByUserId($userId);
        }
        return $struct;
    }

    private function calculateWinnerLosses(int $unitCount, float $ratio): int
    {
        $lossPercent = 0.05 / $ratio;
        $losses = (int)ceil($unitCount * $lossPercent);
        $variance = (int)ceil($losses * 0.2);
        $losses = mt_rand(max(0, $losses - $variance), $losses + $variance);
        return $losses;
    }

    private function calculateLoserLosses(int $unitCount, float $ratio): int
    {
        if ($unitCount <= 0) return 0;
        if ($ratio >= 10.0) return $unitCount;

        $lossPercent = 0.10 * $ratio;
        $lossPercent = min(1.0, $lossPercent);
        $losses = (int)ceil($unitCount * $lossPercent);
        $variance = (int)ceil($losses * 0.1);
        $losses = mt_rand(max(1, $losses - $variance), min($unitCount, $losses + $variance));
        return max(1, $losses);
    }
}