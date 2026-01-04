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
use App\Models\Services\NotificationService;
use PDO;
use Throwable;

/**
* Handles all business logic for Alliance Structures.
* * Refactored Phase 1.3.2: Strict MVC Compliance.
* * Fixed: Added nested transaction support.
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
private NotificationService $notificationService;

public function __construct(
PDO $db,
AllianceRepository $allianceRepo,
AllianceBankLogRepository $bankLogRepo,
AllianceStructureRepository $allianceStructRepo,
AllianceStructureDefinitionRepository $structDefRepo,
UserRepository $userRepo,
AllianceRoleRepository $roleRepo,
AlliancePolicyService $policyService,
NotificationService $notificationService
) {
$this->db = $db;

$this->allianceRepo = $allianceRepo;
$this->bankLogRepo = $bankLogRepo;
$this->allianceStructRepo = $allianceStructRepo;
$this->structDefRepo = $structDefRepo;
$this->userRepo = $userRepo;
$this->roleRepo = $roleRepo;

$this->policyService = $policyService;
$this->notificationService = $notificationService;
}

/**
* Gets all data needed for the Alliance Structures view, checking permissions.
*
* @param int $userId
* @return ServiceResponse
*/
public function getStructurePageData(int $userId): ServiceResponse
{
// 1. Validate User & Alliance
$user = $this->userRepo->findById($userId);
if (!$user || $user->alliance_id === null) {
return ServiceResponse::error('You must be in an alliance to view this page.');
}
$allianceId = $user->alliance_id;

// 2. Check Permissions
$role = $this->roleRepo->findById($user->alliance_role_id);
$canManage = ($role && $role->can_manage_structures);

// 3. Fetch Data
$alliance = $this->allianceRepo->findById($allianceId);
$definitions = $this->structDefRepo->getAllDefinitions();
$ownedStructures = $this->allianceStructRepo->findByAllianceId($allianceId);

$costs = [];
$currentLevels = [];

// 4. Calculate Costs
foreach ($definitions as $def) {
$key = $def->structure_key;
// Fixed: Null-safe check to handle missing keys in $ownedStructures
$currentLevel = ($ownedStructures[$key] ?? null)?->level ?? 0;
$nextLevel = $currentLevel + 1;

// Calculate cost for the next level
$costs[$key] = $this->calculateCost($def->base_cost, $def->cost_multiplier, $nextLevel);
$currentLevels[$key] = $currentLevel;
}

return ServiceResponse::success('Data retrieved', [
'alliance' => $alliance,
'definitions' => $definitions,
'costs' => $costs,
'currentLevels' => $currentLevels,
'canManage' => $canManage
]);
}

/**
* Attempts to purchase or upgrade a single structure for an alliance.
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

// Fixed: Null-safe check for new structure purchases
$currentLevel = $ownedStructure?->level ?? 0;
$nextLevel = $currentLevel + 1;
$cost = $this->calculateCost($definition->base_cost, $definition->cost_multiplier, $nextLevel);

// 4. Validation: Check Cost
if ($alliance->bank_credits < $cost) {
return ServiceResponse::error('The alliance does not have enough credits in its bank for this upgrade.');
}

// 5. Execute Transaction
$transactionStartedByMe = false;
try {
if (!$this->db->inTransaction()) {
$this->db->beginTransaction();
$transactionStartedByMe = true;
}

// 5a. Deduct credits from alliance bank
$this->allianceRepo->updateBankCreditsRelative($allianceId, -$cost);

// 5b. Log the expense
$message = "Purchased/Upgraded {$definition->name} to Level {$nextLevel}";
$this->bankLogRepo->createLog($allianceId, $adminUserId, 'structure_purchase', -$cost, $message);

// 5c. Upgrade structure level
$this->allianceStructRepo->createOrUpgrade($allianceId, $structureKey, $nextLevel);

// 5d. Commit
if ($transactionStartedByMe) {
$this->db->commit();
}

// 5e. Notify alliance members after successful commit
$this->notificationService->notifyAllianceMembers(
    $allianceId,
    $adminUserId,
    'Alliance Structure Upgrade',
    "{$adminUser->characterName} upgraded {$definition->name} to Level {$nextLevel}",
    "/alliance/structures"
);

return ServiceResponse::success("{$definition->name} upgrade to Level {$nextLevel} complete!");

} catch (Throwable $e) {
// 5e. Rollback on failure
if ($transactionStartedByMe && $this->db->inTransaction()) {
$this->db->rollBack();
}
error_log('Alliance Structure Upgrade Error: ' . $e->getMessage());

if (!$transactionStartedByMe) throw $e; // Re-throw for tests

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