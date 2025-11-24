<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\ResourceRepository;
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

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param Config $config
     * @param ResourceRepository $resourceRepo
     */
    public function __construct(Config $config, ResourceRepository $resourceRepo)
    {
        $this->config = $config;
        $this->resourceRepo = $resourceRepo;
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
        $costs = $this->config->get('game_balance.training', []);

        return [
            'resources' => $resources,
            'costs' => $costs
        ];
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
        $unitCost = $this->config->get('game_balance.training.' . $unitType);
        
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

        // 6. Calculate New Totals
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