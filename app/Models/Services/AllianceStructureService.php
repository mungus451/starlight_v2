<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceStructureRepository;
use App\Models\Repositories\AllianceStructureDefinitionRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Services\AlliancePolicyService;
use PDO;
use Throwable;

/**
 * Handles all business logic for Alliance Structures.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class AllianceStructureService
{
    private PDO $db;
    
    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private AllianceStructureRepository $allianceStructRepo;
    private AllianceStructureDefinitionRepository $structDefRepo;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;
    
    private AlliancePolicyService $policyService;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param AllianceRepository $allianceRepo
     * @param AllianceBankLogRepository $bankLogRepo
     * @param AllianceStructureRepository $allianceStructRepo
     * @param AllianceStructureDefinitionRepository $structDefRepo
     * @param UserRepository $userRepo
     * @param AllianceRoleRepository $roleRepo
     * @param AlliancePolicyService $policyService
     */
    public function __construct(
        PDO $db,
        AllianceRepository $allianceRepo,
        AllianceBankLogRepository $bankLogRepo,
        AllianceStructureRepository $allianceStructRepo,
        AllianceStructureDefinitionRepository $structDefRepo,
        UserRepository $userRepo,
        AllianceRoleRepository $roleRepo,
        AlliancePolicyService $policyService
    ) {
        $this->db = $db;
        
        $this->allianceRepo = $allianceRepo;
        $this->bankLogRepo = $bankLogRepo;
        $this->allianceStructRepo = $allianceStructRepo;
        $this->structDefRepo = $structDefRepo;
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
        
        $this->policyService = $policyService;
    }

    /**
     * Gets all data needed for the Alliance Structures view.
     *
     * @param int $allianceId
     * @return array
     */
    public function getStructureData(int $allianceId): array
    {
        $alliance = $this->allianceRepo->findById($allianceId);
        $definitions = $this->structDefRepo->getAllDefinitions();
        $ownedStructures = $this->allianceStructRepo->findByAllianceId($allianceId);

        $costs = [];
        $currentLevels = [];
        
        foreach ($definitions as $def) {
            $key = $def->structure_key;
            $currentLevel = $ownedStructures[$key]->level ?? 0;
            $nextLevel = $currentLevel + 1;
            
            // Calculate cost for the next level
            $costs[$key] = $this->calculateCost($def->base_cost, $def->cost_multiplier, $nextLevel);
            $currentLevels[$key] = $currentLevel;
        }

        return [
            'alliance' => $alliance,
            'definitions' => $definitions,
            'costs' => $costs,
            'currentLevels' => $currentLevels,
        ];
    }

    /**
     * Attempts to purchase or upgrade a single structure for an alliance.
     *
     * @param int $adminUserId The user attempting the action
     * @param string $structureKey e.g., 'citadel_shield'
     * @return ServiceResponse
     */
    public function purchaseOrUpgradeStructure(int $adminUserId, string $structureKey): ServiceResponse
    {
        // 1. Get User and Alliance data
        $adminUser = $this->userRepo->findById($adminUserId);
        if (!$adminUser || $adminUser->alliance_id === null) {
            return ServiceResponse::error('You are not in an alliance.');
        }
        $allianceId = $adminUser->alliance_id;

        // 2. Authorization Check
        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        if (!$adminRole || !$adminRole->can_manage_structures) {
            return ServiceResponse::error('You do not have permission to manage structures.');
        }

        // 3. Get Structure & Cost Data
        $definition = $this->structDefRepo->findByKey($structureKey);
        if (!$definition) {
            return ServiceResponse::error('Invalid structure selected.');
        }
        
        $alliance = $this->allianceRepo->findById($allianceId);
        $ownedStructure = $this->allianceStructRepo->findByAllianceAndKey($allianceId, $structureKey);

        $currentLevel = $ownedStructure->level ?? 0;
        $nextLevel = $currentLevel + 1;
        $cost = $this->calculateCost($definition->base_cost, $definition->cost_multiplier, $nextLevel);

        // 4. Validation: Check Cost
        if ($alliance->bank_credits < $cost) {
            return ServiceResponse::error('The alliance does not have enough credits in its bank for this upgrade.');
        }

        // 5. Execute Transaction
        $this->db->beginTransaction();
        try {
            // 5a. Deduct credits from alliance bank
            $this->allianceRepo->updateBankCreditsRelative($allianceId, -$cost);
            
            // 5b. Log the expense
            $message = "Purchased/Upgraded {$definition->name} to Level {$nextLevel}";
            $this->bankLogRepo->createLog($allianceId, $adminUserId, 'structure_purchase', -$cost, $message);

            // 5c. Upgrade structure level
            $this->allianceStructRepo->createOrUpgrade($allianceId, $structureKey, $nextLevel);

            // 5d. Commit
            $this->db->commit();
            
            return ServiceResponse::success("{$definition->name} upgrade to Level {$nextLevel} complete!");

        } catch (Throwable $e) {
            // 5e. Rollback on failure
            $this->db->rollBack();
            error_log('Alliance Structure Upgrade Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }

    /**
     * Calculates the cost of a structure at a given level.
     */
    private function calculateCost(int $baseCost, float $multiplier, int $level): int
    {
        // Cost for level 1 is just the base cost
        if ($level <= 1) {
            return $baseCost;
        }
        return (int)floor($baseCost * pow($multiplier, $level - 1));
    }
}