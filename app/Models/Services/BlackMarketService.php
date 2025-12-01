<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\BountyRepository;
use PDO;
use Throwable;

/**
* Handles purchase logic for the Black Market.
* Orchestrates transactions for premium features.
*/
class BlackMarketService
{
private PDO $db;
private Config $config;
private ResourceRepository $resourceRepo;
private StatsRepository $statsRepo;
private UserRepository $userRepo;
private BountyRepository $bountyRepo;

// Injected to handle shadow contract execution
private AttackService $attackService;

public function __construct(
PDO $db,
Config $config,
ResourceRepository $resourceRepo,
StatsRepository $statsRepo,
UserRepository $userRepo,
BountyRepository $bountyRepo,
AttackService $attackService
) {
$this->db = $db;
$this->config = $config;
$this->resourceRepo = $resourceRepo;
$this->statsRepo = $statsRepo;
$this->userRepo = $userRepo;
$this->bountyRepo = $bountyRepo;
$this->attackService = $attackService;
}

// --- Option 1: Stat Respec ---
public function purchaseStatRespec(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.stat_respec', 50);
return $this->processPurchase($userId, $cost, function() use ($userId) {
$stats = $this->statsRepo->findByUserId($userId);

// Refund all allocated points
$pointsToRefund = $stats->strength_points +
$stats->constitution_points +
$stats->wealth_points +
$stats->dexterity_points +
$stats->charisma_points;

$newPool = $stats->level_up_points + $pointsToRefund;

// Set stats to 0, pool to total
$this->statsRepo->updateBaseStats($userId, $newPool, 0, 0, 0, 0, 0);
return "Neural pathways reset. You have {$newPool} points available.";
});
}

// --- Option 2: Turn Refill ---
public function purchaseTurnRefill(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.turn_refill', 10);
$amount = $this->config->get('black_market.quantities.turn_refill_amount', 50);

return $this->processPurchase($userId, $cost, function() use ($userId, $amount) {
$this->statsRepo->applyTurnAttackTurn($userId, $amount);
return "Stim-pack applied. {$amount} attack turns restored.";
});
}

// --- Option 3: Citizen Package ---
public function purchaseCitizens(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.citizen_package', 25);
$amount = $this->config->get('black_market.quantities.citizen_package_amount', 500);

return $this->processPurchase($userId, $cost, function() use ($userId, $amount) {
// Use existing repo method to add citizens (0 credits, 0 interest)
$this->resourceRepo->applyTurnIncome($userId, 0, 0, $amount);
return "Smuggling successful. {$amount} citizens have joined your empire.";
});
}

// --- Option 8: The Void Container (Loot Box) ---
public function openVoidContainer(int $userId): ServiceResponse
{
$cost = $this->config->get('black_market.costs.void_container', 100);
$lootTable = $this->config->get('black_market.void_container_loot', []);

return $this->processPurchase($userId, $cost, function() use ($userId, $lootTable) {
// RNG Logic
$totalWeight = array_sum(array_column($lootTable, 'weight'));
$roll = mt_rand(1, $totalWeight);
$current = 0;
$selected = null;

foreach ($lootTable as $item) {
$current += $item['weight'];
if ($roll <= $current) {
$selected = $item;
break;
}
}

// Determine Quantity (if applicable)
$qty = 0;
if (isset($selected['min']) && isset($selected['max'])) {
$qty = mt_rand($selected['min'], $selected['max']);
}

$msg = "";
$outcomeType = 'success'; // Default to success (Green)
$resources = $this->resourceRepo->findByUserId($userId);

// --- Outcome Handling ---
switch ($selected['type']) {
case 'credits':
$this->resourceRepo->updateCredits($userId, $resources->credits + $qty);
$msg = "You found: " . number_format($qty) . " Credits!";
break;

case 'unit':
$u = $selected['unit']; // 'soldiers' or 'spies'
$this->resourceRepo->updateTrainedUnits(
$userId,
$resources->credits, // No credit change
$resources->untrained_citizens, // Do not consume citizens
$resources->workers,
$u === 'soldiers' ? $resources->soldiers + $qty : $resources->soldiers,
$resources->guards,
$u === 'spies' ? $resources->spies + $qty : $resources->spies,
$resources->sentries
);
$msg = "Reinforcements: " . number_format($qty) . " " . ucfirst($u);
break;

case 'crystals':
// Refund cost + jackpot amount
$this->resourceRepo->updateResources($userId, 0, $qty);
$msg = "JACKPOT! " . number_format($qty) . " Naquadah Crystals!";
break;

case 'neutral':
$msg = $selected['text'] ?? "The container was empty.";
// Neutral stays green (success), just disappointing text
break;

case 'credits_loss':
$newCredits = max(0, $resources->credits - $qty);
$this->resourceRepo->updateCredits($userId, $newCredits);
$loss = number_format($resources->credits - $newCredits);
$msg = ($selected['text'] ?? "Trap triggered!") . " Lost {$loss} Credits.";
$outcomeType = 'negative'; // Will trigger Red flash
break;

case 'unit_loss':
$u = $selected['unit'];
$currentUnits = $resources->{$u};
$newUnits = max(0, $currentUnits - $qty);
$loss = $currentUnits - $newUnits;

$this->resourceRepo->updateTrainedUnits(
$userId,
$resources->credits,
$resources->untrained_citizens,
$resources->workers,
$u === 'soldiers' ? $newUnits : $resources->soldiers,
$resources->guards,
$u === 'spies' ? $newUnits : $resources->spies,
$resources->sentries
);
$msg = ($selected['text'] ?? "Ambush!") . " Lost {$loss} " . ucfirst($u) . ".";
$outcomeType = 'negative'; // Will trigger Red flash
break;

default:
$msg = "The container dissolves into nothingness.";
break;
}

// Return complex result for processPurchase to handle
return [
'message' => $msg,
'status' => $outcomeType
];
});
}

// --- Option 10: Place Bounty ---
public function placeBounty(int $userId, string $targetName, float $amount): ServiceResponse
{
if ($amount < 10) return ServiceResponse::error("Minimum bounty is 10 Crystals.");

$target = $this->userRepo->findByCharacterName($targetName);
if (!$target) return ServiceResponse::error("Target not found.");
if ($target->id === $userId) return ServiceResponse::error("You cannot place a bounty on yourself.");

return $this->processPurchase($userId, $amount, function() use ($userId, $target, $amount) {
$this->bountyRepo->create($target->id, $userId, $amount);
return "Bounty of " . number_format($amount) . " Crystals placed on {$target->characterName}.";
});
}

// --- Option 4: Shadow Contract ---
public function purchaseShadowContract(int $userId, string $targetName): ServiceResponse
{
$cost = $this->config->get('black_market.costs.shadow_contract', 500);

// 1. Validation & Resource Check
$resources = $this->resourceRepo->findByUserId($userId);
if ($resources->naquadah_crystals < $cost) {
return ServiceResponse::error("Insufficient Naquadah Crystals. Required: {$cost}");
}

if (empty(trim($targetName))) {
return ServiceResponse::error('You must enter a target.');
}

// 2. Transaction
$this->db->beginTransaction();
try {
// Deduct Cost
$this->resourceRepo->updateResources($userId, 0, -$cost);

// Execute Attack via Service with isHidden=true
// We do NOT call processPurchase because AttackService manages its own complex state.
$attackResponse = $this->attackService->conductAttack($userId, $targetName, 'plunder', true);

if (!$attackResponse->isSuccess()) {
throw new \Exception($attackResponse->message);
}

$this->db->commit();
return ServiceResponse::success("Shadow Contract Fulfilled. " . $attackResponse->message);

} catch (Throwable $e) {
$this->db->rollBack();
return ServiceResponse::error($e->getMessage());
}
}

/**
* Helper to handle the "Check funds -> Deduct -> Execute -> Commit" flow.
* Updated to handle array return types from closures for metadata passing.
*/
private function processPurchase(int $userId, float $cost, callable $action): ServiceResponse
{
$resources = $this->resourceRepo->findByUserId($userId);

if ($resources->naquadah_crystals < $cost) {
return ServiceResponse::error("Insufficient Naquadah Crystals. Required: {$cost}");
}

$this->db->beginTransaction();
try {
// Deduct Cost
$this->resourceRepo->updateResources($userId, 0, -$cost);

// Execute Logic
$result = $action();

// Handle both simple string returns and complex array returns
$message = is_array($result) ? ($result['message'] ?? 'Operation successful') : $result;
$status = is_array($result) ? ($result['status'] ?? 'success') : 'success';

$this->db->commit();

// Pass the outcome type in data payload
return ServiceResponse::success($message, ['outcome_type' => $status]);

} catch (Throwable $e) {
$this->db->rollBack();
error_log("Black Market Error: " . $e->getMessage());
return ServiceResponse::error("Transaction failed: " . $e->getMessage());
}
}
}