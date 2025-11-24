<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for Structures.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class StructureService
{
    private PDO $db;
    private Config $config;
    
    private StructureRepository $structureRepo;
    private ResourceRepository $resourceRepo;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param Config $config
     * @param StructureRepository $structureRepo
     * @param ResourceRepository $resourceRepo
     */
    public function __construct(
        PDO $db,
        Config $config,
        StructureRepository $structureRepo,
        ResourceRepository $resourceRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        
        $this->structureRepo = $structureRepo;
        $this->resourceRepo = $resourceRepo;
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
        
        // Load all relevant game balance configs
        $structureFormulas = $this->config->get('game_balance.structures', []);
        $turnConfig = $this->config->get('game_balance.turn_processor', []);
        $attackConfig = $this->config->get('game_balance.attack', []);
        $spyConfig = $this->config->get('game_balance.spy', []);

        $costs = [];
        
        // Loop over the config file to ensure we get all structures
        foreach ($structureFormulas as $key => $formula) {
            $columnName = $key . '_level'; 
            $currentLevel = $structures->{$columnName} ?? 0;
            $nextLevel = $currentLevel + 1;
            
            $costs[$key] = $this->calculateCost($formula, $nextLevel);
        }

        return [
            'structures' => $structures,
            'resources' => $resources,
            'costs' => $costs,
            'structureFormulas' => $structureFormulas,
            'turnConfig' => $turnConfig,
            'attackConfig' => $attackConfig,
            'spyConfig' => $spyConfig
        ];
    }

    /**
     * Attempts to upgrade a single structure for a user.
     *
     * @param int $userId
     * @param string $structureKey
     * @return ServiceResponse
     */
    public function upgradeStructure(int $userId, string $structureKey): ServiceResponse
    {
        // 1. Validation: Get Formula
        $formula = $this->config->get('game_balance.structures.' . $structureKey);

        if (is_null($formula)) {
            return ServiceResponse::error('Invalid structure selected.');
        }

        // 2. Get Current Data
        $structures = $this->structureRepo->findByUserId($userId);
        $resources = $this->resourceRepo->findByUserId($userId);

        if (!$structures || !$resources) {
            return ServiceResponse::error('Could not find your user data.');
        }

        // 3. Calculate Cost
        $columnName = $structureKey . '_level';
        $currentLevel = $structures->{$columnName};
        $nextLevel = $currentLevel + 1;
        $cost = $this->calculateCost($formula, $nextLevel);

        // 4. Validation: Check Cost
        if ($resources->credits < $cost) {
            return ServiceResponse::error('You do not have enough credits for this upgrade.');
        }

        // 5. Execute Transaction
        $this->db->beginTransaction();
        try {
            // 5a. Deduct credits
            $newCredits = $resources->credits - $cost;
            $this->resourceRepo->updateCredits($userId, $newCredits);
            
            // 5b. Upgrade structure level
            $this->structureRepo->updateStructureLevel($userId, $columnName, $nextLevel);

            $this->db->commit();
            
            $displayName = $formula['name'] ?? $structureKey;
            return ServiceResponse::success("{$displayName} upgrade to Level {$nextLevel} complete!");

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Structure Upgrade Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }

    /**
     * Calculates the cost of a structure at a given level.
     *
     * @param array $formula
     * @param int $level
     * @return int
     */
    private function calculateCost(array $formula, int $level): int
    {
        return (int)($formula['base_cost'] * pow($formula['multiplier'], $level - 1));
    }
}