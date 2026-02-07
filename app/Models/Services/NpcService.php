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
use App\Models\Services\CurrencyConverterService;
use App\Models\Services\SpyService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStructure;
use App\Models\Entities\UserStats;
use App\Core\Logger;
use DI\Attribute\Inject;
use PDO;
use Throwable;

/**
 * Orchestrates the autonomous behavior of NPC agents ("The Void Syndicate").
 * Intended to be run via Cron.
 * * Refactored Phase 4: Intelligence-Led Aggression & Smart Economy.
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
    private SpyService $spyService;
    private PowerCalculatorService $powerCalcService;

    // Logger
    private Logger $logger;

    /**
     * DI Constructor.
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
        SpyService $spyService,
        PowerCalculatorService $powerCalcService,
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
        $this->spyService = $spyService;
        $this->powerCalcService = $powerCalcService;
        
        $this->logger = $logger;
    }

    public function runNpcCycle(): void
    {
        $this->logger->info("--- STARTING NPC AGENT CYCLE (Phase 4 Logic) ---");
        
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
        $profile = $this->getPersonalityProfile($npc->id);
        $this->logger->info("Processing Agent: {$npc->characterName} (ID: {$npc->id}) [Type: {$profile['type']}]");

        $this->manageEconomy($npc, $profile);
        $this->manageTraining($npc, $profile);
        $this->manageArmory($npc);
        $this->manageAlliance($npc);
        $this->manageAggression($npc, $profile);
        
        $this->logger->info("Agent {$npc->characterName} finished.");
        $this->logger->info("----------------------------------------");
    }

    private function getPersonalityProfile(int $npcId): array
    {
        $mode = $npcId % 3;
        
        return match ($mode) {
            0 => [ // The Warlord
                'type' => 'Warlord',
                'priorities' => ['armory' => 50, 'economy_upgrade' => 5],
                'training_ratios' => ['workers' => 0.20, 'soldiers' => 0.60, 'guards' => 0.10, 'spies' => 0.10],
                'aggression_chance' => 70,
            ],
            1 => [ // The Banker (Industrialist)
                'type' => 'Banker',
                'priorities' => ['economy_upgrade' => 60, 'population' => 20],
                'training_ratios' => ['workers' => 0.70, 'soldiers' => 0.10, 'guards' => 0.20, 'spies' => 0.0],
                'aggression_chance' => 5,
            ],
            2 => [ // The Turtle (Sentinel)
                'type' => 'Turtle',
                'priorities' => ['planetary_shield' => 80],
                'training_ratios' => ['workers' => 0.30, 'soldiers' => 0.0, 'guards' => 0.50, 'spies' => 0.10, 'sentries' => 0.10],
                'aggression_chance' => 0,
            ],
            default => [ // Balanced Fallback
                'type' => 'Balanced',
                'priorities' => ['economy_upgrade' => 50, 'population' => 50],
                'training_ratios' => ['workers' => 0.33, 'soldiers' => 0.33, 'guards' => 0.33],
                'aggression_chance' => 30,
            ]
        };
    }

    private function manageEconomy(User $npc, array $profile): void
    {
        $structureData = $this->structureService->getStructureData($npc->id);
        $structures = $structureData['structures'];
        $resources = $structureData['resources'];
        $costs = $structureData['costs'];

        // 1. Desperation Check: Stalling training due to low pop?
        if ($resources->untrained_citizens < 50 && $structures->population_level < 50) {
            $target = 'population';
        } else {
            // 2. Select Target based on Personality Weights
            $target = $this->selectWeightedTarget($profile['priorities']);
        }

        // 3. Fallback if target invalid or maxed (not handled deeply here, assuming standard upgrades)
        if (!isset($costs[$target])) {
            $this->logger->info("  -> Economy: Invalid target {$target}, skipping.");
            return;
        }

        $costData = $costs[$target];
        $creditCost = $costData['credits'];

        // 4. Affordability & Liquidity Check
        $hasCredits = $resources->credits >= $creditCost;

        if ($hasCredits) {
            $res = $this->structureService->upgradeStructure($npc->id, $target);
            if ($res->isSuccess()) {
                $this->logger->info("  -> UPGRADE SUCCESS: {$target}.");
            }
        } else {
            // "Poverty Trap" Fix: Do NOTHING. Save money.
            $this->logger->info("  -> SAVING: Not enough resources for {$target} (Cost: {$creditCost} Cr).");
        }
    }

    private function manageTraining(User $npc, array $profile): void
    {
        $resources = $this->resourceRepo->findByUserId($npc->id);
        if (!$resources || $resources->untrained_citizens < 10) return;

        $toTrain = min($resources->untrained_citizens, 1000);
        $ratios = $profile['training_ratios'];

        foreach ($ratios as $unit => $ratio) {
            if ($ratio <= 0) continue;
            
            $amount = (int)floor($toTrain * $ratio);
            if ($amount > 0) {
                // Determine actual key ('sentries' supported by service? Yes.)
                $res = $this->trainingService->trainUnits($npc->id, $unit, $amount);
                if ($res->isSuccess()) {
                    $this->logger->info("  -> Trained {$amount} {$unit}.");
                }
            }
        }
    }

    private function manageArmory(User $npc): void
    {
        // Existing logic is decent, keeping it simple but functional
        $armoryData = $this->armoryService->getArmoryData($npc->id);
        $structures = $this->structureRepo->findByUserId($npc->id);
        $resources = $this->resourceRepo->findByUserId($npc->id);
        
        $config = $armoryData['armoryConfig'];
        $inventory = $armoryData['inventory'];
        $loadouts = $armoryData['loadouts'];
        
        $currentCredits = $resources->credits;

        foreach ($config as $unitKey => $unitData) {
            foreach ($unitData['categories'] as $catKey => $catData) {
                $equipped = $loadouts[$unitKey][$catKey] ?? null;
                $bestItemKey = null;
                $bestItemCost = 0;
                $bestItemName = '';

                // Find best unlocked item
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
                    $idealStock = 500; // Cap stock to avoid wasting all money on one slot

                    if ($owned < $idealStock) {
                        $needed = $idealStock - $owned;
                        $affordable = (int)floor($currentCredits / max(1, $bestItemCost));
                        $buyAmount = min($needed, $affordable);

                        if ($buyAmount > 5) {
                            $res = $this->armoryService->manufactureItem($npc->id, $bestItemKey, $buyAmount);
                            if ($res->isSuccess()) {
                                $currentCredits -= ($buyAmount * $bestItemCost);
                            }
                        }
                    }
                    
                    // Always try to equip if we have some
                    if (($inventory[$bestItemKey] ?? 0) > 0) {
                        $this->armoryService->equipItem($npc->id, $unitKey, $catKey, $bestItemKey);
                    }
                }
            }
        }
    }

    private function manageAlliance(User $npc): void
    {
        if (!$npc->alliance_id) return;
        // Simple Leader Logic: Upgrade Alliance Defenses if rich
        $alliance = $this->allianceRepo->findById($npc->alliance_id);
        if ($alliance && $alliance->leader_id === $npc->id && $alliance->bank_credits > 100_000_000) {
            $defs = ['citadel_shield', 'command_nexus', 'galactic_research_hub'];
            $target = $defs[array_rand($defs)];
            $this->allianceStructureService->purchaseOrUpgradeStructure($npc->id, $target);
        }
    }

    private function manageAggression(User $npc, array $profile): void
    {
        // 1. Check Aggression Roll
        if (mt_rand(1, 100) > $profile['aggression_chance']) return;

        // 2. Check Turns
        $stats = $this->statsRepo->findByUserId($npc->id);
        if ($stats->attack_turns < 10) return; // Save turns for a real fight

        // 3. Find Target
        $totalTargets = $this->statsRepo->getTotalTargetCount($npc->id);
        if ($totalTargets === 0) return;

        $perPage = 25;
        $randomPage = mt_rand(1, max(1, (int)ceil($totalTargets / $perPage)));
        $offset = ($randomPage - 1) * $perPage;
        $targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $npc->id);
        if (empty($targets)) return;

        $victimData = $targets[array_rand($targets)];
        $victimName = $victimData['character_name'];

        // 4. Intelligence Phase
        $this->logger->info("  -> AGGRESSION: Scouting {$victimName}...");
        
        $spyRes = $this->spyService->conductOperation($npc->id, $victimName);
        
        // If spy failed completely (jammed or error), abort
        if (!$spyRes->isSuccess()) {
            $this->logger->info("  -> SPY FAILED: {$spyRes->message}");
            return;
        }

        // 5. Analysis Phase
        $reportData = $spyRes->data; // ['report_id' => X, 'result' => 'success'|'failure']
        if (!isset($reportData['report_id'])) {
            // Should not happen if SpyService is updated, but safety first
            $this->logger->info("  -> SPY ERROR: No report ID returned.");
            return;
        }

        $report = $this->spyService->getSpyReport($reportData['report_id'], $npc->id);
        if (!$report) return;

        // If we didn't get a successful intel gather, we can't estimate power accurately.
        // Warlords might attack anyway if blind, but let's play smart.
        if ($report->operation_result !== 'success') {
            $this->logger->info("  -> SCOUTING FAILED: No intel gathered. Aborting attack.");
            return;
        }

        // 6. Power Comparison
        $myResources = $this->resourceRepo->findByUserId($npc->id);
        $myStructures = $this->structureRepo->findByUserId($npc->id);
        $myStats = $this->statsRepo->findByUserId($npc->id);
        
        $myOffense = $this->powerCalcService->calculateOffensePower($npc->id, $myResources, $myStats, $myStructures)['total'];

        // Construct Enemy State from Intel
        // We use 0 for unknown values to be conservative (or risk-takers?)
        // Using 0 might underestimate them. Let's use seen values.
        $enemyRes = new UserResource(
            user_id: 0, 
            credits: 0, bank_credits: 0, 
            gemstones: 0, untrained_citizens: 0, 
            workers: 0, 
            soldiers: $report->soldiers_seen ?? 0, 
            guards: $report->guards_seen ?? 0, 
            spies: $report->spies_seen ?? 0, 
            sentries: $report->sentries_seen ?? 0,
            research_data: 0,
            protoform: 0
        );

        $enemyStruct = new UserStructure(
            user_id: 0,
            economy_upgrade_level: 0,
            population_level: 0,
            armory_level: $report->armory_level_seen ?? 0
        );
        
        $enemyStats = new UserStats(
            user_id: 0,
            level: 1,
            experience: 0,
            net_worth: 0,
            war_prestige: 0,
            energy: 0,
            attack_turns: 0,
            level_up_points: 0,
            strength_points: 0,
            constitution_points: 0,
            wealth_points: 0,
            dexterity_points: 0,
            charisma_points: 0,
            deposit_charges: 0,
            last_deposit_at: null,
            battles_won: 0,
            battles_lost: 0,
            spy_successes: 0,
            spy_failures: 0
        );

        $enemyDefense = $this->powerCalcService->calculateDefensePower(0, $enemyRes, $enemyStats, $enemyStruct)['total'];

        // Decision: Need 20% advantage to attack
        $ratio = ($enemyDefense > 0) ? ($myOffense / $enemyDefense) : 999;
        
        if ($ratio > 1.2) {
            $this->logger->info("  -> ATTACKING: Power Ratio " . number_format($ratio, 2) . ". Launching assault!");
            $attRes = $this->attackService->conductAttack($npc->id, $victimName, 'plunder');
            if ($attRes->isSuccess()) {
                $this->logger->info("  -> ATTACK RESULT: " . $attRes->message);
            }
        } else {
            $this->logger->info("  -> HOLDING FIRE: Power Ratio " . number_format($ratio, 2) . " insufficient.");
        }
    }

    private function selectWeightedTarget(array $priorities): string
    {
        $totalWeight = array_sum($priorities);
        if ($totalWeight <= 0) return array_key_first($priorities);

        $rand = mt_rand(1, $totalWeight);
        $current = 0;
        
        foreach ($priorities as $key => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $key;
            }
        }
        return array_key_first($priorities);
    }
}
