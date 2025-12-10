<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Services\ArmoryService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\LevelUpService;
use App\Models\Services\EffectService; // --- NEW ---
use App\Core\Events\EventDispatcher;
use App\Events\BattleConcludedEvent;
use PDO;
use Throwable;

/**
* Handles all business logic for PvP Attacks.
* Updated to support Shadow Contracts and Bounty Claiming.
*/
class AttackService
{
private PDO $db;
private Config $config;

private UserRepository $userRepo;
private ResourceRepository $resourceRepo;
private StructureRepository $structureRepo;
private StatsRepository $statsRepo;
private BattleRepository $battleRepo;
private AllianceRepository $allianceRepo;
private AllianceBankLogRepository $bankLogRepo;
private BountyRepository $bountyRepo;

private ArmoryService $armoryService;
private PowerCalculatorService $powerCalculatorService;
private LevelUpService $levelUpService;
private EventDispatcher $dispatcher;
private EffectService $effectService; // --- NEW ---

public function __construct(
PDO $db,
Config $config,
UserRepository $userRepo,
ResourceRepository $resourceRepo,
StructureRepository $structureRepo,
StatsRepository $statsRepo,
BattleRepository $battleRepo,
AllianceRepository $allianceRepo,
AllianceBankLogRepository $bankLogRepo,
BountyRepository $bountyRepo,
ArmoryService $armoryService,
PowerCalculatorService $powerCalculatorService,
LevelUpService $levelUpService,
EventDispatcher $dispatcher,
EffectService $effectService // --- NEW ---
) {
$this->db = $db;
$this->config = $config;
$this->userRepo = $userRepo;
$this->resourceRepo = $resourceRepo;
$this->structureRepo = $structureRepo;
$this->statsRepo = $statsRepo;
$this->battleRepo = $battleRepo;
$this->allianceRepo = $allianceRepo;
$this->bankLogRepo = $bankLogRepo;
$this->bountyRepo = $bountyRepo;
$this->armoryService = $armoryService;
$this->powerCalculatorService = $powerCalculatorService;
$this->levelUpService = $levelUpService;
$this->dispatcher = $dispatcher;
$this->effectService = $effectService;
}

public function getAttackPageData(int $userId, int $page): array
{
$attackerResources = $this->resourceRepo->findByUserId($userId);
$attackerStats = $this->statsRepo->findByUserId($userId);
$costs = $this->config->get('game_balance.attack', []);

$perPage = $this->config->get('app.leaderboard.per_page', 25);
$totalTargets = $this->statsRepo->getTotalTargetCount($userId);
$totalPages = (int)ceil($totalTargets / $perPage);
$page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
$offset = ($page - 1) * $perPage;

$targets = $this->statsRepo->getPaginatedTargetList($perPage, $offset, $userId);

return [
'attackerResources' => $attackerResources,
'attackerStats' => $attackerStats,
'costs' => $costs,
'targets' => $targets,
'pagination' => [
'currentPage' => $page,
'totalPages' => $totalPages
],
'perPage' => $perPage
];
}

public function getBattleReports(int $userId): array
{
$offensiveReports = $this->battleRepo->findReportsByAttackerId($userId);
$defensiveReports = $this->battleRepo->findReportsByDefenderId($userId);

$allReports = array_merge($offensiveReports, $defensiveReports);

usort($allReports, function($a, $b) {
return $b->created_at <=> $a->created_at;
});

return $allReports;
}

public function getBattleReport(int $reportId, int $viewerId): ?\App\Models\Entities\BattleReport
{
return $this->battleRepo->findReportById($reportId, $viewerId);
}

/**
* Conducts an attack operation.
* Supports optional anonymous attacks (Shadow Contracts).
* Automatically checks for and claims bounties on victory.
*/
public function conductAttack(int $attackerId, string $targetName, string $attackType, bool $isShadowContract = false): ServiceResponse
{
// 1. Validation & Setup
if (empty(trim($targetName))) {
return ServiceResponse::error('You must enter a target.');
}
if ($attackType !== 'plunder') {
return ServiceResponse::error('Invalid attack type.');
}

$defender = $this->userRepo->findByCharacterName($targetName);

if (!$defender) {
return ServiceResponse::error("Character '{$targetName}' not found.");
}
if ($defender->id === $attackerId) {
return ServiceResponse::error('You cannot attack yourself.');
}

// --- Check Active Effects ---
// 1. Defender Shield
if ($this->effectService->hasActiveEffect($defender->id, 'peace_shield')) {
    return ServiceResponse::error("Target is under Safehouse protection. Attack prevented.");
}

// 2. Attacker Shield (Break it)
if ($this->effectService->hasActiveEffect($attackerId, 'peace_shield')) {
    $this->effectService->breakEffect($attackerId, 'peace_shield'); 
}
// ----------------------------

// 2. Get All Data
$attacker = $this->userRepo->findById($attackerId);
$attackerResources = $this->resourceRepo->findByUserId($attackerId);
$attackerStats = $this->statsRepo->findByUserId($attackerId);
$attackerStructures = $this->structureRepo->findByUserId($attackerId);
$defenderResources = $this->resourceRepo->findByUserId($defender->id);
$defenderStats = $this->statsRepo->findByUserId($defender->id);
$defenderStructures = $this->structureRepo->findByUserId($defender->id);

$config = $this->config->get('game_balance.attack');
$treasuryConfig = $this->config->get('game_balance.alliance_treasury');
$xpConfig = $this->config->get('game_balance.xp.rewards');

// 3. Check Costs & Availability
$soldiersSent = $attackerResources->soldiers;
$turnCost = $config['attack_turn_cost'];

if ($soldiersSent <= 0) {
return ServiceResponse::error('You have no soldiers to send.');
}
if ($attackerStats->attack_turns < $turnCost) {
return ServiceResponse::error('You do not have enough attack turns.');
}

// 4. Calculate Battle Power
$offensePowerBreakdown = $this->powerCalculatorService->calculateOffensePower(
$attackerId, $attackerResources, $attackerStats, $attackerStructures, $attacker->alliance_id
);
$offensePower = $offensePowerBreakdown['total'];

$defensePowerBreakdown = $this->powerCalculatorService->calculateDefensePower(
$defender->id, $defenderResources, $defenderStats, $defenderStructures, $defender->alliance_id
);
$defensePower = $defensePowerBreakdown['total'];

// 5. Determine Outcome
$attackResult = 'defeat';
if ($offensePower > $defensePower) {
$attackResult = 'victory';
} elseif ($offensePower == $defensePower) {
$attackResult = 'stalemate';
}

// 6. Calculate Losses & XP
$attackerSoldiersLost = 0;
$defenderGuardsLost = 0;
$casualtyScalar = $config['global_casualty_scalar'] ?? 1.0;

// XP Setup
$attackerXpGain = match($attackResult) {
'victory' => $xpConfig['battle_win'],
'defeat' => $xpConfig['battle_loss'],
'stalemate' => $xpConfig['battle_stalemate'],
default => 0
};
$defenderXpGain = match($attackResult) {
'victory' => $xpConfig['battle_defense_loss'], // Defender lost
'defeat' => $xpConfig['battle_defense_win'], // Defender won
'stalemate' => $xpConfig['battle_defense_win'],
default => 0
};

if ($attackResult === 'victory') {
$attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['winner_loss_percent_max']));
$defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['loser_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
} elseif ($attackResult === 'defeat') {
$attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['loser_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
$defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['winner_loss_percent_max']));
} else { // Stalemate
$attackerSoldiersLost = (int)mt_rand((int)($soldiersSent * $config['winner_loss_percent_min']), (int)($soldiersSent * $config['loser_loss_percent_max']));
$defenderGuardsLost = (int)mt_rand((int)($defenderResources->guards * $config['winner_loss_percent_min']), (int)($defenderResources->guards * $config['loser_loss_percent_max']));
}

$attackerSoldiersLost = (int)ceil($attackerSoldiersLost * $casualtyScalar);
$defenderGuardsLost = (int)ceil($defenderGuardsLost * $casualtyScalar);

$attackerSoldiersLost = min($soldiersSent, $attackerSoldiersLost);
$defenderGuardsLost = min($defenderResources->guards, $defenderGuardsLost);

// 7. Calculate Gains (if 'victory')
$creditsPlundered = 0;
$netWorthStolen = 0;
$warPrestigeGained = 0;
$battleTaxAmount = 0;
$tributeTaxAmount = 0;
$totalTaxAmount = 0;

if ($attackResult === 'victory') {
$creditsPlundered = (int)($defenderResources->credits * $config['plunder_percent']);
$netWorthStolen = (int)($defenderStats->net_worth * $config['net_worth_steal_percent']);
$warPrestigeGained = $config['war_prestige_gain_base'];

if ($attacker->alliance_id !== null && $creditsPlundered > 0) {
$battleTaxAmount = (int)floor($creditsPlundered * $treasuryConfig['battle_tax_rate']);
$tributeTaxAmount = (int)floor($creditsPlundered * $treasuryConfig['tribute_tax_rate']);
$totalTaxAmount = $battleTaxAmount + $tributeTaxAmount;
}
}

$attackerCreditGain = $creditsPlundered - $totalTaxAmount;

// 8. Execute Transaction
$transactionStartedByMe = false;
if (!$this->db->inTransaction()) {
$this->db->beginTransaction();
$transactionStartedByMe = true;
}

try {
// 8a. Update Attacker Resources
$this->resourceRepo->updateBattleAttacker(
$attackerId,
$attackerResources->credits + $attackerCreditGain,
$attackerResources->soldiers - $attackerSoldiersLost
);

// 8b. Update Attacker Stats (XP, Turns, NW, Prestige)
$this->levelUpService->grantExperience($attackerId, $attackerXpGain);
$this->statsRepo->updateBattleAttackerStats(
$attackerId,
$attackerStats->attack_turns - $turnCost,
$attackerStats->net_worth + $netWorthStolen,
$attackerStats->experience + $attackerXpGain,
$attackerStats->war_prestige + $warPrestigeGained
);

// Increment Win/Loss Counter
if ($attackResult === 'victory') {
$this->statsRepo->incrementBattleStats($attackerId, true);
} elseif ($attackResult === 'defeat') {
$this->statsRepo->incrementBattleStats($attackerId, false);
}

// 8c. Update Defender Resources
$this->resourceRepo->updateBattleDefender(
$defender->id,
max(0, $defenderResources->credits - $creditsPlundered),
max(0, $defenderResources->guards - $defenderGuardsLost)
);

// 8d. Update Defender Stats
$this->levelUpService->grantExperience($defender->id, $defenderXpGain);
$this->statsRepo->updateBattleDefenderStats(
$defender->id,
max(0, $defenderStats->net_worth - $netWorthStolen)
);

// 8e. Create Battle Report (Passing isHidden flag)
$battleReportId = $this->battleRepo->createReport(
$attackerId, $defender->id, $attackType, $attackResult, $soldiersSent,
$attackerSoldiersLost, $defenderGuardsLost, $creditsPlundered,
$attackerXpGain, $warPrestigeGained, $netWorthStolen,
(int)$offensePower, (int)$defensePower,
$isShadowContract
);

// 8f. Update Alliance Bank
if ($totalTaxAmount > 0 && $attacker->alliance_id !== null) {
$this->allianceRepo->updateBankCreditsRelative($attacker->alliance_id, $totalTaxAmount);
if ($battleTaxAmount > 0) {
$taxMsg = "Battle tax (" . ($treasuryConfig['battle_tax_rate'] * 100) . "%) from victory against " . $defender->characterName;
$this->bankLogRepo->createLog($attacker->alliance_id, $attackerId, 'battle_tax', $battleTaxAmount, $taxMsg);
}
if ($tributeTaxAmount > 0) {
$tribMsg = "Tribute (" . ($treasuryConfig['tribute_tax_rate'] * 100) . "%) from victory against " . $defender->characterName;
$this->bankLogRepo->createLog($attacker->alliance_id, $attackerId, 'tribute_tax', $tributeTaxAmount, $tribMsg);
}
}

// --- BOUNTY CHECK ---
$bountyMsg = "";
if ($attackResult === 'victory') {
$activeBounty = $this->bountyRepo->findActiveByTargetId($defender->id);
if ($activeBounty) {
// Claim Bounty
$amount = (float)$activeBounty['amount'];
// Add crystals to attacker
$this->resourceRepo->updateResources($attackerId, 0, $amount);
// Close bounty
$this->bountyRepo->claimBounty($activeBounty['id'], $attackerId);

$bountyMsg = " Bounty Claimed! " . number_format($amount) . " Crystals acquired.";
}
}
// --------------------

// 8g. Events
$event = new BattleConcludedEvent(
$battleReportId,
$attacker,
$defender,
$attackResult,
$warPrestigeGained,
$defenderGuardsLost,
$creditsPlundered
);
$this->dispatcher->dispatch($event);

if ($transactionStartedByMe) {
$this->db->commit();
}

} catch (Throwable $e) {
if ($transactionStartedByMe && $this->db->inTransaction()) {
$this->db->rollBack();
}
error_log('Attack Operation Error: '. $e->getMessage());

if (!$transactionStartedByMe) {
throw $e;
}
return ServiceResponse::error('A database error occurred. The attack was cancelled.');
}

// 9. Return Success
$message = "Attack Complete: {$attackResult}!";
if ($attackResult === 'victory') {
$message .= " You plundered " . number_format($creditsPlundered) . " credits.";
}
$message .= " XP Gained: +{$attackerXpGain}.{$bountyMsg}";

return ServiceResponse::success($message);
}
}