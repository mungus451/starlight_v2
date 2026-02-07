<?php

namespace App\Models\Services;

use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\BattleRepository; // Added
use App\Models\Repositories\EffectRepository;
use App\Models\Repositories\NotificationRepository;
use App\Models\Repositories\BountyRepository;
use App\Models\Services\AdvisorService;
use App\Models\Services\PowerCalculatorService;
use App\Models\Services\NetWorthCalculatorService;

/**
* Orchestrates fetching all data needed for the dashboard.
* * Refactored for Strict Dependency Injection.
* * Updated: Passes Alliance ID to military calculations.
*/
class DashboardService
{
    private UserRepository $userRepository;
    private ResourceRepository $resourceRepository;
    private StatsRepository $statsRepository;
    private StructureRepository $structureRepository;
    private EffectRepository $effectRepo;
    private NotificationRepository $notificationRepo;
    private AdvisorService $advisorService;
    private BountyRepository $bountyRepo;
    private PowerCalculatorService $powerCalculator;
    private NetWorthCalculatorService $nwCalculator;
    private WarRepository $warRepo;
    private BattleRepository $battleRepo; // Added

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
        EffectRepository $effectRepo,
        NotificationRepository $notificationRepo,
        AdvisorService $advisorService,
        BountyRepository $bountyRepo,
        WarRepository $warRepo,
        BattleRepository $battleRepo, // Added
        PowerCalculatorService $powerCalculator,
        NetWorthCalculatorService $nwCalculator
    ) {
        $this->userRepository = $userRepository;
        $this->resourceRepository = $resourceRepository;
        $this->statsRepository = $statsRepository;
        $this->structureRepository = $structureRepository;
        $this->effectRepo = $effectRepo;
        $this->notificationRepo = $notificationRepo;
        $this->advisorService = $advisorService;
        $this->bountyRepo = $bountyRepo;
        $this->warRepo = $warRepo;
        $this->battleRepo = $battleRepo; // Added
        $this->powerCalculator = $powerCalculator;
        $this->nwCalculator = $nwCalculator;
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
        $activeEffects = $this->effectRepo->getAllActiveEffects($userId);
        $criticalAlerts = $this->notificationRepo->getRecent($userId, 3);
        $advisorSuggestions = $this->advisorService->getSuggestions($user);
        
        // Fetch recent battles for the oscilloscope
        $recentBattles = $this->battleRepo->getPaginatedUserBattles($userId, 20, 0);

        // 4. Fetch Threat & Opportunity Data
        $threatData = [
            'rival' => $this->statsRepository->findRivalByNetWorth($stats),
            'highest_bounty' => $this->bountyRepo->getHighestActiveBounty(),
            'active_war' => $user->alliance_id ? $this->warRepo->findActiveWarByAllianceId($user->alliance_id) : null
        ];

        // 2. Get all calculation breakdowns from the injected service
        // Pass alliance_id to ALL calculators to ensure structure bonuses apply

        $incomeBreakdown = $this->powerCalculator->calculateIncomePerTurn(
            $userId,
            $resources,
            $stats,
            $structures,
            $user->alliance_id
        );

        $offenseBreakdown = $this->powerCalculator->calculateOffensePower(
            $userId,
            $resources,
            $stats,
            $structures,
            $user->alliance_id
        );

        $defenseBreakdown = $this->powerCalculator->calculateDefensePower(
            $userId,
            $resources,
            $stats,
            $structures,
            $user->alliance_id
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

        $netWorth = $this->nwCalculator->calculateTotalNetWorth($userId);

        // 3. Return everything in a single array
        return [
            'user' => $user,
            'resources' => $resources,
            'stats' => $stats,
            'structures' => $structures,
            'activeEffects' => $activeEffects,
            'critical_alerts' => $criticalAlerts,
            'advisor_suggestions' => $advisorSuggestions,
            'recent_battles' => $recentBattles, // Added
            'threat_and_opportunity' => $threatData,
            'incomeBreakdown' => $incomeBreakdown,
            'offenseBreakdown' => $offenseBreakdown,
            'defenseBreakdown' => $defenseBreakdown,
            'spyBreakdown' => $spyBreakdown,
            'sentryBreakdown' => $sentryBreakdown,
            'net_worth' => $netWorth
        ];
    }
}