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
        $state = $this->determineState($resources, $stats, $structures);
        
        $income = $this->powerCalcService->calculateIncomePerTurn($npc->id, $resources, $stats, $structures, $npc->alliance_id);

        $actions = [
            "Strategy: REAVER (Rush Aggro)",
            "Active State: " . strtoupper($state),
            "Current Assets: " . number_format($resources->credits) . " Cr | " . number_format($resources->naquadah_crystals) . " Nq | " . number_format($resources->dark_matter) . " DM",
            "Unit Composition: Sol: {$resources->soldiers} | Grd: {$resources->guards} | Spy: {$resources->spies} | Sen: {$resources->sentries} | Wrk: {$resources->workers}",
            "Income/Turn: " . number_format($income['total_credit_income']) . " Cr | " . number_format($income['naquadah_income']) . " Nq | " . number_format($income['dark_matter_income']) . " DM | " . number_format($income['total_citizens']) . " Pop"
        ];

        // Infrastructure Baseline Check
        $this->ensureResourceProduction($npc->id, $resources, $structures, $actions);

        switch ($state) {
            case self::STATE_AGGRESSIVE:
                // Train Soldiers with all available credits
                $actions[] = "Evaluating Training: Soldiers...";
                $soldierCost = $this->config->get('game_balance.training.soldiers.credits', 1000);
                $maxSoldiers = floor($resources->credits / $soldierCost);
                $amount = min($maxSoldiers, 1000); 
                
                if ($amount > 0) {
                    $response = $this->trainingService->trainUnits($npc->id, 'soldiers', (int)$amount);
                    if ($response->isSuccess()) {
                        $actions[] = "SUCCESS: Trained " . (int)$amount . " Soldiers";
                    } else {
                        $actions[] = "FAILURE: " . $response->message;
                        if (str_contains($response->message, 'untrained citizens')) {
                            $this->considerCitizenPurchase($npc->id, $resources, $actions);
                        }
                    }
                } else {
                    $actions[] = "SKIP: Cannot afford soldiers.";
                }
                
                // Attack Logic
                $actions[] = "Selecting Target...";
                $targets = $this->statsRepo->findTargetsForNpc($npc->id);
                if (!empty($targets)) {
                    $target = $targets[array_rand($targets)];
                    $actions[] = "Engaging: {$target['character_name']}...";
                    $response = $this->attackService->conductAttack($npc->id, $target['character_name'], 'plunder');
                    $actions[] = "RESULT: " . $response->message;
                } else {
                    $actions[] = "SKIP: No suitable targets in range.";
                }
                break;

            case self::STATE_RECOVERY:
            case self::STATE_GROWTH:
                $this->attemptUpgrade($npc->id, 'naquadah_mining_complex', $resources, $actions);
                break;
        }

        $this->considerCrystalPurchase($npc->id, $resources->credits, $resources->naquadah_crystals, $actions);

        return $actions;
    }
}
