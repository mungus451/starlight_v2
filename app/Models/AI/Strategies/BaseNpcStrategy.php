<?php

namespace App\Models\AI\Strategies;

use App\Models\Entities\User;
use App\Models\Entities\UserResource;
use App\Models\Entities\UserStats;
use App\Models\Entities\UserStructure;
use App\Models\Services\StructureService;
use App\Models\Services\TrainingService;
use App\Models\Services\ArmoryService;
use App\Models\Services\BlackMarketService;
use App\Models\Services\CurrencyConverterService;
use App\Models\Services\AttackService; // --- NEW ---
use App\Models\Services\PowerCalculatorService; // --- NEW ---
use App\Models\Repositories\StatsRepository; // --- NEW ---
use App\Core\Config;

/**
 * Base abstract class containing common logic for all NPC types.
 * (e.g., helper methods for buying, calculating ROI, checking costs)
 */
abstract class BaseNpcStrategy implements NpcStrategyInterface
{
    protected StructureService $structureService;
    protected TrainingService $trainingService;
    protected ArmoryService $armoryService;
    protected BlackMarketService $blackMarketService;
    protected CurrencyConverterService $converterService;
    protected AttackService $attackService; // --- NEW ---
    protected StatsRepository $statsRepo;  // --- NEW ---
    protected PowerCalculatorService $powerCalcService; // --- NEW ---
    protected Config $config;

    // Common States
    const STATE_GROWTH = 'growth';       // Focus on Workers/Mines
    const STATE_PREPARE = 'prepare';     // Focus on Tech/Armory
    const STATE_AGGRESSIVE = 'attack';   // Active Hunting
    const STATE_DEFENSIVE = 'defend';    // Rebuilding Guards/Shields

    public function __construct(
        StructureService $structureService,
        TrainingService $trainingService,
        ArmoryService $armoryService,
        BlackMarketService $blackMarketService,
        CurrencyConverterService $converterService,
        AttackService $attackService, // --- NEW ---
        StatsRepository $statsRepo,   // --- NEW ---
        PowerCalculatorService $powerCalcService, // --- NEW ---
        Config $config
    ) {
        $this->structureService = $structureService;
        $this->trainingService = $trainingService;
        $this->armoryService = $armoryService;
        $this->blackMarketService = $blackMarketService;
        $this->converterService = $converterService;
        $this->attackService = $attackService; // --- NEW ---
        $this->statsRepo = $statsRepo;         // --- NEW ---
        $this->powerCalcService = $powerCalcService; // --- NEW ---
        $this->config = $config;
    }

    /**
     * Checks if resource production is sufficient. If not, attempts to upgrade infrastructure.
     * Returns true if an upgrade was attempted/performed.
     */
    protected function ensureResourceProduction(int $userId, UserResource $res, UserStructure $structures, array &$actions): bool
    {
        // 1. Dark Matter Check
        // If DM income is effectively zero (level < 1) or very low compared to needs
        if ($structures->dark_matter_siphon_level < 5) {
            $actions[] = "Priority: Establishing Dark Matter Production...";
            if ($this->attemptUpgrade($userId, 'dark_matter_siphon', $res, $actions)) {
                return true;
            }
        }

        // 2. Naquadah Check
        if ($structures->naquadah_mining_complex_level < 5) {
            $actions[] = "Priority: Establishing Naquadah Production...";
            if ($this->attemptUpgrade($userId, 'naquadah_mining_complex', $res, $actions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tries to buy citizens if the NPC is running low and has crystals.
     */
    protected function considerCitizenPurchase(int $userId, UserResource $res, array &$actions): void
    {
        $actions[] = "Evaluating Black Market for Citizens...";
        
        $cost = $this->config->get('black_market.costs.citizen_package', 25);
        
        if ($res->untrained_citizens < 100) {
            // 50% chance to actually go through with it
            if (mt_rand(1, 100) > 50) {
                $actions[] = "SKIP: Decided to wait for natural growth (50% chance).";
                return;
            }

            if ($res->naquadah_crystals >= $cost) {
                $response = $this->blackMarketService->purchaseCitizens($userId);
                if ($response->isSuccess()) {
                    $actions[] = "SUCCESS: Bought Smuggled Citizens from Black Market";
                } else {
                    $actions[] = "FAILURE: Black Market rejected purchase: " . $response->message;
                }
            } else {
                $actions[] = "SKIP: Cannot afford citizens (Need {$cost} Crystals, have " . floor($res->naquadah_crystals) . ")";
            }
        } else {
            $actions[] = "SKIP: Citizens sufficient (" . $res->untrained_citizens . ")";
        }
    }

    /**
     * Tries to buy crystals from the Black Market if the price is right
     */
    protected function considerCrystalPurchase(int $userId, int $currentCredits, float $currentCrystals, array &$actions): void
    {
        $actions[] = "Checking Currency Exchange...";
        
        // 20% chance to check the market (increased for visibility)
        if (mt_rand(1, 100) > 20) {
            $actions[] = "SKIP: Decided not to trade this turn.";
            return;
        }

        if ($currentCredits > 10000000) {
            $amountToConvert = $currentCredits * 0.1; 
            $response = $this->converterService->convertCreditsToCrystals($userId, $amountToConvert);
            if ($response->isSuccess()) {
                $actions[] = "SUCCESS: " . $response->message;
            } else {
                $actions[] = "FAILURE: Exchange failed: " . $response->message;
            }
        } else {
            $actions[] = "SKIP: Credit balance too low for exchange (" . number_format($currentCredits) . ")";
        }
    }

    /**
     * Helper to upgrade a structure if affordable.
     */
    protected function attemptUpgrade(int $userId, string $structureKey, UserResource $res, array &$actions): bool
    {
        $actions[] = "Attempting Upgrade: {$structureKey}...";
        
        try {
            $response = $this->structureService->upgradeStructure($userId, $structureKey);
            if ($response->isSuccess()) {
                $actions[] = "SUCCESS: " . $response->message;
                return true;
            } else {
                $actions[] = "FAILURE: " . $response->message;
                return false;
            }
        } catch (\Throwable $e) {
            $actions[] = "CRITICAL: Upgrade logic error for {$structureKey}";
            return false;
        }
    }
}
