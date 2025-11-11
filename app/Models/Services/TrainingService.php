<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\ResourceRepository;
use PDO;

/**
 * Handles all business logic for training units.
 */
class TrainingService
{
    private Session $session;
    private ResourceRepository $resourceRepo;
    private Config $config;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        
        // This service only needs the ResourceRepository
        $this->resourceRepo = new ResourceRepository($db);
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
        
        // Get the 'training' section from the 'game_balance.php' config file
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
     * @return bool True on success, false on failure
     */
    public function trainUnits(int $userId, string $unitType, int $amount): bool
    {
        // --- 1. Validation 1: Amount ---
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Amount to train must be a positive number.');
            return false;
        }

        // --- 2. Validation 2: Unit Type & Costs ---
        $unitCost = $this->config->get('game_balance.training.' . $unitType);
        
        if (is_null($unitCost)) {
            $this->session->setFlash('error', 'Invalid unit type selected.');
            return false;
        }

        // --- 3. Validation 3: Get User's Resources ---
        $resources = $this->resourceRepo->findByUserId($userId);
        if (!$resources) {
            $this->session->setFlash('error', 'Could not find your resource data.');
            return false;
        }

        // --- 4. Calculate Costs ---
        $totalCreditCost = $unitCost['credits'] * $amount;
        $totalCitizenCost = $unitCost['citizens'] * $amount;

        // --- 5. Validation 4: Check Costs vs. Resources ---
        if ($resources->credits < $totalCreditCost) {
            $this->session->setFlash('error', 'You do not have enough credits to train these units.');
            return false;
        }
        if ($resources->untrained_citizens < $totalCitizenCost) {
            $this->session->setFlash('error', 'You do not have enough untrained citizens for this training.');
            return false;
        }

        // --- 6. Calculate New Totals ---
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
            default    => null, // We already validated this, but good practice
        };

        // --- 7. Execute Update ---
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
            $this->session->setFlash('success', 'Training complete. ' . number_format($amount) . ' ' . $unitType . ' added to your forces.');
            return true;
        } else {
            $this->session->setFlash('error', 'A database error occurred during training. Please try again.');
            return false;
        }
    }
}