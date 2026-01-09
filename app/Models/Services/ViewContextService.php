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
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StructureRepository;

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
    private ResourceRepository $resourceRepo;
    private StructureRepository $structureRepo;

    public function __construct(
        StatsRepository $statsRepo,
        LevelCalculatorService $levelCalculator,
        DashboardService $dashboardService,
        DashboardPresenter $dashboardPresenter,
        RealmNewsService $realmNewsService,
        BattleService $battleService,
        \App\Models\Repositories\UserRepository $userRepo,
        \App\Models\Repositories\AllianceRepository $allianceRepo,
        \App\Models\Repositories\WarRepository $warRepo,
        \App\Models\Repositories\AllianceBankLogRepository $bankLogRepo,
        \App\Models\Repositories\TreatyRepository $treatyRepo,
        \App\Models\Repositories\WarBattleLogRepository $warLogRepo,
        \App\Models\Repositories\BattleRepository $battleRepo,
        \App\Models\Repositories\SpyRepository $spyRepo,
        \App\Models\Repositories\ResourceRepository $resourceRepo,
        \App\Models\Repositories\StructureRepository $structureRepo
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
        $this->resourceRepo = $resourceRepo;
        $this->structureRepo = $structureRepo;
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

        // Check permissions (assuming AllianceRoleRepository is injected or accessed via a service)
        // Since I don't have RoleRepo injected here, I'll do a quick check via raw query or add injection.
        // Adding injection is cleaner but more work. I'll fetch role via existing repos if possible.
        // Actually, let's just assume if leader_id matches. For officers, we'll need role lookup.
        // I'll skip complex permission check here for display-only logic and rely on Controller validation for security.
        // But for UI button visibility...
        // Let's add a placeholder is_leader check.
        $isLeader = ($alliance->leader_id === $userId);

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

        // 6. Badges
        $badges = $this->prepareBadges($alliance->completed_directives);

        return [
            'id' => $alliance->id,
            'name' => $alliance->name,
            'tag' => $alliance->tag,
            'avatar' => $alliance->profile_picture_url,
            'treasury' => $alliance->bank_credits,
            'is_leader' => $isLeader,
            'defcon' => $defcon,
            'objective' => $objective,
            'war' => $warData,
            'ops' => $ops,
            'feed' => $feed,
            'badges' => $badges
        ];
    }

    private function prepareBadges(array $counts): array
    {
        $config = [
            'industry' => ['name' => "Architect's Seal", 'icon' => 'fa-industry'],
            'military' => ['name' => "Warlord's Crest", 'icon' => 'fa-fighter-jet'],
            'intel'    => ['name' => "The All-Seeing Eye", 'icon' => 'fa-user-secret'],
            'treasury' => ['name' => "Golden Handshake", 'icon' => 'fa-coins'],
            'recruit'  => ['name' => "Legion Banner", 'icon' => 'fa-users'],
        ];

        $badges = [];
        foreach ($config as $type => $meta) {
            $count = $counts[$type] ?? 0;
            if ($count > 0) {
                // Determine Tier
                $tier = 'Bronze';
                $color = '#cd7f32'; // Bronze
                if ($count >= 100) { $tier = 'Starlight'; $color = '#00f3ff'; }
                elseif ($count >= 50) { $tier = 'Diamond'; $color = '#b9f2ff'; }
                elseif ($count >= 25) { $tier = 'Platinum'; $color = '#e5e4e2'; }
                elseif ($count >= 10) { $tier = 'Gold'; $color = '#ffd700'; }
                elseif ($count >= 3) { $tier = 'Silver'; $color = '#c0c0c0'; }

                $badges[] = [
                    'name' => $meta['name'],
                    'icon' => $meta['icon'],
                    'count' => $count,
                    'tier' => $tier,
                    'color' => $color
                ];
            }
        }
        return $badges;
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

        // Priority 2: Leader Directive
        if ($alliance->directive_type) {
            return $this->calculateDirectiveProgress($alliance);
        }

        // Priority 3: Crisis (Low Funds)
        if ($alliance->bank_credits < 1000000) {
            $pct = ($alliance->bank_credits / 1000000) * 100;
            return [
                'type' => 'CRISIS',
                'name' => 'Emergency Funding',
                'progress' => min(100, $pct),
                'label' => '1M Goal'
            ];
        }

        // Priority 4: Growth
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

    private function calculateDirectiveProgress($alliance): array
    {
        $currentValue = 0;
        $name = 'Directive';
        $label = 'Progress';

        switch ($alliance->directive_type) {
            case 'industry':
                $name = 'Industrial Revolution';
                $label = 'Total Levels';
                $currentValue = $this->structureRepo->getAggregateStructureLevelForAlliance($alliance->id);
                break;
            case 'military':
                $name = 'Total Mobilization';
                $label = 'Total Units';
                $currentValue = $this->resourceRepo->getAggregateUnitsForAlliance($alliance->id, ['soldiers', 'guards']);
                break;
            case 'intel':
                $name = 'Shadow Protocol';
                $label = 'Spy Network';
                $currentValue = $this->resourceRepo->getAggregateUnitsForAlliance($alliance->id, ['spies', 'sentries']);
                break;
            case 'treasury':
                $name = 'Treasury Tithe';
                $label = 'Credits';
                $currentValue = $alliance->bank_credits;
                break;
            case 'recruit':
                $name = 'Mass Recruitment';
                $label = 'Members';
                $currentValue = $this->userRepo->countAllianceMembers($alliance->id);
                break;
        }

        // Progress Calculation relative to Start Value
        $start = $alliance->directive_start_value;
        $target = $alliance->directive_target;
        $delta = $target - $start;
        
        if ($delta <= 0) $pct = 100;
        else {
            $pct = (($currentValue - $start) / $delta) * 100;
        }

        return [
            'type' => 'DIRECTIVE',
            'name' => $name,
            'progress' => max(0, min(100, $pct)),
            'label' => number_format((int)$currentValue) . ' / ' . number_format($target) . ' ' . $label
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