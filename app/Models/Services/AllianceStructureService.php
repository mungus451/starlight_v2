<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Core\Session;
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
 */
class AllianceStructureService
{
    private PDO $db;
    private Session $session;
    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private AllianceStructureRepository $allianceStructRepo;
    private AllianceStructureDefinitionRepository $structDefRepo;
    private UserRepository $userRepo;
    private AllianceRoleRepository $roleRepo;
    private AlliancePolicyService $policyService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        // Load all necessary repositories
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->bankLogRepo = new AllianceBankLogRepository($this->db);
        $this->allianceStructRepo = new AllianceStructureRepository($this->db);
        $this->structDefRepo = new AllianceStructureDefinitionRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $this->roleRepo = new AllianceRoleRepository($this->db);
        
        $this->policyService = new AlliancePolicyService();
    }

    /**
     * Gets all data needed for the Alliance Structures view.
     * Calculates the cost for the *next* level of all structures.
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
     * This is a transactional operation.
     *
     * @param int $adminUserId The user attempting the action
     * @param string $structureKey e.g., 'citadel_shield'
     * @return bool True on success
     */
    public function purchaseOrUpgradeStructure(int $adminUserId, string $structureKey): bool
    {
        // 1. Get User and Alliance data
        $adminUser = $this->userRepo->findById($adminUserId);
        if (!$adminUser || $adminUser->alliance_id === null) {
            $this->session->setFlash('error', 'You are not in an alliance.');
            return false;
        }
        $allianceId = $adminUser->alliance_id;

        // 2. Authorization Check
        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        if (!$adminRole || !$adminRole->can_manage_structures) {
            $this->session->setFlash('error', 'You do not have permission to manage structures.');
            return false;
        }

        // 3. Get Structure & Cost Data
        $definition = $this->structDefRepo->findByKey($structureKey);
        if (!$definition) {
            $this->session->setFlash('error', 'Invalid structure selected.');
            return false;
        }
        
        $alliance = $this->allianceRepo->findById($allianceId);
        $ownedStructure = $this->allianceStructRepo->findByAllianceAndKey($allianceId, $structureKey);

        $currentLevel = $ownedStructure->level ?? 0;
        $nextLevel = $currentLevel + 1;
        $cost = $this->calculateCost($definition->base_cost, $definition->cost_multiplier, $nextLevel);

        // 4. Validation: Check Cost
        if ($alliance->bank_credits < $cost) {
            $this->session->setFlash('error', 'The alliance does not have enough credits in its bank for this upgrade.');
            return false;
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
            
            $this->session->setFlash('success', "{$definition->name} upgrade to Level {$nextLevel} complete!");
            return true;

        } catch (Throwable $e) {
            // 5e. Rollback on failure
            $this->db->rollBack();
            error_log('Alliance Structure Upgrade Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. Please try again.');
            return false;
        }
    }

    /**
     * Calculates the cost of a structure at a given level.
     * Formula: base_cost * (multiplier ^ (level - 1))
     *
     * @param int $baseCost
     * @param float $multiplier
     * @param int $level The level to calculate the cost for
     * @return int The calculated cost
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