<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\StatsRepository;
use App\Models\Services\LevelCalculatorService;
use PDO;
use Throwable;

/**
 * Handles all business logic for Leveling Up.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class LevelUpService
{
    private PDO $db;
    private Config $config;
    
    private StatsRepository $statsRepo;
    private LevelCalculatorService $levelCalculator;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param Config $config
     * @param StatsRepository $statsRepo
     * @param LevelCalculatorService $levelCalculator
     */
    public function __construct(
        PDO $db,
        Config $config,
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->statsRepo = $statsRepo;
        $this->levelCalculator = $levelCalculator;
    }

    /**
     * Gets all data needed to render the level up page.
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
     * This is primarily an internal method (called by Attack/Spy services),
     * so it returns ServiceResponse to maintain consistency, even if often ignored.
     *
     * @param int $userId
     * @param int $amount
     * @return ServiceResponse
     */
    public function grantExperience(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::success('No XP gained.');
        }

        $stats = $this->statsRepo->findByUserId($userId);
        if (!$stats) {
            error_log("LevelUpService: User {$userId} not found.");
            return ServiceResponse::error('User stats not found.');
        }

        // Calculate New Totals
        $newExperience = $stats->experience + $amount;
        $newLevel = $this->levelCalculator->calculateLevelFromXp($newExperience);
        
        // Check for Level Up
        $levelsGained = $newLevel - $stats->level;
        $newLevelUpPoints = $stats->level_up_points;
        $message = "Gained {$amount} XP.";

        if ($levelsGained > 0) {
            $pointsPerLevel = 1; 
            $pointsGained = $levelsGained * $pointsPerLevel;
            $newLevelUpPoints += $pointsGained;
            
            // Append level up info to message
            $message .= " Level Up! Reached Level {$newLevel}, +{$pointsGained} SP.";
        }

        try {
            $this->statsRepo->updateLevelProgress($userId, $newExperience, $newLevel, $newLevelUpPoints);
            return ServiceResponse::success($message, [
                'levels_gained' => $levelsGained,
                'new_level' => $newLevel
            ]);
        } catch (Throwable $e) {
            error_log('Grant Experience Error: ' . $e->getMessage());
            return ServiceResponse::error('Database error updating XP.');
        }
    }

    /**
     * Attempts to spend level up points on base stats.
     * 
     * @return ServiceResponse
     */
    public function spendPoints(int $userId, string $statKey, int $amount): ServiceResponse 
    {
        // 1. Validation (Inputs)
        $allowedStats = ['strength', 'constitution', 'wealth', 'dexterity', 'charisma'];
        if (!in_array($statKey, $allowedStats)) {
            return ServiceResponse::error('Invalid stat selected.');
        }

        if ($amount <= 0) {
            return ServiceResponse::error('You must spend at least one point.');
        }

        // 2. Validation (Costs)
        $costPerPoint = $this->config->get('game_balance.level_up.cost_per_point', 1);
        $totalCost = $amount * $costPerPoint;
        
        $stats = $this->statsRepo->findByUserId($userId);

        if ($stats->level_up_points < $totalCost) {
            return ServiceResponse::error('You do not have enough level up points.');
        }

        // 3. Calculate New Totals
        $newLevelUpPoints = $stats->level_up_points - $totalCost;
        $statColumn = "{$statKey}_points"; // E.g., 'strength_points'
        $currentStatValue = $stats->{$statColumn};
        $newStatValue = $currentStatValue + $amount;

        // 4. Execute Update
        try {
            // Assuming a method exists to update a single stat.
            // If not, this would need to be created in StatsRepository.
            $this->statsRepo->updateSingleBaseStat(
                $userId,
                $newLevelUpPoints,
                $statColumn,
                $newStatValue
            );
            
            return ServiceResponse::success("Successfully allocated {$amount} points to " . ucfirst($statKey) . ".");

        } catch (Throwable $e) {
            error_log('Level Up Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred. Please try again.');
        }
    }
}