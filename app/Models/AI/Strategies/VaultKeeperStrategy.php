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
        $state = $this->determineState($resources, $stats, $structures);
        
        $income = $this->powerCalcService->calculateIncomePerTurn($npc->id, $resources, $stats, $structures, $npc->alliance_id);

        $actions = [
            "Strategy: VAULT KEEPER (Turtle/Banker)",
            "Active State: " . strtoupper($state),
            "Current Assets: " . number_format($resources->credits) . " Cr | " . number_format($resources->naquadah_crystals) . " Nq | " . number_format($resources->dark_matter) . " DM",
            "Unit Composition: Sol: {$resources->soldiers} | Grd: {$resources->guards} | Spy: {$resources->spies} | Sen: {$resources->sentries} | Wrk: {$resources->workers}",
            "Income/Turn: " . number_format($income['total_credit_income']) . " Cr | " . number_format($income['naquadah_income']) . " Nq | " . number_format($income['dark_matter_income']) . " DM | " . number_format($income['total_citizens']) . " Pop"
        ];
        
        // Infrastructure Baseline Check
        $this->ensureResourceProduction($npc->id, $resources, $structures, $actions);

        // Always deposit excess credits (BankService logic)
        // Note: BankService is not yet injected, assuming future implementation or direct repo call
        // $this->bankService->deposit(...)

        // Prioritize Defensive Structures
        $this->attemptUpgrade($npc->id, 'planetary_shield', $resources, $actions);
        $this->attemptUpgrade($npc->id, 'armory', $resources, $actions); // Ensure armory access

        // Train Guards & Sentries
        $actions[] = "Evaluating Training: Guards & Sentries...";
        $respG = $this->trainingService->trainUnits($npc->id, 'guards', 20);
        if ($respG->isSuccess()) {
            $actions[] = "SUCCESS: Trained 20 Guards";
        } elseif (str_contains($respG->message, 'untrained citizens')) {
            $this->considerCitizenPurchase($npc->id, $resources, $actions);
        }

        $respS = $this->trainingService->trainUnits($npc->id, 'sentries', 10);
        if ($respS->isSuccess()) {
            $actions[] = "SUCCESS: Trained 10 Sentries";
        } elseif (str_contains($respS->message, 'untrained citizens')) {
            $this->considerCitizenPurchase($npc->id, $resources, $actions);
        }
        
        // Armory Logic (Defense)
        $this->manageArmory($npc->id, 'guard', 'defense', $resources->guards, $structures, $actions);
        $this->manageArmory($npc->id, 'sentry', 'defense', $resources->sentries, $structures, $actions);
        
        // Occasional Tech Upgrade
        $this->attemptUpgrade($npc->id, 'quantum_research_lab', $resources, $actions);

        $this->considerCrystalPurchase($npc->id, $resources->credits, $resources->naquadah_crystals, $actions);

        return $actions;
    }
}
