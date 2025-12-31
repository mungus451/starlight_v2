<?php

namespace App\Models\AI\Strategies;

use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;

/**
 * The Industrialist (Eco-Boomer)
 * Strategy: Aggressive geometric scaling of Workers/Mines. 
 * Ignores army until income hits a threshold.
 */
class IndustrialistStrategy extends BaseNpcStrategy
{
    public function determineState(UserResource $resources, UserStats $stats, UserStructure $structures): string
    {
        // 1. Check Income Threshold (e.g., 50M/turn logic, simplified here)
        // If workers < 5000 or Mines < 10, keep growing.
        if ($resources->workers < 5000 || $structures->naquadah_mining_complex_level < 10 || $structures->population_level < 10) {
            return self::STATE_GROWTH;
        }

        // 2. If Income is high but Defense is zero, switch to Defense briefly
        if ($resources->guards < 100) {
            return self::STATE_DEFENSIVE;
        }

        // 3. Otherwise, prepare for mid-game
        return self::STATE_PREPARE;
    }

    public function execute(User $npc, UserResource $resources, UserStats $stats, UserStructure $structures): array
    {
        $actions = [];
        $state = $this->determineState($resources, $stats, $structures);

        switch ($state) {
            case self::STATE_GROWTH:
                // Prioritize: 1. Population (Worker Capacity), 2. Mining Complex (Income), 3. Workers
                if ($this->attemptUpgrade($npc->id, 'population', $resources)) {
                    $actions[] = "Upgraded Population Structure";
                }
                
                if ($this->attemptUpgrade($npc->id, 'naquadah_mining_complex', $resources)) {
                    $actions[] = "Upgraded Naquadah Mining Complex";
                }

                // Spend remaining cash on workers
                // Logic: Calculate max affordable workers and hire 50% of them (save some cash)
                $workerCost = $this->config->get('game_balance.training.workers.credits', 500); // Fallback 500
                $affordable = floor($resources->credits * 0.5 / $workerCost);
                if ($affordable > 0) {
                    $response = $this->trainingService->trainUnits($npc->id, 'workers', (int)$affordable);
                    if ($response->isSuccess()) {
                        $actions[] = "Trained " . (int)$affordable . " Workers";
                    } else {
                        $actions[] = "Failed to train workers: " . $response->message;
                        // specific handling for citizen shortage
                        if (str_contains($response->message, 'untrained citizens')) {
                            if ($this->considerCitizenPurchase($npc->id, $resources)) {
                                $actions[] = "Bought Smuggled Citizens from Black Market";
                            }
                        }
                    }
                }
                break;

            case self::STATE_DEFENSIVE:
                // Train Guards
                $this->trainingService->trainUnits($npc->id, 'guards', 50);
                $actions[] = "Trained Guards for defense";
                break;
            
            case self::STATE_PREPARE:
                // Upgrade Command Nexus or Bank
                if ($this->attemptUpgrade($npc->id, 'command_nexus', $resources)) {
                    $actions[] = "Upgraded Command Nexus";
                }
                break;
        }
        
        // Occasional Black Market Crystal Buy
        $this->considerCrystalPurchase($npc->id, $resources->credits, 1000);

        return $actions;
    }
}
