<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Services\PowerCalculatorService; // Import the new service
use PDO;

/**
 * Orchestrates fetching all data needed for the dashboard.
 */
class DashboardService
{
    private PDO $db;
    private UserRepository $userRepository;
    private ResourceRepository $resourceRepository;
    private StatsRepository $statsRepository;
    private StructureRepository $structureRepository;
    private PowerCalculatorService $powerCalculator; // Add new service property

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Initialize all repositories, passing them the same PDO connection
        $this->userRepository = new UserRepository($this->db);
        $this->resourceRepository = new ResourceRepository($this->db);
        $this->statsRepository = new StatsRepository($this->db);
        $this->structureRepository = new StructureRepository($this->db);

        // Initialize the calculator service
        $this->powerCalculator = new PowerCalculatorService();
    }

    /**
     * --- MODIFIED METHOD ---
     * Fetches all data required for the user's dashboard,
     * including detailed calculation breakdowns.
     *
     * @param int $userId The ID of the logged-in user
     * @return array An associative array with all dashboard data
     */
    public function getDashboardData(int $userId): array
    {
        // 1. Fetch all core data entities
        $user = $this->userRepository->findById($userId);
        $resources = $this->resourceRepository->findByUserId($userId);
        $stats = $this->statsRepository->findByUserId($userId);
        $structures = $this->structureRepository->findByUserId($userId);

        // 2. Get all calculation breakdowns from the new service
        $incomeBreakdown = $this->powerCalculator->calculateIncomePerTurn($resources, $structures);
        
        $offenseBreakdown = $this->powerCalculator->calculateOffensePower($userId, $resources, $stats, $structures);
        
        $defenseBreakdown = $this->powerCalculator->calculateDefensePower($userId, $resources, $stats, $structures);
        
        $spyBreakdown = $this->powerCalculator->calculateSpyPower($userId, $resources, $structures);
        
        $sentryBreakdown = $this->powerCalculator->calculateSentryPower($userId, $resources, $structures);

        // 3. Return everything in a single array
        return [
            'user' => $user,
            'resources' => $resources,
            'stats' => $stats,
            'structures' => $structures,
            'incomeBreakdown' => $incomeBreakdown,
            'offenseBreakdown' => $offenseBreakdown,
            'defenseBreakdown' => $defenseBreakdown,
            'spyBreakdown' => $spyBreakdown,
            'sentryBreakdown' => $sentryBreakdown,
        ];
    }
}