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
            $crystalCost = 0;
            $darkMatterCost = 0;

            if (isset($data['base_crystal_cost'])) {
                $crystalCost = $this->calculateCost($data['base_crystal_cost'], $data['multiplier'], $currentLevel);
            }
            if (isset($data['base_dark_matter_cost'])) {
                $darkMatterCost = $this->calculateCost($data['base_dark_matter_cost'], $data['multiplier'], $currentLevel);
            }
            $costs[$key] = [
                'credits' => $creditCost, 
                'crystals' => $crystalCost,
                'dark_matter' => $darkMatterCost
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
        $crystalCost = 0;
        $darkMatterCost = 0;

        if (isset($structureConfig['base_crystal_cost'])) {
            $crystalCost = $this->calculateCost($structureConfig['base_crystal_cost'], $structureConfig['multiplier'], $currentLevel);
        }
        if (isset($structureConfig['base_dark_matter_cost'])) {
            $darkMatterCost = $this->calculateCost($structureConfig['base_dark_matter_cost'], $structureConfig['multiplier'], $currentLevel);
        }

        // 4. Check Affordability
        if ($resources->credits < $creditCost) {
            return ServiceResponse::error('Insufficient credits for upgrade.');
        }
        if ($resources->naquadah_crystals < $crystalCost) {
            return ServiceResponse::error('Insufficient naquadah crystals for upgrade.');
        }
        if ($resources->dark_matter < $darkMatterCost) {
            return ServiceResponse::error('Insufficient dark matter for upgrade.');
        }

        // 5. Transaction
        $transactionStarted = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $transactionStarted = true;
        }

        try {
            // Deduct Credits, Crystals, and Dark Matter atomically
            $this->resourceRepo->updateResources(
                $userId,
                -1 * $creditCost,
                -1 * $crystalCost,
                -1 * $darkMatterCost
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
                    'cost' => $creditCost, 
                    'crystal_cost' => $crystalCost,
                    'dark_matter_cost' => $darkMatterCost
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