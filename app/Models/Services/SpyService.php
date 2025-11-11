<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\SpyRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for Espionage.
 */
class SpyService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private SpyRepository $spyRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        
        // This service needs all five repositories for its complex logic
        $this->userRepo = new UserRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->structureRepo = new StructureRepository($this->db);
        $this->statsRepo = new StatsRepository($this->db);
        $this->spyRepo = new SpyRepository($this->db);
    }

    /**
     * Gets all data needed to render the main spy page.
     *
     * @param int $userId
     * @return array
     */
    public function getSpyData(int $userId): array
    {
        $resources = $this->resourceRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.spy', []);

        return [
            'resources' => $resources,
            'stats' => $stats,
            'costs' => $costs
        ];
    }

    /**
     * Gets the list of spy reports for the user.
     *
     * @param int $userId
     * @return array
     */
    public function getSpyReports(int $userId): array
    {
        return $this->spyRepo->findReportsByAttackerId($userId);
    }

    /**
     * Gets a single, specific spy report, ensuring the user owns it.
     *
     * @param int $reportId
     * @param int $userId
     * @return \App\Models\Entities\SpyReport|null
     */
    public function getSpyReport(int $reportId, int $userId): ?\App\Models\Entities\SpyReport
    {
        return $this->spyRepo->findReportById($reportId, $userId);
    }

    /**
     * Conducts an "all-in" espionage operation.
     *
     * @param int $attackerId
     * @param string $targetName
     * @return bool True on success
     */
    public function conductOperation(int $attackerId, string $targetName): bool
    {
        // --- 1. Validation (Target) ---
        if (empty(trim($targetName))) {
            $this->session->setFlash('error', 'You must enter a target.');
            return false;
        }

        $defender = $this->userRepo->findByCharacterName($targetName);

        if (!$defender) {
            $this->session->setFlash('error', "Character '{$targetName}' not found.");
            return false;
        }

        if ($defender->id === $attackerId) {
            $this->session->setFlash('error', 'You cannot spy on yourself.');
            return false;
        }

        // --- 2. Get All Data ---
        $attackerResources = $this->resourceRepo->findByUserId($attackerId);
        $attackerStats = $this->statsRepo->findByUserId($attackerId);
        $attackerStructures = $this->structureRepo->findByUserId($attackerId);
        $defenderResources = $this->resourceRepo->findByUserId($defender->id);
        $defenderStructures = $this->structureRepo->findByUserId($defender->id);
        $config = $this->config->get('game_balance.spy');

        // --- 3. Check Costs & Availability ---
        $spiesSent = $attackerResources->spies;
        $creditCost = $config['cost_per_spy'] * $spiesSent;
        $turnCost = $config['attack_turn_cost'];

        if ($spiesSent <= 0) {
            $this->session->setFlash('error', 'You have no spies to send.');
            return false;
        }
        if ($attackerResources->credits < $creditCost) {
            $this->session->setFlash('error', 'You do not have enough credits to send all your spies.');
            return false;
        }
        if ($attackerStats->attack_turns < $turnCost) {
            $this->session->setFlash('error', 'You do not have enough attack turns.');
            return false;
        }

        // --- 4. Calculate Spy Roll (Success/Failure) ---
        $offense = $spiesSent * (1 + ($attackerStructures->spy_upgrade_level * $config['offense_power_per_level']));
        $defense = $defenderResources->sentries * (1 + ($defenderStructures->spy_upgrade_level * $config['defense_power_per_level']));
        $totalPower = $offense + $defense;

        $successChance = $totalPower > 0 ? ($offense / $totalPower) * $config['base_success_multiplier'] : 1;
        $successChance = max(min($successChance, $config['base_success_chance_cap']), $config['base_success_chance_floor']);
        $isSuccess = (mt_rand(1, 1000) / 1000) <= $successChance;

        // --- 5. Calculate Counter-Spy (Caught/Not Caught) ---
        $counterChance = $totalPower > 0 ? ($defense / $totalPower) * $config['base_counter_spy_multiplier'] : 0;
        $counterChance = min($counterChance, $config['base_counter_spy_chance_cap']);
        $isCaught = (mt_rand(1, 1000) / 1000) <= $counterChance;

        // --- 6. Calculate Losses ---
        $spiesLost = $isCaught ? (int)mt_rand($spiesSent * $config['spies_lost_percent_min'], $spiesSent * $config['spies_lost_percent_max']) : 0;
        $sentriesLost = $isCaught ? (int)mt_rand($defenderResources->sentries * $config['sentries_lost_percent_min'], $defenderResources->sentries * $config['sentries_lost_percent_max']) : 0;

        // --- 7. Gather Intel & Determine Result ---
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

        // --- 8. Execute 3-Table Transaction ---
        $this->db->beginTransaction();
        try {
            // 8a. Update Attacker Resources
            $newCredits = $attackerResources->credits - $creditCost;
            $newSpies = $attackerResources->spies - $spiesLost;
            $this->resourceRepo->updateSpyAttacker($attackerId, $newCredits, $newSpies);
            
            // 8b. Update Attacker Stats
            $newAttackTurns = $attackerStats->attack_turns - $turnCost;
            $this->statsRepo->updateAttackTurns($attackerId, $newAttackTurns);

            // 8c. Update Defender Resources (if caught)
            if ($isCaught && $sentriesLost > 0) {
                $newSentries = max(0, $defenderResources->sentries - $sentriesLost);
                $this->resourceRepo->updateSpyDefender($defender->id, $newSentries);
            }

            // 8d. Create Spy Report
            $this->spyRepo->createReport(
                $attackerId, $defender->id, $operation_result, $spiesSent, $spiesLost, $sentriesLost,
                $intel_credits, $intel_gemstones, $intel_workers, $intel_soldiers, $intel_guards, $intel_spies, $intel_sentries,
                $intel_fortLevel, $intel_offenseLevel, $intel_defenseLevel, $intel_spyLevel, $intel_econLevel, $intel_popLevel, $intel_armoryLevel
            );

            // 8e. Commit
            $this->db->commit();
            
        } catch (Throwable $e) {
            // 8f. Rollback on failure
            $this->db->rollBack();
            error_log('Spy Operation Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. The operation was cancelled.');
            return false;
        }

        // --- 9. Set Flash Message ---
        $message = "Operation {$operation_result}. You sent {$spiesSent} spies and lost {$spiesLost}.";
        if ($isCaught && $sentriesLost > 0) {
            $message .= " You destroyed {$sentriesLost} enemy sentries.";
        }
        $this->session->setFlash('success', $message);
        return true;
    }
}