<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for Structures.
 */
class StructureService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private StructureRepository $structureRepo;
    private ResourceRepository $resourceRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        
        // This service needs both repositories
        $this->structureRepo = new StructureRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
    }

    /**
     * Gets all data needed for the Structures view.
     * Calculates the cost for the *next* level of all structures.
     *
     * @param int $userId
     * @return array
     */
    public function getStructureData(int $userId): array
    {
        $structures = $this->structureRepo->findByUserId($userId);
        $resources = $this->resourceRepo->findByUserId($userId);
        
        // --- NEW: Load all relevant game balance configs ---
        $structureFormulas = $this->config->get('game_balance.structures', []);
        $turnConfig = $this->config->get('game_balance.turn_processor', []);
        $attackConfig = $this->config->get('game_balance.attack', []);
        $spyConfig = $this->config->get('game_balance.spy', []);
        // --- END NEW ---

        $costs = [];
        
        // Loop over the config file to ensure we get all structures
        foreach ($structureFormulas as $key => $formula) {
            // e.g., 'fortification' + '_level' = 'fortification_level'
            $columnName = $key . '_level'; 
            
            // Get the structure's current level from the UserStructure entity
            $currentLevel = $structures->{$columnName} ?? 0;
            $nextLevel = $currentLevel + 1;
            
            // Calculate the cost for the next level
            $costs[$key] = $this->calculateCost($formula, $nextLevel);
        }

        return [
            'structures' => $structures,
            'resources' => $resources,
            'costs' => $costs, // Array of calculated costs for the *next* level
            'structureFormulas' => $structureFormulas, // The raw config data (for names)
            
            // --- NEW: Pass extra configs to the view ---
            'turnConfig' => $turnConfig,
            'attackConfig' => $attackConfig,
            'spyConfig' => $spyConfig
        ];
    }

    /**
     * Attempts to upgrade a single structure for a user.
     * This is a transactional operation.
     *
     * @param int $userId
     * @param string $structureKey e.g., 'fortification'
     * @return bool True on success, false on failure
     */
    public function upgradeStructure(int $userId, string $structureKey): bool
    {
        // --- 1. Validation 1: Get Formula ---
        $formula = $this->config->get('game_balance.structures.' . $structureKey);

        if (is_null($formula)) {
            $this->session->setFlash('error', 'Invalid structure selected.');
            return false;
        }

        // --- 2. Get Current Data ---
        $structures = $this->structureRepo->findByUserId($userId);
        $resources = $this->resourceRepo->findByUserId($userId);

        if (!$structures || !$resources) {
            $this->session->setFlash('error', 'Could not find your user data.');
            return false;
        }

        // --- 3. Calculate Cost ---
        $columnName = $structureKey . '_level'; // e.g., 'fortification_level'
        $currentLevel = $structures->{$columnName};
        $nextLevel = $currentLevel + 1;
        $cost = $this->calculateCost($formula, $nextLevel);

        // --- 4. Validation 2: Check Cost ---
        if ($resources->credits < $cost) {
            $this->session->setFlash('error', 'You do not have enough credits for this upgrade.');
            return false;
        }

        // --- 5. Execute Transaction ---
        $this->db->beginTransaction();
        try {
            // 5a. Deduct credits
            $newCredits = $resources->credits - $cost;
            $this->resourceRepo->updateCredits($userId, $newCredits);
            
            // 5b. Upgrade structure level (uses the whitelisted method)
            $this->structureRepo->updateStructureLevel($userId, $columnName, $nextLevel);

            // 5c. Commit
            $this->db->commit();
            
            $displayName = $formula['name'] ?? $structureKey;
            $this->session->setFlash('success', "{$displayName} upgrade to Level {$nextLevel} complete!");
            return true;

        } catch (Throwable $e) {
            // 5d. Rollback on failure
            $this->db->rollBack();
            error_log('Structure Upgrade Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. Please try again.');
            return false;
        }
    }

    /**
     * Calculates the cost of a structure at a given level.
     * Formula: base_cost * (multiplier ^ (level - 1))
     *
     * @param array $formula ['base_cost' => int, 'multiplier' => float]
     * @param int $level The level to calculate the cost for
     * @return int The calculated cost
     */
    private function calculateCost(array $formula, int $level): int
    {
        return (int)($formula['base_cost'] * pow($formula['multiplier'], $level - 1));
    }
}