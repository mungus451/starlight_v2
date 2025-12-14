<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\PowerCalculatorService;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use PDO;
use Throwable;

/**
* Handles the game's "turn" logic for all users.
* Intended to be run from a cron job.
* * Fixed: Added nested transaction support for testing.
*/
class TurnProcessorService
{
private PDO $db;
private Config $config;

private UserRepository $userRepo;
private ResourceRepository $resourceRepo;
private StructureRepository $structureRepo;
private StatsRepository $statsRepo;
private PowerCalculatorService $powerCalculatorService;

private AllianceRepository $allianceRepo;
private AllianceBankLogRepository $bankLogRepo;

private array $treasuryConfig;

/**
* DI Constructor.
* All dependencies are injected by the Container.
*/
public function __construct(
    PDO $db,
    Config $config,
    UserRepository $userRepo,
    ResourceRepository $resourceRepo,
    StructureRepository $structureRepo,
    StatsRepository $statsRepo,
    PowerCalculatorService $powerCalculatorService,
    AllianceRepository $allianceRepo,
    AllianceBankLogRepository $bankLogRepo
    ) {
        $this->db = $db;
        $this->config = $config;

        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;

        $this->powerCalculatorService = $powerCalculatorService;

        $this->allianceRepo = $allianceRepo;
        $this->bankLogRepo = $bankLogRepo;

        // Load config immediately
        $this->treasuryConfig = $this->config->get('game_balance.alliance_treasury', []);
}

/**
* Processes the turn for every user and alliance in the database.
*
* @return array The number of users and alliances successfully processed.
*/
public function processAllUsers(): array
{
$userIds = $this->userRepo->getAllUserIds();
$processedUserCount = 0;

foreach ($userIds as $userId) {
// We process each user in their own transaction.
// This way, one user failing doesn't stop the whole batch.
if ($this->processTurnForUser($userId)) {
$processedUserCount++;
}
}

// Process all alliances
$processedAllianceCount = $this->processAllAlliances();

return [
'users' => $processedUserCount,
'alliances' => $processedAllianceCount
];
}

/**
* Processes all turn-based income for a single user.
*
* @param int $userId
* @return bool True on success, false on failure.
*/
private function processTurnForUser(int $userId): bool
{
$transactionStartedByMe = false;

try {
// Transaction Safety Check
if (!$this->db->inTransaction()) {
$this->db->beginTransaction();
$transactionStartedByMe = true;
}

// 1. Get all required data
$user = $this->userRepo->findById($userId);
$resources = $this->resourceRepo->findByUserId($userId);
$structures = $this->structureRepo->findByUserId($userId);
$stats = $this->statsRepo->findByUserId($userId);

if (!$user || !$resources || !$structures || !$stats) {
// User might be new or data is missing, skip them.
// If we started the transaction, roll it back to be clean
if ($transactionStartedByMe) {
$this->db->rollBack();
}
return false;
}

// 2. Calculate all income (including alliance structure bonuses)
$incomeBreakdown = $this->powerCalculatorService->calculateIncomePerTurn(
$userId,
$resources,
$stats,
$structures,
$user->alliance_id // Pass alliance ID for bonus calculations
);

        $creditsGained = $incomeBreakdown['total_credit_income'];
        $interestGained = $incomeBreakdown['interest'];
        $citizensGained = $incomeBreakdown['total_citizens'];
        $researchDataIncome = $incomeBreakdown['research_data_income'];
        $darkMatterIncome = $incomeBreakdown['dark_matter_income']; // NEW
        $attackTurnsGained = 1; // Grant 1 attack turn per... turn

        // 3. Apply income using the atomic repository method
        $this->resourceRepo->applyTurnIncome($userId, $creditsGained, $interestGained, $citizensGained, $researchDataIncome, $darkMatterIncome);
// 4. Apply attack turns using the new atomic method
$this->statsRepo->applyTurnAttackTurn($userId, $attackTurnsGained);

// 5. Commit if we own the transaction
if ($transactionStartedByMe) {
$this->db->commit();
}
return true;

} catch (Throwable $e) {
// 6. Rollback only if we started it
if ($transactionStartedByMe && $this->db->inTransaction()) {
$this->db->rollBack();
}
// If we didn't start it, we re-throw so the parent transaction knows something failed
// However, for the cron loop, we might want to suppress and log instead?
// Actually, suppressing here is better for the loop, but for tests re-throwing is better.
// Compromise: Log and return false. The parent loop continues.
error_log("Failed to process turn for user {$userId}: " . $e->getMessage());

// In a test environment, we might want to know.
if (php_sapi_name() === 'cli' && defined('TEST_MODE')) {
throw $e;
}

return false;
}
}

/**
* Processes turn-based interest for all alliances.
*
* @return int The number of alliances successfully processed.
*/
private function processAllAlliances(): int
{
$alliances = $this->allianceRepo->getAllAlliances();
$interestRate = $this->treasuryConfig['bank_interest_rate'] ?? 0;
$processedCount = 0;

if ($interestRate <= 0) {
return 0; // Interest is disabled
}

foreach ($alliances as $alliance) {
// Ensure we have a valid bank balance to calculate on
if ($alliance->bank_credits <= 0) {
continue;
}

$interestGained = (int)floor($alliance->bank_credits * $interestRate);

if ($interestGained > 0) {
$transactionStartedByMe = false;
try {
if (!$this->db->inTransaction()) {
$this->db->beginTransaction();
$transactionStartedByMe = true;
}

// 1. Add interest to bank
$this->allianceRepo->updateBankCreditsRelative($alliance->id, $interestGained);

// 2. Log the transaction
$message = "Bank interest (" . ($interestRate * 100) . "%) earned.";
$this->bankLogRepo->createLog($alliance->id, null, 'interest', $interestGained, $message);

// 3. Update compound timestamp
$this->allianceRepo->updateLastCompoundAt($alliance->id);

if ($transactionStartedByMe) {
$this->db->commit();
}
$processedCount++;

} catch (Throwable $e) {
if ($transactionStartedByMe && $this->db->inTransaction()) {
$this->db->rollBack();
}
error_log("Failed to process turn for alliance {$alliance->id}: " . $e->getMessage());
}
}
}

return $processedCount;
}
}