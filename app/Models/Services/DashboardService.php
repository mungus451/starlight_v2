<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
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

    public function __construct()
    {
        $this->db = Database::getInstance();
        
        // Initialize all repositories, passing them the same PDO connection
        $this->userRepository = new UserRepository($this->db);
        $this->resourceRepository = new ResourceRepository($this->db);
        $this->statsRepository = new StatsRepository($this->db);
        $this->structureRepository = new StructureRepository($this->db);
    }

    /**
     * Fetches all data required for the user's dashboard.
     *
     * @param int $userId The ID of the logged-in user
     * @return array An associative array with all dashboard data
     */
    public function getDashboardData(int $userId): array
    {
        // For our clean MVC, we'll make separate calls.
        // In a high-traffic app, this could be one optimized SQL query.
        
        $user = $this->userRepository->findById($userId);
        $resources = $this->resourceRepository->findByUserId($userId);
        $stats = $this->statsRepository->findByUserId($userId);
        $structures = $this->structureRepository->findByUserId($userId);

        // Because our AuthService uses a transaction, we can be confident
        // that if the $user exists, all other data rows will also exist.

        return [
            'user' => $user,
            'resources' => $resources,
            'stats' => $stats,
            'structures' => $structures,
        ];
    }
}