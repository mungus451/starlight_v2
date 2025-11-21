<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Session;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for Structures.
 * * Refactored for Strict Dependency Injection.
 */
class StructureService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    
    private StructureRepository $structureRepo;
    private ResourceRepository $resourceRepo;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Session $session
     * @param Config $config
     * @param StructureRepository $structureRepo
     * @param ResourceRepository $resourceRepo
     */
    public function __construct(
        PDO $db,
        Session $session,
        Config $config,
        StructureRepository $structureRepo,
        ResourceRepository $resourceRepo
    ) {
        $this->db = $db;
        $this->session = $session;
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
     * @return bool True on success
     */
    public function upgradeStructure(int $userId, string $structureKey): bool
    {
        // 1. Validation: Get Formula
        $formula = $this->config->get('game_balance.structures.' . $structureKey);

        if (is_null($formula)) {
            $this->session->setFlash('error', 'Invalid structure selected.');
            return false;
        }

        // 2. Get Current Data
        $structures = $this->structureRepo->findByUserId($userId);
        $resources = $this->resourceRepo->findByUserId($userId);

        if (!$structures || !$resources) {
            $this->session->setFlash('error', 'Could not find your user data.');
            return false;
        }

        // 3. Calculate Cost
        $columnName = $structureKey . '_level';
        $currentLevel = $structures->{$columnName};
        $nextLevel = $currentLevel + 1;
        $cost = $this->calculateCost($formula, $nextLevel);

        // 4. Validation: Check Cost
        if ($resources->credits < $cost) {
            $this->session->setFlash('error', 'You do not have enough credits for this upgrade.');
            return false;
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
            $this->session->setFlash('success', "{$displayName} upgrade to Level {$nextLevel} complete!");
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Structure Upgrade Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. Please try again.');
            return false;
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