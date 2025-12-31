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
        Config $config
    ) {
        $this->structureService = $structureService;
        $this->trainingService = $trainingService;
        $this->armoryService = $armoryService;
        $this->blackMarketService = $blackMarketService;
        $this->converterService = $converterService;
        $this->config = $config;
    }

    /**
     * Tries to buy crystals from the Black Market if the price is right
     * or if the NPC is desperate.
     */
    protected function considerCrystalPurchase(int $userId, int $currentCredits, int $neededCrystals): void
    {
        // 5% chance to check the market to simulate "occasional" interest
        if (mt_rand(1, 100) > 5) return;

        // Simple logic: If we have tons of cash (> 100M) and need crystals, buy.
        // Or if the market price is low (requires MarketService visibility, skipping complexity for now).
        // For now, we assume standard Black Market exchange.
        
        // This is a placeholder for the requested feature.
        // Implementation depends on BlackMarketService methods (e.g., buyCrystals).
    }

    /**
     * Helper to upgrade a structure if affordable.
     */
    protected function attemptUpgrade(int $userId, string $structureKey, UserResource $res): bool
    {
        // Check cost via Config (mocking logic here, actual implementation needs StructureService::getCost)
        // For MVP, we simply try calling the service. The service handles affordability checks.
        // However, smart AI shouldn't "try and fail", it should check first.
        // We will rely on the service's return value for simplicity in this base class.
        
        try {
            $response = $this->structureService->upgradeStructure($userId, $structureKey);
            return $response->isSuccess();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
