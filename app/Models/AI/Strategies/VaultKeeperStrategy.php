<?php

namespace App\Models\AI\Strategies;

use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;

/**
 * The Vault Keeper (Turtle/Banker)
 * Strategy: Max Defense. Hoards gold.
 */
class VaultKeeperStrategy extends BaseNpcStrategy
{
    public function determineState(UserResource $resources, UserStats $stats, UserStructure $structures): string
    {
        // If Bank is not max level for the current Nexus, prioritize Tech/Buildings
        // Otherwise, Defend.
        return self::STATE_DEFENSIVE;
    }

    public function execute(User $npc, UserResource $resources, UserStats $stats, UserStructure $structures): array
    {
        $actions = [];
        
        // Always deposit excess credits (BankService logic)
        // Placeholder: $this->bankService->deposit(...)

        // Prioritize Defensive Structures
        if ($this->attemptUpgrade($npc->id, 'shield_generator', $resources)) {
            $actions[] = "Upgraded Shield Generator";
        }

        // Train Guards & Sentries
        // Placeholder: $this->trainingService->trainUnit(...)
        
        // Occasional Tech Upgrade
        $this->attemptUpgrade($npc->id, 'research_lab', $resources);

        return $actions;
    }
}
