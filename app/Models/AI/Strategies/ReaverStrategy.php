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
                $soldierCost = $this->config->get('game_balance.training.soldiers.credits', 1000);
                $maxSoldiers = floor($resources->credits / $soldierCost);
                // Cap at 1000 per turn to be safe/realistic
                $amount = min($maxSoldiers, 1000); 
                
                if ($amount > 0) {
                    $response = $this->trainingService->trainUnits($npc->id, 'soldiers', (int)$amount);
                    if ($response->isSuccess()) {
                        $actions[] = "Trained " . (int)$amount . " Soldiers";
                    } else {
                        $actions[] = "Failed to train soldiers: " . $response->message;
                        if (str_contains($response->message, 'untrained citizens')) {
                            if ($this->considerCitizenPurchase($npc->id, $resources)) {
                                $actions[] = "Bought Smuggled Citizens from Black Market";
                            }
                        }
                    }
                }
                
                // Attack Logic
                $targets = $this->statsRepo->findTargetsForNpc($npc->id);
                if (!empty($targets)) {
                    $target = $targets[array_rand($targets)];
                    $response = $this->attackService->conductAttack($npc->id, $target['character_name'], 'plunder');
                    $actions[] = "Attacked {$target['character_name']}: " . $response->message;
                } else {
                    $actions[] = "No suitable targets found.";
                }
                break;

            case self::STATE_RECOVERY:
            case self::STATE_GROWTH:
                // Minimal eco upgrades to fund the war machine
                $this->attemptUpgrade($npc->id, 'naquadah_mining_complex', $resources);
                break;
        }

        // Occasional Black Market Crystal Buy
        $this->considerCrystalPurchase($npc->id, $resources->credits, 1000);

        return $actions;
    }
}
