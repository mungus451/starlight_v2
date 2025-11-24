<?php

namespace App\Models\Services;

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
use App\Core\Logger;
use DI\Attribute\Inject;
use PDO;
use Throwable;

/**
 * Orchestrates the autonomous behavior of NPC agents ("The Void Syndicate").
 * Intended to be run via Cron.
 * * Refactored to use Injected Logger (Decoupled I/O).
 */
class NpcService
{
    private PDO $db;
    
    // Repositories
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

    // Logger
    private Logger $logger;

    /**
     * DI Constructor.
     * Note the use of #[Inject('NpcLogger')] to get the specific CLI-enabled logger.
     */
    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo,
        AllianceRepository $allianceRepo,
        StructureService $structureService,
        TrainingService $trainingService,
        ArmoryService $armoryService,
        AttackService $attackService,
        AllianceStructureService $allianceStructureService,
        #[Inject('NpcLogger')] Logger $logger
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        $this->allianceRepo = $allianceRepo;

        $this->structureService = $structureService;
        $this->trainingService = $trainingService;
        $this->armoryService = $armoryService;
        $this->attackService = $attackService;
        $this->allianceStructureService = $allianceStructureService;
        
        $this->logger = $logger;
    }

    public function runNpcCycle(): void
    {
        $this->logger->info("--- STARTING NPC AGENT CYCLE ---");
        
        $npcs = $this->userRepo->findNpcs();
        $count = count($npcs);
        $this->logger->info("Found {$count} NPC agents active.");

        if ($count === 0) {
            $this->logger->info("WARNING: No NPCs found.");
            return;
        }
        
        foreach ($npcs as $npc) {
            try {
                $this->processNpc($npc);
            } catch (Throwable $e) {
                $this->logger->error("ERROR processing {$npc->characterName}: " . $e->getMessage());
            }
        }

        $this->logger->info("--- NPC CYCLE COMPLETE ---");
    }

    private function processNpc(User $npc): void
    {
        $this->logger->info("Processing Agent: {$npc->characterName} (ID: {$npc->id})");

        $this->manageEconomy($npc);
        $this->manageTraining($npc);
        $this->manageArmory($npc);
        $this->manageAlliance($npc);
        $this->manageAggression($npc);
        
        $this->logger->info("Agent {$npc->characterName} finished.");
        $this->logger->info("----------------------------------------");
    }

    private function manageEconomy(User $npc): void
    {
        $structures = $this->structureRepo->findByUserId($npc->id);
        $resources = $this->resourceRepo->findByUserId($npc->id);
        
        if (!$structures || !$resources) return;

        $target = 'economy_upgrade';
        if ($structures->economy_upgrade_level >= 20) {
            $options = ['fortification', 'offense_upgrade', 'defense_upgrade', 'spy_upgrade', 'population', 'armory'];
            $target = $options[array_rand($options)];
        }

        $response = $this->structureService->upgradeStructure($npc->id, $target);
        if ($response->isSuccess()) {
            $this->logger->info("  -> SUCCESS: Upgraded {$target}.");
        }
    }

    private function manageTraining(User $npc): void
    {
        $resources = $this->resourceRepo->findByUserId($npc->id);
        if (!$resources) return;
        
        if ($resources->untrained_citizens < 100) return;

        $toTrain = min($resources->untrained_citizens, 500);
        
        $soldiers = (int)floor($toTrain * 0.4);
        $guards = (int)floor($toTrain * 0.4);
        $spies = (int)floor($toTrain * 0.2);

        if ($soldiers > 0) {
            $res = $this->trainingService->trainUnits($npc->id, 'soldiers', $soldiers);
            if ($res->isSuccess()) $this->logger->info("  -> Trained {$soldiers} Soldiers.");
        }
        if ($guards > 0) {
            $res = $this->trainingService->trainUnits($npc->id, 'guards', $guards);
            if ($res->isSuccess()) $this->logger->info("  -> Trained {$guards} Guards.");
        }
        if ($spies > 0) {
            $res = $this->trainingService->trainUnits($npc->id, 'spies', $spies);
            if ($res->isSuccess()) $this->logger->info("  -> Trained {$spies} Spies.");
        }
    }

    private function manageArmory(User $npc): void
    {
        $this->logger->info("  -> Armory Check...");
        
        $armoryData = $this->armoryService->getArmoryData($npc->id);
        $structures = $this->structureRepo->findByUserId($npc->id);
        $resources = $this->resourceRepo->findByUserId($npc->id);
        
        $config = $armoryData['armoryConfig'];
        $inventory = $armoryData['inventory'];
        $loadouts = $armoryData['loadouts'];

        $actionsTaken = 0;

        foreach ($config as $unitKey => $unitData) {
            foreach ($unitData['categories'] as $catKey => $catData) {
                $equipped = $loadouts[$unitKey][$catKey] ?? null;
                
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
                    $owned = $inventory[$bestItemKey] ?? 0;
                    $needed = 1000; 

                    if ($owned < $needed && $resources->credits > ($bestItemCost * $needed)) {
                        $response = $this->armoryService->manufactureItem($npc->id, $bestItemKey, $needed);
                        if ($response->isSuccess()) {
                            $this->logger->info("  -> Manufactured {$needed}x {$bestItemName}.");
                            $actionsTaken++;
                            $resources = $this->resourceRepo->findByUserId($npc->id);
                        }
                    }

                    $newOwned = ($inventory[$bestItemKey] ?? 0) + ($actionsTaken > 0 ? $needed : 0);
                    
                    if ($newOwned > 0) {
                        $response = $this->armoryService->equipItem($npc->id, $unitKey, $catKey, $bestItemKey);
                        if ($response->isSuccess()) {
                            $this->logger->info("  -> Equipped {$bestItemName} to {$unitKey} ({$catKey}).");
                            $actionsTaken++;
                        }
                    }
                }
            }
        }

        if ($actionsTaken === 0) {
            $this->logger->info("  -> Armory: No actions taken.");
        }
    }

    private function manageAlliance(User $npc): void
    {
        if (!$npc->alliance_id) return;

        $alliance = $this->allianceRepo->findById($npc->alliance_id);
        if ($alliance && $alliance->leader_id === $npc->id) {
            if ($alliance->bank_credits > 100000000) {
                $defs = ['citadel_shield', 'command_nexus', 'galactic_research_hub', 'orbital_training_grounds'];
                $target = $defs[array_rand($defs)];
                
                $response = $this->allianceStructureService->purchaseOrUpgradeStructure($npc->id, $target);
                if ($response->isSuccess()) {
                    $this->logger->info("  -> ALLIANCE UPGRADE: Purchased/Upgraded {$target}.");
                }
            }
        }
    }

    private function manageAggression(User $npc): void
    {
        $roll = mt_rand(1, 100);
        if ($roll <= 30) return;

        $stats = $this->statsRepo->findByUserId($npc->id);
        if ($stats->attack_turns < 1) return;

        $totalTargets = $this->statsRepo->getTotalTargetCount($npc->id);
        if ($totalTargets === 0) return;

        $perPage = 25;
        $totalPages = (int)ceil($totalTargets / $perPage);
        $randomPage = mt_rand(1, max(1, $totalPages));
        $offset = ($randomPage - 1) * $perPage;

        $targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $npc->id);
        
        if (empty($targets)) return;

        $victim = $targets[array_rand($targets)];
        
        $victimUser = $this->userRepo->findById($victim['id']);
        if ($victimUser && $victimUser->alliance_id === $npc->alliance_id) return;

        $this->logger->info("  -> ATTACKING: {$victim['character_name']} (ID: {$victim['id']})");
        
        $response = $this->attackService->conductAttack($npc->id, $victim['character_name'], 'plunder');
        
        if ($response->isSuccess()) {
            $this->logger->info("  -> Attack COMPLETE.");
        } else {
            $this->logger->info("  -> Attack FAILED: " . $response->message);
        }
    }
}