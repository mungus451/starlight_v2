<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\PowerCalculatorService;
// --- NEW REPOSITORIES TO INJECT ---
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use PDO;
use Throwable;

/**
 * Handles the game's "turn" logic for all users.
 * Intended to be run from a cron job.
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
    
    // --- NEW PROPERTIES ---
    private AllianceRepository $allianceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private array $treasuryConfig;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = new Config();
        
        $this->userRepo = new UserRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->structureRepo = new StructureRepository($this->db);
        $this->statsRepo = new StatsRepository($this->db);
        
        // Instantiate services
        $this->powerCalculatorService = new PowerCalculatorService();
        
        // --- NEW REPOSITORIES ---
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->bankLogRepo = new AllianceBankLogRepository($this->db);
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
        
        // --- NEW: Process all alliances ---
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
        $this->db->beginTransaction();
        try {
            // 1. Get all required data
            $resources = $this->resourceRepo->findByUserId($userId);
            $structures = $this->structureRepo->findByUserId($userId);
            $stats = $this->statsRepo->findByUserId($userId);

            if (!$resources || !$structures || !$stats) {
                // User might be new or data is missing, skip them.
                throw new \Exception('User resource, structure, or stats data not found.');
            }

            // 2. Calculate all income (REFACTORED)
            $incomeBreakdown = $this->powerCalculatorService->calculateIncomePerTurn(
                $userId,
                $resources,
                $stats,
                $structures
            );
            
            $creditsGained = $incomeBreakdown['total_credit_income'];
            $interestGained = $incomeBreakdown['interest'];
            $citizensGained = $incomeBreakdown['total_citizens'];
            $attackTurnsGained = 1; // Grant 1 attack turn per... turn

            // 3. Apply income using the atomic repository method
            $this->resourceRepo->applyTurnIncome($userId, $creditsGained, $interestGained, $citizensGained);
            
            // 4. Apply attack turns using the new atomic method
            $this->statsRepo->applyTurnAttackTurn($userId, $attackTurnsGained);

            // 5. Commit this user's transaction
            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            // 6. Rollback on failure for this user
            $this->db->rollBack();
            error_log("Failed to process turn for user {$userId}: " . $e->getMessage());
            return false;
        }
    }
    
    // --- NEW METHOD FOR ALLIANCE INTEREST ---
    
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
            $interestGained = (int)floor($alliance->bank_credits * $interestRate);

            if ($interestGained > 0) {
                $this->db->beginTransaction();
                try {
                    // 1. Add interest to bank
                    $this->allianceRepo->updateBankCreditsRelative($alliance->id, $interestGained);
                    
                    // 2. Log the transaction
                    $message = "Bank interest (" . ($interestRate * 100) . "%) earned.";
                    $this->bankLogRepo->createLog($alliance->id, null, 'interest', $interestGained, $message);
                    
                    // 3. Update compound timestamp
                    $this->allianceRepo->updateLastCompoundAt($alliance->id);
                    
                    $this->db->commit();
                    $processedCount++;
                    
                } catch (Throwable $e) {
                    $this->db->rollBack();
                    error_log("Failed to process turn for alliance {$alliance->id}: " . $e->getMessage());
                }
            }
        }
        
        return $processedCount;
    }
}