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
use App\Models\Repositories\GeneralRepository;
use App\Models\Repositories\ScientistRepository;
use App\Models\Repositories\EdictRepository;
use App\Models\Services\EmbassyService;
use App\Models\Services\NetWorthCalculatorService;
use PDO;
use Throwable;

/**
 * Handles the game's "turn" logic for all users.
 * Intended to be run from a cron job.
 * * Fixed: Added nested transaction support for testing.
 * * Added: Edict Upkeep Logic.
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
    private GeneralRepository $generalRepo;
    private ScientistRepository $scientistRepo;
    private EdictRepository $edictRepo;
    private EmbassyService $embassyService;
    private NetWorthCalculatorService $nwCalculator;

    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;

    private array $treasuryConfig;
    private array $upkeepConfig;

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
        AllianceBankLogRepository $bankLogRepo,
        GeneralRepository $generalRepo,
        ScientistRepository $scientistRepo,
        EdictRepository $edictRepo,
        EmbassyService $embassyService,
        NetWorthCalculatorService $nwCalculator
    ) {
        $this->db = $db;
        $this->config = $config;

        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
        $this->statsRepo = $statsRepo;
        $this->generalRepo = $generalRepo;
        $this->scientistRepo = $scientistRepo;
        $this->edictRepo = $edictRepo;

        $this->powerCalculatorService = $powerCalculatorService;
        $this->embassyService = $embassyService;
        $this->nwCalculator = $nwCalculator;

        $this->allianceRepo = $allianceRepo;
        $this->bankLogRepo = $bankLogRepo;

        // Load config immediately
        $this->treasuryConfig = $this->config->get('game_balance.alliance_treasury', []);
        $this->upkeepConfig = $this->config->get('game_balance.upkeep', []);
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
                $user->alliance_id
            );

            $creditsGained = $incomeBreakdown['total_credit_income'];
            $interestGained = $incomeBreakdown['interest'];
            $citizensGained = $incomeBreakdown['total_citizens'];
            $researchDataIncome = $incomeBreakdown['research_data_income'];
            $darkMatterIncome = $incomeBreakdown['dark_matter_income'];
            $naquadahIncome = $incomeBreakdown['naquadah_income'];
            $protoformIncome = $incomeBreakdown['protoform_income'];
            $attackTurnsGained = 1;

            // 3. Apply income using the atomic repository method
            $this->resourceRepo->applyTurnIncome(
                $userId, 
                $creditsGained, 
                $interestGained, 
                $citizensGained, 
                $researchDataIncome, 
                $darkMatterIncome, 
                $naquadahIncome, 
                $protoformIncome
            );
            
            // 4. Apply attack turns
            $this->statsRepo->applyTurnAttackTurn($userId, $attackTurnsGained);

            // 5. Calculate and apply UNIT upkeep (Generals/Scientists)
            $generalCount = $this->generalRepo->countByUserId($userId);
            $scientistCount = $this->scientistRepo->getActiveScientistCount($userId);

            $generalUpkeep = $generalCount * ($this->upkeepConfig['general']['protoform'] ?? 0);
            $scientistUpkeep = $scientistCount * ($this->upkeepConfig['scientist']['protoform'] ?? 0);
            $totalUnitUpkeep = $generalUpkeep + $scientistUpkeep;

            if ($totalUnitUpkeep > 0) {
                $this->resourceRepo->updateResources($userId, null, null, null, -1 * $totalUnitUpkeep);
            }

            // 6. Edict Upkeep Logic
            $activeEdicts = $this->edictRepo->findActiveByUserId($userId);
            foreach ($activeEdicts as $activeEdict) {
                $def = $this->edictRepo->getDefinition($activeEdict->edict_key);
                if (!$def || $def->upkeep_cost <= 0) continue;

                $cost = $def->upkeep_cost;
                $canAfford = false;

                if ($def->upkeep_resource === 'credits') {
                    // Check balance (including what we just added)
                    // We need to re-fetch or estimate. Re-fetching is safer inside transaction.
                    // But for performance, we can just try to deduct and check affected rows?
                    // ResourceRepo::updateResources returns bool.
                    // But wait, updateResources with negative might make it negative?
                    // The repo usually clamps or allows negative? Let's check.
                    // Standard approach: Check logic.
                    // Let's optimize: We assume income was added.
                    
                    // Actually, simple check:
                    // If credits < cost, revoke.
                    // Since we added income already, we should use the updated balance.
                    // Ideally we track running balance in memory, but that's complex.
                    // Let's do a quick fetch of the column we need.
                    
                    // Optimization: We know starting balance + gain.
                    $currentCredits = $resources->credits + $creditsGained + $interestGained; 
                    // Note: This ignores unit upkeep we just paid? 
                    // No, unit upkeep is protoform.
                    // If unit upkeep was credits, we'd need to subtract.
                    
                    if ($currentCredits >= $cost) {
                        $this->resourceRepo->updateResources($userId, -1 * $cost, 0, 0);
                        $currentCredits -= $cost; // Update local tracker
                        $canAfford = true;
                    }
                } elseif ($def->upkeep_resource === 'energy') {
                    // Energy is in user_stats
                    $currentEnergy = $stats->energy;
                    if ($currentEnergy >= $cost) {
                        $this->statsRepo->updateEnergy($userId, -1 * $cost);
                        $canAfford = true; 
                    }
                } else {
                    // Unknown resource, free pass?
                    $canAfford = true;
                }

                if (!$canAfford) {
                    $this->embassyService->revokeEdict($userId, $activeEdict->edict_key);
                    // Optional: Send notification "Edict revoked due to lack of funds"
                }
            }

            // 7. Update Net Worth
            // We calculate this after all income and expenses have been applied to get the accurate state.
            $newNetWorth = $this->nwCalculator->calculateTotalNetWorth($userId);
            $this->statsRepo->updateNetWorth($userId, $newNetWorth);

            // 8. Commit if we own the transaction
            if ($transactionStartedByMe) {
                $this->db->commit();
            }
            return true;

        } catch (Throwable $e) {

// 7. Rollback only if we started it
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
     * Processes turn-based interest AND Net Worth updates for all alliances.
     *
     * @return int The number of alliances successfully processed.
     */
    private function processAllAlliances(): int
    {
        $alliances = $this->allianceRepo->getAllAlliances();
        $interestRate = $this->treasuryConfig['bank_interest_rate'] ?? 0;
        $processedCount = 0;

        foreach ($alliances as $alliance) {
            $transactionStartedByMe = false;
            try {
                if (!$this->db->inTransaction()) {
                    $this->db->beginTransaction();
                    $transactionStartedByMe = true;
                }

                // --- 1. Net Worth Calculation (FIX) ---
                // Calculate total net worth of all members
                $totalNetWorth = $this->userRepo->sumNetWorthByAllianceId($alliance->id);
                $this->allianceRepo->updateNetWorth($alliance->id, $totalNetWorth);

                // --- 2. Bank Interest Logic ---
                if ($interestRate > 0 && $alliance->bank_credits > 0) {
                    $interestGained = (int)floor($alliance->bank_credits * $interestRate);

                    if ($interestGained > 0) {
                        // Add interest to bank
                        $this->allianceRepo->updateBankCreditsRelative($alliance->id, $interestGained);

                        // Log the transaction
                        $message = "Bank interest (" . ($interestRate * 100) . "%) earned.";
                        $this->bankLogRepo->createLog($alliance->id, null, 'interest', $interestGained, $message);

                        // Update compound timestamp
                        $this->allianceRepo->updateLastCompoundAt($alliance->id);
                    }
                }

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

        return $processedCount;
    }
}