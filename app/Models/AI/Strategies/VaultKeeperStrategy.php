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
        $actions = [
            "Strategy: VAULT KEEPER (Turtle/Banker)",
            "Active State: " . strtoupper($state),
            "Current Assets: " . number_format($resources->credits) . " Credits | " . number_format($resources->naquadah_crystals) . " Crystals"
        ];
        
        // Always deposit excess credits (BankService logic)
        // Note: BankService is not yet injected, assuming future implementation or direct repo call
        // $this->bankService->deposit(...)

        // Prioritize Defensive Structures
        $this->attemptUpgrade($npc->id, 'planetary_shield', $resources, $actions);

        // Train Guards & Sentries
        $actions[] = "Evaluating Training: Guards & Sentries...";
        $respG = $this->trainingService->trainUnits($npc->id, 'guards', 20);
        if ($respG->isSuccess()) {
            $actions[] = "SUCCESS: Trained Guards";
        } elseif (str_contains($respG->message, 'untrained citizens')) {
            $this->considerCitizenPurchase($npc->id, $resources, $actions);
        }

        $respS = $this->trainingService->trainUnits($npc->id, 'sentries', 10);
        if ($respS->isSuccess()) {
            $actions[] = "SUCCESS: Trained Sentries";
        } elseif (str_contains($respS->message, 'untrained citizens')) {
            $this->considerCitizenPurchase($npc->id, $resources, $actions);
        }
        
        // Occasional Tech Upgrade
        $this->attemptUpgrade($npc->id, 'quantum_research_lab', $resources, $actions);

        $this->considerCrystalPurchase($npc->id, $resources->credits, $resources->naquadah_crystals, $actions);

        return $actions;
    }
}
