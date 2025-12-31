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
        $state = $this->determineState($resources, $stats, $structures);
        
        // Calculate Income
        $income = $this->powerCalcService->calculateIncomePerTurn($npc->id, $resources, $stats, $structures, $npc->alliance_id);
        
        $actions = [
            "Strategy: INDUSTRIALIST (Eco-Boomer)",
            "Active State: " . strtoupper($state),
            "Current Assets: " . number_format($resources->credits) . " Cr | " . number_format($resources->naquadah_crystals) . " Nq | " . number_format($resources->dark_matter) . " DM",
            "Unit Composition: Sol: {$resources->soldiers} | Grd: {$resources->guards} | Spy: {$resources->spies} | Sen: {$resources->sentries} | Wrk: {$resources->workers}",
            "Income/Turn: " . number_format($income['total_credit_income']) . " Cr | " . number_format($income['naquadah_income']) . " Nq | " . number_format($income['dark_matter_income']) . " DM | " . number_format($income['total_citizens']) . " Pop"
        ];

        switch ($state) {
            case self::STATE_GROWTH:
                // Prioritize: 1. Population (Worker Capacity), 2. Mining Complex (Income), 3. Workers
                $this->attemptUpgrade($npc->id, 'population', $resources, $actions);
                $this->attemptUpgrade($npc->id, 'naquadah_mining_complex', $resources, $actions);

                // Spend remaining cash on workers
                $actions[] = "Evaluating Training: Workers...";
                $workerCost = $this->config->get('game_balance.training.workers.credits', 500);
                $affordable = floor($resources->credits * 0.5 / $workerCost);
                if ($affordable > 0) {
                    $response = $this->trainingService->trainUnits($npc->id, 'workers', (int)$affordable);
                    if ($response->isSuccess()) {
                        $actions[] = "SUCCESS: Trained " . (int)$affordable . " Workers";
                    } else {
                        $actions[] = "FAILURE: " . $response->message;
                        if (str_contains($response->message, 'untrained citizens')) {
                            $this->considerCitizenPurchase($npc->id, $resources, $actions);
                        }
                    }
                } else {
                    $actions[] = "SKIP: Cannot afford any workers.";
                }
                break;

            case self::STATE_DEFENSIVE:
                $actions[] = "Priority: Rebuilding Defense...";
                $response = $this->trainingService->trainUnits($npc->id, 'guards', 50);
                if ($response->isSuccess()) {
                    $actions[] = "SUCCESS: Trained Guards";
                } else {
                    $actions[] = "FAILURE: " . $response->message;
                    if (str_contains($response->message, 'untrained citizens')) {
                        $this->considerCitizenPurchase($npc->id, $resources, $actions);
                    }
                }
                break;
            
            case self::STATE_PREPARE:
                $this->attemptUpgrade($npc->id, 'command_nexus', $resources, $actions);
                break;
        }
        
        $this->considerCrystalPurchase($npc->id, $resources->credits, $resources->naquadah_crystals, $actions);

        return $actions;
    }
}
