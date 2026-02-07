<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

/**
 * Handles business logic for Personal Structures (Upgrades & Costs).
 * * Fixes Fatal Error: Corrects class name and namespace.
 * * Implements logic previously mixed in Controller/View.
 */
class StructureService
{
    private PDO $db;
    private Config $config;
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;

    public function __construct(
        PDO $db,
        Config $config,
        ResourceRepository $resourceRepo,
        StructureRepository $structureRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
    }

    /**
     * Retrieves raw data for the Structures page.
     *
     * @param int $userId
     * @return array
     */
    public function getStructureData(int $userId): array
    {
        $resources = $this->resourceRepo->findByUserId($userId);
        $structures = $this->structureRepo->findByUserId($userId);
        
        // Load Configurations for calculations
        $structureConfig = $this->config->get('game_balance.structures', []);
        
        // Calculate Costs for Next Levels
        $costs = [];
        foreach ($structureConfig as $key => $data) {
            $currentLevel = $structures->{$key . '_level'} ?? 0;
            $creditCost = $this->calculateCost($data['base_cost'], $data['multiplier'], $currentLevel);
            $costs[$key] = [
                'credits' => $creditCost
            ];
        }

        return [
            'resources' => $resources,
            'structures' => $structures,
            'costs' => $costs,
            'structureFormulas' => $structureConfig,
            // Pass configs needed for Presenter text generation
            'turnConfig' => $this->config->get('game_balance.turn_processor', []),
            'attackConfig' => $this->config->get('game_balance.attack', []),
            'spyConfig' => $this->config->get('game_balance.spy', [])
        ];
    }

    /**
     * Process multiple structure upgrades in a single transaction.
     *
     * @param int $userId
     * @param array $structureKeys
     * @return ServiceResponse
     */
    public function processBatchUpgrade(int $userId, array $structureKeys): ServiceResponse
    {
        if (empty($structureKeys)) {
            return ServiceResponse::error('No structures selected for upgrade.');
        }

        // 1. Get User Data
        $resources = $this->resourceRepo->findByUserId($userId);
        $structures = $this->structureRepo->findByUserId($userId);
        
        // 2. Load Config
        $structureConfig = $this->config->get('game_balance.structures', []);
        
        // 3. Simulate & Calculate Total Costs
        $simulatedLevels = [];
        foreach ($structureConfig as $k => $v) {
            $simulatedLevels[$k] = $structures->{$k . '_level'} ?? 0;
        }

        $totalCreditCost = 0;
        
        $upgradesToPerform = []; // [key => newLevel]

        foreach ($structureKeys as $key) {
            if (!isset($structureConfig[$key])) {
                continue; // Skip invalid keys
            }
            
            $config = $structureConfig[$key];
            $currentLevel = $simulatedLevels[$key];
            
            // Calculate Cost for THIS level
            $cCost = $this->calculateCost($config['base_cost'], $config['multiplier'], $currentLevel);

            $totalCreditCost += $cCost;

            // Increment simulated level for next iteration (if same key is present)
            $simulatedLevels[$key]++;
            
            // Track the FINAL level to reach for this key
            $upgradesToPerform[$key] = $simulatedLevels[$key];
        }

        if (empty($upgradesToPerform)) {
             return ServiceResponse::error('No valid structures selected.');
        }

        // 4. Check Affordability
        if ($resources->credits < $totalCreditCost) {
            return ServiceResponse::error('Insufficient credits for batch upgrade. Total: ' . number_format($totalCreditCost));
        }

        // 5. Transaction
        $transactionStarted = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStarted = true;
        }

        try {
            // Deduct Resources
            $this->resourceRepo->updateResources(
                $userId,
                -1 * $totalCreditCost
            );
            
            // Update Levels
            foreach ($upgradesToPerform as $key => $level) {
                 $column = $key . '_level';
                 $this->structureRepo->updateStructureLevel($userId, $column, $level);
            }

            if ($transactionStarted) {
                $this->db->commit();
            }
            
            return ServiceResponse::success(
                count($structureKeys) . " structures upgraded successfully!",
                [
                    'total_cost' => $totalCreditCost
                ]
            );

        } catch (Throwable $e) {
            if ($transactionStarted && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Batch Upgrade Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred during batch upgrade.');
        }
    }

    /**
     * Process a structure upgrade.
     *
     * @param int $userId
     * @param string $structureKey
     * @return ServiceResponse
     */
    public function upgradeStructure(int $userId, string $structureKey): ServiceResponse
    {
        // 1. Validate Structure Existence
        $structureConfig = $this->config->get('game_balance.structures.' . $structureKey);
        if (!$structureConfig) {
            return ServiceResponse::error('Invalid structure type.');
        }

        // 2. Get User Data
        $resources = $this->resourceRepo->findByUserId($userId);
        $structures = $this->structureRepo->findByUserId($userId);

        // 3. Calculate Cost
        // Column names in DB are typically "{key}_level" (e.g., fortification_level)
        $dbColumn = $structureKey . '_level';
        $currentLevel = $structures->{$dbColumn} ?? 0;
        $nextLevel = $currentLevel + 1;
        
        $creditCost = $this->calculateCost($structureConfig['base_cost'], $structureConfig['multiplier'], $currentLevel);

        // 4. Check Affordability
        if ($resources->credits < $creditCost) {
            return ServiceResponse::error('Insufficient credits for upgrade.');
        }

        // 5. Transaction
        $transactionStarted = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStarted = true;
        }

        try {
            // Deduct Credits atomically
            $this->resourceRepo->updateResources(
                $userId,
                -1 * $creditCost
            );
            
            // Update Structure Level
            $this->structureRepo->updateStructureLevel($userId, $dbColumn, $nextLevel);

            if ($transactionStarted) {
                $this->db->commit();
            }
            
            return ServiceResponse::success(
                "{$structureConfig['name']} upgraded to Level {$nextLevel}!",
                [
                    'new_level' => $nextLevel, 
                    'cost' => $creditCost
                ]
            );

        } catch (Throwable $e) {
            if ($transactionStarted && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Structure Upgrade Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
    }

    /**
     * Calculate upgrade cost: Base * (Multiplier ^ CurrentLevel)
     */
    private function calculateCost(int $base, float $multiplier, int $currentLevel): int
    {
        if ($currentLevel === 0) {
            return $base;
        }
        return (int)floor($base * pow($multiplier, $currentLevel));
    }
}