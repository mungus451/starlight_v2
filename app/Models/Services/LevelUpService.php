<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Session;
use App\Models\Repositories\StatsRepository;
use App\Models\Services\LevelCalculatorService;
use PDO;
use Throwable;

/**
 * Handles all business logic for Leveling Up:
 * - Spending points
 * - Granting experience
 * - Calculating level gains
 * * Refactored for Strict Dependency Injection.
 */
class LevelUpService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    
    private StatsRepository $statsRepo;
    private LevelCalculatorService $levelCalculator;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Session $session
     * @param Config $config
     * @param StatsRepository $statsRepo
     * @param LevelCalculatorService $levelCalculator
     */
    public function __construct(
        PDO $db,
        Session $session,
        Config $config,
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->config = $config;
        $this->statsRepo = $statsRepo;
        $this->levelCalculator = $levelCalculator;
    }

    /**
     * Gets all data needed to render the level up page.
     *
     * @param int $userId
     * @return array
     */
    public function getLevelUpData(int $userId): array
    {
        $stats = $this->statsRepo->findByUserId($userId);
        $costs = $this->config->get('game_balance.level_up', []);

        return [
            'stats' => $stats,
            'costs' => $costs
        ];
    }

    /**
     * Grants experience points to a user and handles level-ups.
     *
     * @param int $userId
     * @param int $amount
     * @return bool True on success
     */
    public function grantExperience(int $userId, int $amount): bool
    {
        if ($amount <= 0) {
            return true; // No change needed, technically a success
        }

        // 1. Get Current Stats
        $stats = $this->statsRepo->findByUserId($userId);
        if (!$stats) {
            error_log("LevelUpService: User {$userId} not found.");
            return false;
        }

        // 2. Calculate New Totals
        $newExperience = $stats->experience + $amount;
        $newLevel = $this->levelCalculator->calculateLevelFromXp($newExperience);
        
        // 3. Check for Level Up
        $levelsGained = $newLevel - $stats->level;
        $newLevelUpPoints = $stats->level_up_points;

        if ($levelsGained > 0) {
            // Define points per level (standard 1 point per level gained)
            $pointsPerLevel = 1; 
            $pointsGained = $levelsGained * $pointsPerLevel;
            $newLevelUpPoints += $pointsGained;

            // Set a flash message for the UI
            $this->session->setFlash('success', "Level Up! You reached Level {$newLevel} and gained {$pointsGained} Skill Points.");
        }

        // 4. Persist Changes
        try {
            $this->statsRepo->updateLevelProgress($userId, $newExperience, $newLevel, $newLevelUpPoints);
            return true;
        } catch (Throwable $e) {
            error_log('Grant Experience Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Attempts to spend level up points on base stats.
     *
     * @param int $userId
     * @param int $spendStrength
     * @param int $spendConstitution
     * @param int $spendWealth
     * @param int $spendDexterity
     * @param int $spendCharisma
     * @return bool True on success
     */
    public function spendPoints(
        int $userId,
        int $spendStrength,
        int $spendConstitution,
        int $spendWealth,
        int $spendDexterity,
        int $spendCharisma
    ): bool {
        // --- 1. Validation (Inputs) ---
        if ($spendStrength < 0 || $spendConstitution < 0 || $spendWealth < 0 || $spendDexterity < 0 || $spendCharisma < 0) {
            $this->session->setFlash('error', 'You cannot spend a negative number of points.');
            return false;
        }
        
        $totalPointsToSpend = $spendStrength + $spendConstitution + $spendWealth + $spendDexterity + $spendCharisma;

        if ($totalPointsToSpend === 0) {
            $this->session->setFlash('error', 'You did not allocate any points to spend.');
            return false;
        }

        // --- 2. Validation (Costs) ---
        $costPerPoint = $this->config->get('game_balance.level_up.cost_per_point', 1);
        $totalCost = $totalPointsToSpend * $costPerPoint;
        
        $stats = $this->statsRepo->findByUserId($userId);

        if ($stats->level_up_points < $totalCost) {
            $this->session->setFlash('error', 'You do not have enough level up points to make this change.');
            return false;
        }

        // --- 3. Calculate New Totals ---
        $newLevelUpPoints = $stats->level_up_points - $totalCost;
        $newStrength = $stats->strength_points + $spendStrength;
        $newConstitution = $stats->constitution_points + $spendConstitution;
        $newWealth = $stats->wealth_points + $spendWealth;
        $newDexterity = $stats->dexterity_points + $spendDexterity;
        $newCharisma = $stats->charisma_points + $spendCharisma;

        // --- 4. Execute Transaction ---
        $this->db->beginTransaction();
        try {
            $this->statsRepo->updateBaseStats(
                $userId,
                $newLevelUpPoints,
                $newStrength,
                $newConstitution,
                $newWealth,
                $newDexterity,
                $newCharisma
            );

            $this->db->commit();
            $this->session->setFlash('success', 'You have successfully allocated ' . $totalPointsToSpend . ' points.');
            return true;

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Level Up Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred. Please try again.');
            return false;
        }
    }
}