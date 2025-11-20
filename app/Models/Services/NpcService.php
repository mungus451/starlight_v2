<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Services\StructureService;
use App\Models\Services\TrainingService;
use App\Models\Services\ArmoryService;
use App\Models\Services\AttackService;
use App\Models\Services\AllianceStructureService;
use PDO;
use Throwable;

/**
 * Orchestrates the autonomous behavior of NPC agents ("The Void Syndicate").
 * Intended to be run via Cron.
 */
class NpcService
{
    private PDO $db;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private AllianceRepository $allianceRepo;

    // Services for actions
    private StructureService $structureService;
    private TrainingService $trainingService;
    private ArmoryService $armoryService;
    private AttackService $attackService;
    private AllianceStructureService $allianceStructureService;

    // Logging
    private string $logFile;

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Repositories
        $this->userRepo = new UserRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->structureRepo = new StructureRepository($this->db);
        $this->statsRepo = new StatsRepository($this->db);
        $this->allianceRepo = new AllianceRepository($this->db);

        // Services
        $this->structureService = new StructureService();
        $this->trainingService = new TrainingService();
        $this->armoryService = new ArmoryService();
        $this->attackService = new AttackService();
        $this->allianceStructureService = new AllianceStructureService();

        // Set log path (relative to this file location in /app/Models/Services)
        $this->logFile = __DIR__ . '/../../../logs/npc_actions.log';
    }

    /**
     * Internal logger helper. Writes to file and echos to console.
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[$timestamp] $message" . PHP_EOL;
        
        // 1. Write to file
        file_put_contents($this->logFile, $line, FILE_APPEND);
        
        // 2. Output to console (if running in CLI)
        if (php_sapi_name() === 'cli') {
            echo $line;
        }
    }

    /**
     * Main entry point. Iterates through all NPCs and processes their turn.
     */
    public function runNpcCycle(): void
    {
        $this->log("--- STARTING NPC AGENT CYCLE ---");
        
        $npcs = $this->userRepo->findNpcs();
        $count = count($npcs);
        $this->log("Found {$count} NPC agents active.");

        if ($count === 0) {
            $this->log("WARNING: No NPCs found. Did you run the migration '17.1_create_npc_faction.php'?");
            return;
        }
        
        foreach ($npcs as $npc) {
            try {
                $this->processNpc($npc);
            } catch (Throwable $e) {
                $this->log("ERROR processing {$npc->characterName}: " . $e->getMessage());
            }
        }

        $this->log("--- NPC CYCLE COMPLETE ---");
    }

    /**
     * Decisions Logic for a single NPC.
     */
    private function processNpc(User $npc): void
    {
        $this->log("Processing Agent: {$npc->characterName} (ID: {$npc->id})");

        // 1. Economy & Structures
        $this->manageEconomy($npc);

        // 2. Recruitment
        $this->manageTraining($npc);

        // 3. Armory & Equipment
        $this->manageArmory($npc);

        // 4. Alliance Duties (Leader only)
        $this->manageAlliance($npc);

        // 5. Aggression (Attacks)
        $this->manageAggression($npc);
        
        $this->log("Agent {$npc->characterName} finished.");
        $this->log("----------------------------------------");
    }

    private function manageEconomy(User $npc): void
    {
        $structures = $this->structureRepo->findByUserId($npc->id);
        $resources = $this->resourceRepo->findByUserId($npc->id);
        
        if (!$structures || !$resources) {
            $this->log("  -> SKIPPING Economy: Missing data.");
            return;
        }

        $target = 'economy_upgrade';
        // If Economy is decent, randomize development
        if ($structures->economy_upgrade_level >= 20) {
            $options = ['fortification', 'offense_upgrade', 'defense_upgrade', 'spy_upgrade', 'population', 'armory'];
            $target = $options[array_rand($options)];
        }

        // Simple heuristic logging
        $this->log("  -> Economy Check: Target={$target}, Current Credits=" . number_format($resources->credits));

        // Attempt upgrade
        if ($this->structureService->upgradeStructure($npc->id, $target)) {
            $this->log("  -> SUCCESS: Upgraded {$target}.");
        } else {
            $this->log("  -> FAILURE: Could not upgrade {$target} (Likely insufficient funds).");
        }
    }

    private function manageTraining(User $npc): void
    {
        $resources = $this->resourceRepo->findByUserId($npc->id);
        if (!$resources) return;
        
        $this->log("  -> Training Check: Citizens={$resources->untrained_citizens}, Soldiers={$resources->soldiers}, Guards={$resources->guards}");

        if ($resources->untrained_citizens < 100) {
            $this->log("  -> SKIPPING Training: Not enough citizens.");
            return;
        }

        // Train in batches
        $toTrain = min($resources->untrained_citizens, 500);
        
        // 40% Soldiers, 40% Guards, 20% Spies
        $soldiers = (int)floor($toTrain * 0.4);
        $guards = (int)floor($toTrain * 0.4);
        $spies = (int)floor($toTrain * 0.2);

        if ($soldiers > 0 && $this->trainingService->trainUnits($npc->id, 'soldiers', $soldiers)) {
            $this->log("  -> Trained {$soldiers} Soldiers.");
        }
        if ($guards > 0 && $this->trainingService->trainUnits($npc->id, 'guards', $guards)) {
            $this->log("  -> Trained {$guards} Guards.");
        }
        if ($spies > 0 && $this->trainingService->trainUnits($npc->id, 'spies', $spies)) {
            $this->log("  -> Trained {$spies} Spies.");
        }
    }

    private function manageArmory(User $npc): void
    {
        $this->log("  -> Armory Check...");
        
        // Fetch armory data to see what's available
        $armoryData = $this->armoryService->getArmoryData($npc->id);
        $structures = $this->structureRepo->findByUserId($npc->id);
        $resources = $this->resourceRepo->findByUserId($npc->id);
        
        $config = $armoryData['armoryConfig'];
        $inventory = $armoryData['inventory'];
        $loadouts = $armoryData['loadouts'];

        $actionsTaken = 0;

        foreach ($config as $unitKey => $unitData) {
            foreach ($unitData['categories'] as $catKey => $catData) {
                // Check equipped item
                $equipped = $loadouts[$unitKey][$catKey] ?? null;
                
                // Identify best unlocked item for this slot
                $bestItemKey = null;
                $bestItemCost = 0;
                $bestItemName = '';

                foreach ($catData['items'] as $itemKey => $itemInfo) {
                    $reqLevel = $itemInfo['armory_level_req'] ?? 0;
                    if ($structures->armory_level >= $reqLevel) {
                        $bestItemKey = $itemKey; 
                        $bestItemCost = $itemInfo['cost'];
                        $bestItemName = $itemInfo['name'];
                    }
                }

                if ($bestItemKey && $bestItemKey !== $equipped) {
                    // Check inventory
                    $owned = $inventory[$bestItemKey] ?? 0;
                    $needed = 1000; // Target batch size

                    // Buy if needed
                    if ($owned < $needed && $resources->credits > ($bestItemCost * $needed)) {
                        if ($this->armoryService->manufactureItem($npc->id, $bestItemKey, $needed)) {
                            $this->log("  -> Manufactured {$needed}x {$bestItemName}.");
                            $actionsTaken++;
                            // Update local credit count roughly
                            $resources = $this->resourceRepo->findByUserId($npc->id);
                        }
                    }

                    // Equip if available
                    // Refresh inventory count (in case we just bought it)
                    $newOwned = ($inventory[$bestItemKey] ?? 0) + ($actionsTaken > 0 ? $needed : 0);
                    
                    if ($newOwned > 0) {
                        if ($this->armoryService->equipItem($npc->id, $unitKey, $catKey, $bestItemKey)) {
                            $this->log("  -> Equipped {$bestItemName} to {$unitKey} ({$catKey}).");
                            $actionsTaken++;
                        }
                    }
                }
            }
        }

        if ($actionsTaken === 0) {
            $this->log("  -> Armory: No actions taken (Equipped optimally or broke).");
        }
    }

    private function manageAlliance(User $npc): void
    {
        if (!$npc->alliance_id) return;

        // Check if this user is the leader
        $alliance = $this->allianceRepo->findById($npc->alliance_id);
        if ($alliance && $alliance->leader_id === $npc->id) {
            
            $this->log("  -> Alliance Leader Action Check (Bank: " . number_format($alliance->bank_credits) . ")");

            // If bank is healthy (> 100M), upgrade random structure
            if ($alliance->bank_credits > 100000000) {
                $defs = ['citadel_shield', 'command_nexus', 'galactic_research_hub', 'orbital_training_grounds'];
                $target = $defs[array_rand($defs)];
                
                if ($this->allianceStructureService->purchaseOrUpgradeStructure($npc->id, $target)) {
                    $this->log("  -> ALLIANCE UPGRADE: Purchased/Upgraded {$target}.");
                } else {
                    $this->log("  -> Alliance Upgrade Failed ({$target}).");
                }
            }
        }
    }

    private function manageAggression(User $npc): void
    {
        $roll = mt_rand(1, 100);
        $this->log("  -> Aggression Roll: {$roll} (Needs > 30)");

        if ($roll <= 30) {
            return;
        }

        $stats = $this->statsRepo->findByUserId($npc->id);
        if ($stats->attack_turns < 1) {
            $this->log("  -> SKIPPING Attack: No turns.");
            return;
        }

        // Find a target
        $totalTargets = $this->statsRepo->getTotalTargetCount($npc->id);
        if ($totalTargets === 0) return;

        $perPage = 25;
        $totalPages = (int)ceil($totalTargets / $perPage);
        $randomPage = mt_rand(1, max(1, $totalPages));
        $offset = ($randomPage - 1) * $perPage;

        $targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $npc->id);
        
        if (empty($targets)) {
            $this->log("  -> Attack: No targets found.");
            return;
        }

        // Pick random target
        $victim = $targets[array_rand($targets)];
        
        // Check if friendly
        $victimUser = $this->userRepo->findById($victim['id']);
        if ($victimUser && $victimUser->alliance_id === $npc->alliance_id) {
            $this->log("  -> Attack Aborted: Target {$victim['character_name']} is an ally.");
            return;
        }

        // Attack!
        $this->log("  -> ATTACKING: {$victim['character_name']} (ID: {$victim['id']})");
        
        // conductAttack returns true/false and sets session flash
        // Since we are in CLI, we don't see flash, but we can assume it worked if true
        if ($this->attackService->conductAttack($npc->id, $victim['character_name'], 'plunder')) {
            $this->log("  -> Attack COMPLETE.");
        } else {
            $this->log("  -> Attack FAILED (Logic error or low resources).");
        }
    }
}