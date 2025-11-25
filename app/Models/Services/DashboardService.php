<?php

namespace App\Models\Services;

use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Services\PowerCalculatorService;

/**
 * Orchestrates fetching all data needed for the dashboard.
 * * Refactored for Strict Dependency Injection.
 */
class DashboardService
{
    private UserRepository $userRepository;
    private ResourceRepository $resourceRepository;
    private StatsRepository $statsRepository;
    private StructureRepository $structureRepository;
    private PowerCalculatorService $powerCalculator;

    /**
     * DI Constructor.
     *
     * @param UserRepository $userRepository
     * @param ResourceRepository $resourceRepository
     * @param StatsRepository $statsRepository
     * @param StructureRepository $structureRepository
     * @param PowerCalculatorService $powerCalculator
     */
    public function __construct(
        UserRepository $userRepository,
        ResourceRepository $resourceRepository,
        StatsRepository $statsRepository,
        StructureRepository $structureRepository,
        PowerCalculatorService $powerCalculator
    ) {
        $this->userRepository = $userRepository;
        $this->resourceRepository = $resourceRepository;
        $this->statsRepository = $statsRepository;
        $this->structureRepository = $structureRepository;
        $this->powerCalculator = $powerCalculator;
    }

    /**
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

        // 2. Get all calculation breakdowns from the injected service
        $incomeBreakdown = $this->powerCalculator->calculateIncomePerTurn(
            $userId,
            $resources,
            $stats,
            $structures,
            $user->alliance_id  // Pass alliance ID for bonus calculations
        );
        
        $offenseBreakdown = $this->powerCalculator->calculateOffensePower(
            $userId,
            $resources,
            $stats,
            $structures
        );
        
        $defenseBreakdown = $this->powerCalculator->calculateDefensePower(
            $userId,
            $resources,
            $stats,
            $structures
        );
        
        $spyBreakdown = $this->powerCalculator->calculateSpyPower(
            $userId,
            $resources,
            $structures
        );
        
        $sentryBreakdown = $this->powerCalculator->calculateSentryPower(
            $userId,
            $resources,
            $structures
        );

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