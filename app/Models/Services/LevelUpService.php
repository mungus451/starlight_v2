<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\StatsRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for spending level up points.
 */
class LevelUpService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private StatsRepository $statsRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        $this->statsRepo = new StatsRepository($this->db);
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
            // --- FIX ---
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
            // --- FIX ---
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