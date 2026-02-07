<?php

namespace App\Models\Services;

use App\Models\AI\Strategies\NpcStrategyInterface;
use App\Models\AI\Strategies\IndustrialistStrategy;
use App\Models\AI\Strategies\ReaverStrategy;
use App\Models\AI\Strategies\VaultKeeperStrategy;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\StructureService;
use App\Models\Services\TrainingService;
use App\Models\Services\ArmoryService;
// use App\Models\Services\BlackMarketService;
use App\Models\Services\CurrencyConverterService;
use App\Core\Config;
use Exception;

/**
 * The Brain of the NPC AI.
 * Assigns strategies and orchestrates the turn execution.
 */
class NpcDirectorService
{
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;

    // Strategies
    private IndustrialistStrategy $industrialistStrategy;
    private ReaverStrategy $reaverStrategy;
    private VaultKeeperStrategy $vaultKeeperStrategy;

    public function __construct(
        UserRepository $userRepo,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo,
        // Injected Strategies
        IndustrialistStrategy $industrialistStrategy,
        ReaverStrategy $reaverStrategy,
        VaultKeeperStrategy $vaultKeeperStrategy
    ) {
        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        
        $this->industrialistStrategy = $industrialistStrategy;
        $this->reaverStrategy = $reaverStrategy;
        $this->vaultKeeperStrategy = $vaultKeeperStrategy;
    }

    /**
     * Main entry point called by Cron.
     * Iterates through all NPCs and executes their AI.
     */
    public function processAllNpcs(): array
    {
        $npcs = $this->userRepo->findNpcs();
        $results = [];

        foreach ($npcs as $npc) {
            try {
                $strategy = $this->assignStrategy($npc);
                $actions = $this->executeNpcTurn($npc, $strategy);
                $results[$npc->characterName] = $actions;
            } catch (Exception $e) {
                $results[$npc->characterName] = ['Error: ' . $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Maps an NPC to a specific strategy based on ID or DB flag.
     * (Currently using ID modulo logic for simplicity, can be DB driven later)
     */
    private function assignStrategy(User $npc): NpcStrategyInterface
    {
        // Simple Logic: 
        // ID % 3 == 0 -> Industrialist
        // ID % 3 == 1 -> Reaver
        // ID % 3 == 2 -> VaultKeeper
        
        $mode = $npc->id % 3;
        
        return match ($mode) {
            0 => $this->industrialistStrategy,
            1 => $this->reaverStrategy,
            2 => $this->vaultKeeperStrategy,
            default => $this->industrialistStrategy
        };
    }

    /**
     * Loads NPC data and executes the strategy.
     */
    private function executeNpcTurn(User $npc, NpcStrategyInterface $strategy): array
    {
        $resources = $this->resourceRepo->findByUserId($npc->id);
        $stats = $this->statsRepo->findByUserId($npc->id);
        $structures = $this->structureRepo->findByUserId($npc->id);

        if (!$resources || !$stats || !$structures) {
            return ['Skipped: Missing Data'];
        }

        return $strategy->execute($npc, $resources, $stats, $structures);
    }
}
