<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StructureDefinitionRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\ArmoryRepository;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\UserRepository;

/**
 * Calculates a user's total Net Worth based on Assets and Income Potential.
 * Formula: Income Score (24h projection) + Asset Value (Liquid + Fixed).
 */
class NetWorthCalculatorService
{
    private Config $config;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;
    private StatsRepository $statsRepo;
    private ArmoryRepository $armoryRepo;
    private PowerCalculatorService $powerService;
    private UserRepository $userRepo;

    // Valuation Weights for Resources
    private const WEIGHT_CITIZEN = 10;
    private const HOURS_PROJECTION = 24;

    public function __construct(
        Config $config,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo,
        StatsRepository $statsRepo,
        ArmoryRepository $armoryRepo,
        PowerCalculatorService $powerService,
        UserRepository $userRepo
    ) {
        $this->config = $config;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        $this->armoryRepo = $armoryRepo;
        $this->powerService = $powerService;
        $this->userRepo = $userRepo;
    }

    /**
     * Calculates and returns the total net worth for a user.
     * 
     * @param int $userId
     * @return float
     */
    public function calculateTotalNetWorth(int $userId): float
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) return 0;

        $resources = $this->resourceRepo->findByUserId($userId);
        $structures = $this->structureRepo->findByUserId($userId);
        $stats = $this->statsRepo->findByUserId($userId);

        if (!$resources || !$structures || !$stats) return 0;

        // 1. Asset Value (Liquid)
        // Credits on Hand + Banked Credits
        $liquidValue = $resources->credits + $resources->banked_credits;

        // 2. Asset Value (Fixed - Structures)
        $structureValue = $this->calculateStructureValue($structures);

        // 3. Asset Value (Fixed - Units)
        $unitValue = $this->calculateUnitValue($resources, $userId);

        // 4. Asset Value (Fixed - Armory)
        $armoryValue = $this->calculateArmoryValue($userId);

        // 5. Income Score (Projected 24h Income)
        $incomeScore = $this->calculateIncomeScore($userId, $resources, $stats, $structures, $user->alliance_id);

        return ($liquidValue + $structureValue + $unitValue + $armoryValue + $incomeScore);
    }

    /**
     * Calculates the total value of all owned structures.
     * Uses geometric series sum to account for all levels.
     */
    private function calculateStructureValue($structures): int
    {
        $totalValue = 0;
        $definitions = $this->config->get('game_balance.structures');
        
        foreach ($definitions as $key => $def) {
            // Check if user has this structure (property exists and level > 0)
            if (!property_exists($structures, $key)) continue;
            
            $level = $structures->$key;
            if ($level <= 0) continue;

            $baseCost = $def['base_cost'];
            $multiplier = $def['multiplier'];

            // Geometric Series Sum: S_n = a * (1 - r^n) / (1 - r)
            if ($multiplier == 1) {
                $value = $baseCost * $level;
            } else {
                $value = $baseCost * ( (pow($multiplier, $level) - 1) / ($multiplier - 1) );
            }
            
            $totalValue += (int)$value;
        }

        return $totalValue;
    }

    /**
     * Calculates the total value of all owned units.
     */
    private function calculateUnitValue($resources, int $userId): int
    {
        $trainingConfig = $this->config->get('game_balance.training');
        $totalValue = 0;

        // Basic Units
        $totalValue += $resources->workers * ($trainingConfig['workers']['credits'] ?? 0);
        $totalValue += $resources->soldiers * ($trainingConfig['soldiers']['credits'] ?? 0);
        $totalValue += $resources->guards * ($trainingConfig['guards']['credits'] ?? 0);
        $totalValue += $resources->spies * ($trainingConfig['spies']['credits'] ?? 0);
        $totalValue += $resources->sentries * ($trainingConfig['sentries']['credits'] ?? 0);



        return $totalValue;
    }

    /**
     * Calculates the total value of owned Armory items (Loadouts).
     */
    private function calculateArmoryValue(int $userId): int
    {
        $inventory = $this->armoryRepo->getInventory($userId);
        if (empty($inventory)) return 0;

        $armoryConfig = $this->config->get('armory_items');
        // The config is structured as [ 'soldiers' => [ 'categories' => [ 'weapons' => [ 'items' => [ 'pulse_rifle' => [ 'cost' => 100 ] ] ] ] ] ]
        // We need a flat map of item_key => cost to be efficient.
        // Let's build it once. In a real app, this should be cached or pre-processed.
        
        $itemCosts = [];
        foreach ($armoryConfig as $unitData) {
            foreach ($unitData['categories'] ?? [] as $catData) {
                foreach ($catData['items'] ?? [] as $itemKey => $itemData) {
                    // Only count Credit cost for now as per "Item Cost" spec?
                    // Proposal said "Total Credit Cost".
                    $cost = $itemData['cost'] ?? 0;
                    
                    $itemCosts[$itemKey] = $cost;
                }
            }
        }

        $totalValue = 0;
        foreach ($inventory as $itemKey => $quantity) {
            if (isset($itemCosts[$itemKey])) {
                $totalValue += $itemCosts[$itemKey] * $quantity;
            }
        }

        return $totalValue;
    }

    /**
     * Calculates a score based on projected daily income.
     */
    private function calculateIncomeScore(int $userId, $resources, $stats, $structures, ?int $allianceId): float
    {
        $income = $this->powerService->calculateIncomePerTurn($userId, $resources, $stats, $structures, $allianceId);
        
        $score = 0;
        $score += $income['total_credit_income'];
        $score += $income['total_citizens'] * self::WEIGHT_CITIZEN;
        
        // Project to 24 hours (96 turns)
        // Config 'turn_duration' is usually 15 mins -> 4 per hour -> 96 per day.
        // But the user proposal said "x24 hourly rate".
        // Income is per turn. Turns per hour = 4.
        // So Per Turn * 4 * 24 = Per Turn * 96.
        
        return $score * 96;
    }
}
