<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\PowerCalculatorService; // Import new service
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
    private PowerCalculatorService $powerCalculatorService; // Add new service property

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->config = new Config();
        
        $this->userRepo = new UserRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->structureRepo = new StructureRepository($this->db);
        $this->statsRepo = new StatsRepository($this->db);
        
        // Instantiate services
        $this->powerCalculatorService = new PowerCalculatorService(); // Instantiate new service
    }

    /**
     * Processes the turn for every user in the database.
     *
     * @return int The number of users successfully processed.
     */
    public function processAllUsers(): int
    {
        $userIds = $this->userRepo->getAllUserIds();
        $processedCount = 0;

        foreach ($userIds as $userId) {
            // We process each user in their own transaction.
            // This way, one user failing doesn't stop the whole batch.
            if ($this->processTurnForUser($userId)) {
                $processedCount++;
            }
        }
        
        return $processedCount;
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

            if (!$resources || !$structures) {
                // User might be new or data is missing, skip them.
                throw new \Exception('User resource or structure data not found.');
            }

            // 2. Calculate all income (REFACTORED)
            $incomeBreakdown = $this->powerCalculatorService->calculateIncomePerTurn(
                $resources,
                $structures
            );
            
            $creditsGained = $incomeBreakdown['base_credits'];
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
}