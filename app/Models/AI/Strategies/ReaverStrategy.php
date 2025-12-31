<?php

namespace App\Models\AI\Strategies;

use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;

/**
 * The Reaver (Rush Aggro)
 * Strategy: Early aggression. Trains cheap units (Soldiers) en masse.
 */
class ReaverStrategy extends BaseNpcStrategy
{
    public function determineState(UserResource $resources, UserStats $stats, UserStructure $structures): string
    {
        // Always aggressive unless broke or broken.
        
        // If army is wiped out (< 10 soldiers), Recover
        if ($resources->soldiers < 10 && $stats->attack_turns > 0) {
            return self::STATE_RECOVERY; // Reusing Defensive logic or new state
        }

        // If we have Attack Turns, Attack
        if ($stats->attack_turns > 5) {
            return self::STATE_AGGRESSIVE;
        }

        return self::STATE_GROWTH; // Fallback to get more resources for army
    }

    // Custom state for clarity
    const STATE_RECOVERY = 'recovery';

    public function execute(User $npc, UserResource $resources, UserStats $stats, UserStructure $structures): array
    {
        $actions = [];
        $state = $this->determineState($resources, $stats, $structures);

        switch ($state) {
            case self::STATE_AGGRESSIVE:
                // Train Soldiers with all available credits
                // Placeholder: $this->trainingService->trainUnit(...)
                $actions[] = "Trained Mass Soldiers";
                
                // Attack Logic (Requires AttackService injection or delegation)
                // "Find target, Attack target"
                $actions[] = "Hunting for targets...";
                break;

            case self::STATE_RECOVERY:
            case self::STATE_GROWTH:
                // Minimal eco upgrades to fund the war machine
                $this->attemptUpgrade($npc->id, 'crystal_mine', $resources);
                break;
        }

        return $actions;
    }
}
