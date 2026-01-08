<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository; // --- NEW DEPENDENCY ---
use PDO;

/**
 * Handles all business logic for training units.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class TrainingService
{
    private Config $config;
    private ResourceRepository $resourceRepo;
    private GeneralService $generalService;
    private StructureRepository $structureRepo; // --- NEW PROPERTY ---

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param Config $config
     * @param ResourceRepository $resourceRepo
     * @param GeneralService $generalService
     * @param StructureRepository $structureRepo
     */
    public function __construct(
        Config $config, 
        ResourceRepository $resourceRepo,
        GeneralService $generalService,
        StructureRepository $structureRepo // --- NEW INJECTION ---
    ) {
        $this->config = $config;
        $this->resourceRepo = $resourceRepo;
        $this->generalService = $generalService;
        $this->structureRepo = $structureRepo;
    }

    /**
     * Gets all data needed to render the training page.
     *
     * @param int $userId
     * @return array Contains 'resources' (entity) and 'costs' (array)
     */
    public function getTrainingData(int $userId): array
    {
        $resources = $this->resourceRepo->findByUserId($userId);
        // Use dynamic costs
        $costs = $this->calculateDiscountedCosts($userId);
        
        // Enrich with UI data (Stats, Descriptions)
        $units = $this->enrichUnitData($costs);

        return [
            'resources' => $resources,
            'costs' => $costs, // Kept for backward compat if needed, but 'units' is preferred
            'units' => $units
        ];
    }

    private function enrichUnitData(array $costs): array
    {
        $info = [
            'workers' => [
                'name' => 'Worker',
                'role' => 'Economy',
                'atk' => 0, 
                'def' => 0,
                'desc' => 'The backbone of your economy. Each worker generates passive credit income every turn.',
                'icon' => 'fas fa-hammer'
            ],
            'soldiers' => [
                'name' => 'Soldier',
                'role' => 'Offense',
                'atk' => $this->config->get('game_balance.attack.power_per_soldier', 1), 
                'def' => 0, 
                'desc' => 'Frontline troops trained for offensive operations. High casualty rate, high impact.',
                'icon' => 'fas fa-crosshairs'
            ],
            'guards' => [
                'name' => 'Guard',
                'role' => 'Defense',
                'atk' => 0, 
                'def' => $this->config->get('game_balance.attack.power_per_guard', 1),
                'desc' => 'Defensive specialists trained to protect your territory and resources from invasion.',
                'icon' => 'fas fa-shield-alt'
            ],
            'spies' => [
                'name' => 'Spy',
                'role' => 'Intel',
                'atk' => $this->config->get('game_balance.spy.base_power_per_spy', 1), 
                'def' => 0,
                'desc' => 'Covert operatives used to infiltrate enemy empires, gather intel, and sabotage systems.',
                'icon' => 'fas fa-user-secret'
            ],
            'sentries' => [
                'name' => 'Sentry',
                'role' => 'Intel',
                'atk' => 0, 
                'def' => $this->config->get('game_balance.spy.base_power_per_sentry', 1),
                'desc' => 'Counter-espionage units specialized in detecting and neutralizing enemy infiltrators.',
                'icon' => 'fas fa-eye'
            ],
        ];

        $enriched = [];
        foreach ($costs as $key => $cost) {
            // Skip non-unit keys if any exist in the array
            if (!isset($info[$key])) continue;

            $enriched[$key] = array_merge($cost, $info[$key]);
        }
        
        return $enriched;
    }

    private function calculateDiscountedCosts(int $userId): array
    {
        $costs = $this->config->get('game_balance.training', []);
        // Fetch structures - handle potential null if user not fully initialized, though unlikely here
        $structures = $this->structureRepo->findByUserId($userId);
        
        if (!$structures) return $costs;

        $cloningLevel = $structures->cloning_vats_level ?? 0;
        
        if ($cloningLevel > 0) {
            $discountPerLevel = $costs['cloning_vats_discount_per_level'] ?? 0.01;
            $maxDiscount = $costs['cloning_vats_max_discount'] ?? 0.40;
            
            $discountPct = min($cloningLevel * $discountPerLevel, $maxDiscount);
            $multiplier = 1.0 - $discountPct;
            
            // Apply to Soldiers and Guards credits only
            if (isset($costs['soldiers']['credits'])) {
                $costs['soldiers']['credits'] = (int)floor($costs['soldiers']['credits'] * $multiplier);
            }
            if (isset($costs['guards']['credits'])) {
                $costs['guards']['credits'] = (int)floor($costs['guards']['credits'] * $multiplier);
            }
        }
        
        return $costs;
    }

    /**
     * Attempts to train a specified number of units.
     *
     * @param int $userId
     * @param string $unitType The key of the unit (e.g., 'soldiers')
     * @param int $amount The number of units to train
     * @return ServiceResponse
     */
    public function trainUnits(int $userId, string $unitType, int $amount): ServiceResponse
    {
        // 1. Validation 1: Amount
        if ($amount <= 0) {
            return ServiceResponse::error('Amount to train must be a positive number.');
        }

        // 2. Validation 2: Unit Type & Costs
        // Use discounted costs
        $allCosts = $this->calculateDiscountedCosts($userId);
        $unitCost = $allCosts[$unitType] ?? null;
        
        if (is_null($unitCost)) {
            return ServiceResponse::error('Invalid unit type selected.');
        }

        // 3. Validation 3: Get User's Resources
        $resources = $this->resourceRepo->findByUserId($userId);
        if (!$resources) {
            return ServiceResponse::error('Could not find your resource data.');
        }

        // 4. Calculate Costs
        $totalCreditCost = $unitCost['credits'] * $amount;
        $totalCitizenCost = $unitCost['citizens'] * $amount;

        // 5. Validation 4: Check Costs vs. Resources
        if ($resources->credits < $totalCreditCost) {
            return ServiceResponse::error('You do not have enough credits to train these units.');
        }
        if ($resources->untrained_citizens < $totalCitizenCost) {
            return ServiceResponse::error('You do not have enough untrained citizens for this training.');
        }

        // 6. Validation 5: Army Capacity (Generals)
        if ($unitType === 'soldiers') {
            $cap = $this->generalService->getArmyCapacity($userId);
            if (($resources->soldiers + $amount) > $cap) {
                return ServiceResponse::error("Army Limit Reached ({$cap}). Commission a General to expand your forces.");
            }
        }

        // 7. Calculate New Totals
        $newCredits = $resources->credits - $totalCreditCost;
        $newUntrained = $resources->untrained_citizens - $totalCitizenCost;
        
        // Start with existing values
        $newWorkers = $resources->workers;
        $newSoldiers = $resources->soldiers;
        $newGuards = $resources->guards;
        $newSpies = $resources->spies;
        $newSentries = $resources->sentries;

        // Add the newly trained units to the correct property
        match ($unitType) {
            'workers'  => $newWorkers += $amount,
            'soldiers' => $newSoldiers += $amount,
            'guards'   => $newGuards += $amount,
            'spies'    => $newSpies += $amount,
            'sentries' => $newSentries += $amount,
            default    => null,
        };

        // 7. Execute Update (Atomic)
        $success = $this->resourceRepo->updateTrainedUnits(
            $userId,
            $newCredits,
            $newUntrained,
            $newWorkers,
            $newSoldiers,
            $newGuards,
            $newSpies,
            $newSentries
        );

        if ($success) {
            $msg = 'Training complete. ' . number_format($amount) . ' ' . ucfirst($unitType) . ' added to your forces.';
            return ServiceResponse::success($msg);
        } else {
            return ServiceResponse::error('A database error occurred during training. Please try again.');
        }
    }
}