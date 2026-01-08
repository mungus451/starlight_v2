<?php

namespace App\Models\Services;

use App\Models\Repositories\StatsRepository;
use App\Models\Services\LevelCalculatorService;
use App\Models\Services\DashboardService;
use App\Models\Services\RealmNewsService;
use App\Models\Services\BattleService;
use App\Presenters\DashboardPresenter;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\WarRepository;

/**
 * Responsible for gathering "Global" data required by the main layout.
 * (e.g., XP Bar, Navbar Stats, Unread Notification counts, Alliance Uplink).
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
    
    // New dependencies for Alliance Uplink
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private WarRepository $warRepo;

    public function __construct(
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator,
        DashboardService $dashboardService,
        DashboardPresenter $dashboardPresenter,
        RealmNewsService $realmNewsService,
        BattleService $battleService,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        WarRepository $warRepo
    ) {
        $this->statsRepo = $statsRepo;
        $this->levelCalculator = $levelCalculator;
        $this->dashboardService = $dashboardService;
        $this->dashboardPresenter = $dashboardPresenter;
        $this->realmNewsService = $realmNewsService;
        $this->battleService = $battleService;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->warRepo = $warRepo;
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

        // 5. Fetch Alliance Uplink Data
        $data['allianceContext'] = $this->getAllianceContext($userId);
        $data['currentUserAllianceId'] = $data['allianceContext']['id'] ?? null;

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

    /**
     * Fetches data for the Alliance Uplink sidebar.
     * Returns null if user is not in an alliance.
     *
     * @param int $userId
     * @return array|null
     */
    private function getAllianceContext(int $userId): ?array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || !$user->alliance_id) {
            return null;
        }

        $alliance = $this->allianceRepo->findById($user->alliance_id);
        if (!$alliance) {
            return null;
        }

        // Check for active war
        $war = $this->warRepo->findActiveWarByAllianceId($alliance->id);
        $warData = null;
        
        if ($war) {
            $isDeclarer = ($war->declarer_alliance_id === $alliance->id);
            $myScore = $isDeclarer ? $war->declarer_score : $war->defender_score;
            $opponentName = $isDeclarer ? $war->defender_name : $war->declarer_name;
            $pct = ($war->goal_threshold > 0) ? ($myScore / $war->goal_threshold) * 100 : 0;
            
            $warData = [
                'opponent' => $opponentName ?? 'Unknown',
                'progress' => min(100, $pct),
                'score' => $myScore,
                'goal' => $war->goal_threshold
            ];
        }

        // Dummy Live Feed Data (Placeholder until real logging system is hooked up)
        // In a real implementation, this would query an 'alliance_logs' table.
        $feed = [
            'System: Uplink established.',
            'Intel: Sector 7 quiet.',
            'Treasury: Daily tax collection pending.',
        ];

        return [
            'id' => $alliance->id,
            'name' => $alliance->name,
            'tag' => $alliance->tag,
            'avatar' => $alliance->profile_picture_url,
            'treasury' => $alliance->bank_credits,
            'war' => $warData,
            'feed' => $feed
        ];
    }
}