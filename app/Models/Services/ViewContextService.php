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
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\TreatyRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\BattleRepository;
use App\Models\Repositories\SpyRepository;

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
    private AllianceBankLogRepository $bankLogRepo;
    private TreatyRepository $treatyRepo;
    private WarBattleLogRepository $warLogRepo;
    private BattleRepository $battleRepo;
    private SpyRepository $spyRepo;

    public function __construct(
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator,
        DashboardService $dashboardService,
        DashboardPresenter $dashboardPresenter,
        RealmNewsService $realmNewsService,
        BattleService $battleService,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        WarRepository $warRepo,
        AllianceBankLogRepository $bankLogRepo,
        TreatyRepository $treatyRepo,
        WarBattleLogRepository $warLogRepo,
        BattleRepository $battleRepo,
        SpyRepository $spyRepo
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
        $this->bankLogRepo = $bankLogRepo;
        $this->treatyRepo = $treatyRepo;
        $this->warLogRepo = $warLogRepo;
        $this->battleRepo = $battleRepo;
        $this->spyRepo = $spyRepo;
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

        // 1. Check for active war
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

        // 2. Calculate DEFCON
        $defcon = $this->calculateDefcon($alliance->id);

        // 3. Determine Objective
        $objective = $this->determineObjective($alliance, $war);

        // 4. Build Live Feed
        $feed = $this->getLiveFeed($alliance->id);

        // 5. Active Ops (Treaties, Tax, etc)
        $ops = [];
        if ($warData) {
            $ops[] = "WAR: Engagement Active";
        }
        // Placeholder for real treaty logic
        //$treaties = $this->treatyRepo->findActiveByAllianceId($alliance->id);
        //foreach($treaties as $t) $ops[] = "Treaty: {$t->partner_name} ...";
        if (empty($ops)) {
            $ops[] = "Routine Operations";
        }

        return [
            'id' => $alliance->id,
            'name' => $alliance->name,
            'tag' => $alliance->tag,
            'avatar' => $alliance->profile_picture_url,
            'treasury' => $alliance->bank_credits,
            'defcon' => $defcon,
            'objective' => $objective,
            'war' => $warData,
            'ops' => $ops,
            'feed' => $feed
        ];
    }

    private function calculateDefcon(int $allianceId): int
    {
        // 1. Fetch Latest Incidents (Returns raw array with 'seconds_ago')
        $lastBattle = $this->battleRepo->findLatestDefenseByAlliance($allianceId);
        $lastSpy = $this->spyRepo->findLatestDefenseByAlliance($allianceId);
        
        // 2. Determine Most Recent Event
        $secondsSince = null;
        $isSuccess = false; // Was the hostile action successful?
        
        // Check Battle
        if ($lastBattle) {
            $secondsSince = (int)$lastBattle['seconds_ago'];
            // 'victory' means Attacker won -> Successful Incident
            $isSuccess = ($lastBattle['attack_result'] === 'victory');
        }
        
        // Check Spy (Compare recency if both exist)
        if ($lastSpy) {
            $spySeconds = (int)$lastSpy['seconds_ago'];
            if ($secondsSince === null || $spySeconds < $secondsSince) {
                $secondsSince = $spySeconds;
                // 'success' means Spy won -> Successful Incident
                $isSuccess = ($lastSpy['operation_result'] === 'success');
            }
        }
        
        // If no events ever, we are safe
        if ($secondsSince === null) {
            return 5;
        }
        
        // 3. Determine Base Level
        // Success = DEFCON 1 (Severe)
        // Failure = DEFCON 3 (Elevated, but capped)
        $baseLevel = $isSuccess ? 1 : 3;
        
        // 4. Calculate Recovery (Time Decay)
        // 2 hours = +1 Level
        // Ensure secondsSince is not negative (future date safety)
        $secondsSince = max(0, $secondsSince);
        $hoursSince = $secondsSince / 3600;
        $recoveryLevels = floor($hoursSince / 2);
        
        // 5. Final Calculation
        $currentLevel = $baseLevel + (int)$recoveryLevels;
        
        return min(5, $currentLevel);
    }

    private function determineObjective($alliance, $war): array
    {
        // Priority 1: War
        if ($war) {
            $isDeclarer = ($war->declarer_alliance_id === $alliance->id);
            $myScore = $isDeclarer ? $war->declarer_score : $war->defender_score;
            $pct = ($war->goal_threshold > 0) ? ($myScore / $war->goal_threshold) * 100 : 0;
            
            return [
                'type' => 'WAR EFFORT',
                'name' => 'Operation: Victory',
                'progress' => min(100, $pct),
                'label' => 'Score Goal'
            ];
        }

        // Priority 2: Crisis (Low Funds)
        if ($alliance->bank_credits < 1000000) {
            $pct = ($alliance->bank_credits / 1000000) * 100;
            return [
                'type' => 'CRISIS',
                'name' => 'Emergency Funding',
                'progress' => min(100, $pct),
                'label' => '1M Goal'
            ];
        }

        // Priority 3: Growth
        // Next milestone: 10M, 50M, 100M, 1B
        $milestones = [10000000, 50000000, 100000000, 1000000000];
        $target = 1000000000;
        foreach ($milestones as $m) {
            if ($alliance->bank_credits < $m) {
                $target = $m;
                break;
            }
        }
        $pct = ($alliance->bank_credits / $target) * 100;
        
        return [
            'type' => 'GROWTH',
            'name' => 'Treasury Expansion',
            'progress' => min(100, $pct),
            'label' => number_format($target / 1000000) . 'M Goal'
        ];
    }

    private function getLiveFeed(int $allianceId): array
    {
        // In a real app, union query: Bank Logs + Battle Logs + War Logs
        // For now, fetch recent bank logs
        $logs = [];
        
        $bankLogs = $this->bankLogRepo->findLogsByAllianceId($allianceId, 5);
        foreach ($bankLogs as $l) {
            $logs[] = [
                'type' => 'BANK',
                'text' => ($l->character_name ?? 'System') . " " . ($l->amount >= 0 ? 'deposited' : 'withdrew') . " " . number_format(abs($l->amount)) . " Cr",
                'time' => strtotime($l->created_at)
            ];
        }
        
        // Sort by time desc
        usort($logs, fn($a, $b) => $b['time'] <=> $a['time']);
        
        return array_slice($logs, 0, 10);
    }
}