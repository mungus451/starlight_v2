<?php

namespace App\Models\Services;

use App\Models\Repositories\StatsRepository;
use App\Models\Services\LevelCalculatorService;
use App\Models\Services\DashboardService;
use App\Models\Services\RealmNewsService;
use App\Models\Services\BattleService;
use App\Presenters\DashboardPresenter;

/**
 * Responsible for gathering "Global" data required by the main layout.
 * (e.g., XP Bar, Navbar Stats, Unread Notification counts).
 * 
 * This prevents the BaseController from being coupled to specific domain logic.
 */
class ViewContextService
{
    private StatsRepository $statsRepo;
    private LevelCalculatorService $levelCalculator;
    private DashboardService $dashboardService;
    private DashboardPresenter $dashboardPresenter;
    private RealmNewsService $realmNewsService;
    private BattleService $battleService;

    public function __construct(
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator,
        DashboardService $dashboardService,
        DashboardPresenter $dashboardPresenter,
        RealmNewsService $realmNewsService,
        BattleService $battleService
    ) {
        $this->statsRepo = $statsRepo;
        $this->levelCalculator = $levelCalculator;
        $this->dashboardService = $dashboardService;
        $this->dashboardPresenter = $dashboardPresenter;
        $this->realmNewsService = $realmNewsService;
        $this->battleService = $battleService;
    }

    /**
     * Retrieves global context data for a logged-in user.
     *
     * @param int $userId
     * @return array
     */
    public function getGlobalLayoutData(int $userId): array
    {
        $data = [];
        
        // 1. Fetch RPG Stats for XP Bar
        $stats = $this->statsRepo->findByUserId($userId);
        
        if ($stats) {
            $xpData = $this->levelCalculator->getLevelProgress($stats->experience, $stats->level);
            
            $data['global_xp_data'] = $xpData;
            $data['global_user_level'] = $stats->level;
        }

        // 2. Fetch data for the Advisor Panel
        $data['advisorData'] = $this->getAdvisorData($userId);

        // 3. Fetch latest realm news
        $data['realmNews'] = $this->realmNewsService->getLatestNews();

        // 4. Fetch latest global battles
        $data['latestBattles'] = $this->battleService->getLatestGlobalBattles();

        return $data;
    }

    /**
     * Fetches and prepares all data needed for the Advisor HUD.
     *
     * @param int $userId
     * @return array
     */
    private function getAdvisorData(int $userId): array
    {
        $dashboardData = $this->dashboardService->getDashboardData($userId);
        return $this->dashboardPresenter->present($dashboardData);
    }
}