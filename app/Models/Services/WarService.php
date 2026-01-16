<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Core\Permissions;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\WarHistoryRepository;
use App\Models\Services\NotificationService;
use App\Models\Entities\User;
use App\Models\Repositories\AllianceStructureRepository;
use App\Core\Config;
use App\Models\Repositories\WarSpyLogRepository;
use PDO;

/**
 * Handles all business logic for Alliance Wars.
 * * Refactored Phase 1.3: Strict MVC Compliance.
 * * Handles authorization and data aggregation internally.
 */
class WarService
{
    private PDO $db;
    
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;
    private WarRepository $warRepo;
    private WarBattleLogRepository $warLogRepo;
    private WarHistoryRepository $warHistoryRepo;
    private NotificationService $notificationService;
    private AllianceStructureRepository $allianceStructureRepo;
    private Config $config;
    private WarSpyLogRepository $warSpyLogRepo; // NEW
    private WarBattleLogRepository $warBattleLogRepo; // NEW

    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        WarRepository $warRepo,
        WarBattleLogRepository $warLogRepo,
        WarHistoryRepository $warHistoryRepo,
        NotificationService $notificationService,
        AllianceStructureRepository $allianceStructureRepo,
        Config $config,
        WarSpyLogRepository $warSpyLogRepo, // NEW
        WarBattleLogRepository $warBattleLogRepo // NEW
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
        $this->warRepo = $warRepo;
        $this->warLogRepo = $warLogRepo;
        $this->warHistoryRepo = $warHistoryRepo;
        $this->notificationService = $notificationService;
        $this->allianceStructureRepo = $allianceStructureRepo;
        $this->config = $config;
        $this->warSpyLogRepo = $warSpyLogRepo; // NEW
        $this->warBattleLogRepo = $warBattleLogRepo; // NEW
    }

    /**
     * Retrieves all data required for the War Room view.
     * Handles permissions internally.
     *
     * @param int $userId
     * @return ServiceResponse
     */
    public function getWarPageData(int $userId): ServiceResponse
    {
        // 1. Validate User & Alliance
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to access the War Room.');
        }
        $allianceId = $user->alliance_id;

        // 2. Check Permissions
        $role = $this->roleRepo->findById($user->alliance_role_id);
        $canDeclareWar = ($role && $role->hasPermission(Permissions::CAN_DECLARE_WAR));

        // 3. Fetch Data
        // Fetch alliances for the "Declare War" dropdown (excluding own)
        $allAlliances = $this->allianceRepo->getAllAlliances();
        $otherAlliances = array_filter($allAlliances, function($alliance) use ($allianceId) {
            return $alliance->id !== $allianceId;
        });

        // In a real app, we'd filter these by alliance_id, but for now we return placeholders/global lists
        // as per the original Controller implementation structure.
        $activeWar = $this->warRepo->findActiveWarByAllianceId($allianceId);
        $activeWars = $activeWar ? [$activeWar] : [];
        $historicalWars = []; // Placeholder: $this->warHistoryRepo->findByAlliance($allianceId);

        return ServiceResponse::success('Data retrieved', [
            'viewer' => $user,
            'canDeclareWar' => $canDeclareWar,
            'allianceId' => $allianceId,
            'otherAlliances' => $otherAlliances,
            'activeWars' => $activeWars,
            'historicalWars' => $historicalWars
        ]);
    }

    /**
     * Declares a new war against another alliance.
     */
    public function declareWar(
        int $adminUserId,
        int $targetAllianceId,
        string $casusBelli
    ): ServiceResponse {
        $adminUser = $this->userRepo->findById($adminUserId);
        if (!$adminUser || $adminUser->alliance_id === null) {
            return ServiceResponse::error('You are not in an alliance.');
        }
        
        $declarerAllianceId = $adminUser->alliance_id;

        // 1. Permission Check
        $role = $this->roleRepo->findById($adminUser->alliance_role_id);
        if (!$role || !$role->hasPermission(Permissions::CAN_DECLARE_WAR)) {
            return ServiceResponse::error('You do not have permission to declare war.');
        }

        // 2. Validation
        if ($declarerAllianceId === $targetAllianceId) {
            return ServiceResponse::error('You cannot declare war on your own alliance.');
        }
        
        // 3. Check for existing active war
        $activeWar = $this->warRepo->findActiveWarBetween($declarerAllianceId, $targetAllianceId);
        if ($activeWar) {
            return ServiceResponse::error('There is already an active war between your alliances.');
        }
        
        // 4. Create War - Generate a generic name
        $declaringAlliance = $this->allianceRepo->findById($declarerAllianceId);
        $targetAlliance = $this->allianceRepo->findById($targetAllianceId);

        if (!$declaringAlliance || !$targetAlliance) {
            return ServiceResponse::error('Could not find one of the alliances.');
        }

        $genericName = "War of [{$declaringAlliance->tag}] vs [{$targetAlliance->tag}]";
        $this->warRepo->createWar($genericName, $declarerAllianceId, $targetAllianceId, $casusBelli);
        
        // Designate strategic targets for the new war
        $this->designateStrategicTargets($declarerAllianceId, $targetAllianceId);
        
        // Notify both alliances about the war declaration
        $declaringAlliance = $this->allianceRepo->findById($declarerAllianceId);
        $targetAlliance = $this->allianceRepo->findById($targetAllianceId);
        
        if ($declaringAlliance && $targetAlliance) {
            $this->notificationService->notifyAllianceMembers(
                $targetAllianceId,
                0,
                'War Declared!',
                "Alliance [{$declaringAlliance->tag}] {$declaringAlliance->name} declared war on you!",
                "/alliance/war"
            );
            
            $this->notificationService->notifyAllianceMembers(
                $declarerAllianceId,
                $adminUserId,
                'War Declared!',
                "Your alliance declared war against [{$targetAlliance->tag}] {$targetAlliance->name}.",
                "/alliance/war"
            );
        }
        
        return ServiceResponse::success('War has been successfully declared!');
    }

    /**
     * Designates strategic targets for both alliances at the start of a war.
     *
     * @param int $alliance1Id
     * @param int $alliance2Id
     * @return void
     */
    public function designateStrategicTargets(int $alliance1Id, int $alliance2Id): void
    {
        $eligible_targets = $this->config->get('game_balance.war.eligible_strategic_targets');
        $min_level = $this->config->get('game_balance.war.min_level_for_strategic_targets');

        // Designate for Alliance 1
        $this->designateTargetForAlliance($alliance1Id, $eligible_targets, $min_level);

        // Designate for Alliance 2
        $this->designateTargetForAlliance($alliance2Id, $eligible_targets, $min_level);
    }

    /**
     * Helper function to select and flag a strategic target for a single alliance.
     *
     * @param int $allianceId
     * @param array $eligible_targets
     * @param int $min_level
     * @return void
     */
    private function designateTargetForAlliance(int $allianceId, array $eligible_targets, int $min_level): void
    {
        $structures = $this->allianceStructureRepo->findEligibleWarObjectives($allianceId, $eligible_targets, $min_level);

        if (empty($structures)) {
            // No eligible structures, log or handle as needed
            return;
        }

        // Randomly select one structure to be the objective
        $targetStructure = $structures[array_rand($structures)];
        $this->allianceStructureRepo->setAsWarObjective($targetStructure->id);
    }

    /**
     * Logs points for a destroyed strategic objective.
     *
     * @param int $warId
     * @param int $winningAllianceId
     * @return void
     */
    public function logObjectivePoints(int $warId, int $winningAllianceId): void
    {
        $points = $this->config->get('game_balance.war.strategic_objective_points', 0);
        if ($points <= 0) {
            return;
        }
        
        $war = $this->warRepo->findById($warId);
        if (!$war) {
            return;
        }

        $isDeclarer = ($winningAllianceId === $war->declarer_alliance_id);
        $this->warRepo->updateWarScore($war->id, $isDeclarer, $points);

        // TODO: Create a nice war log message for this event
    }

    /**
     * Retrieves all data required for the War Dashboard view.
     *
     * @param int $warId
     * @param int $viewerAllianceId
     * @return ServiceResponse
     */
    public function getWarDashboardData(int $warId, int $viewerAllianceId): ServiceResponse
    {
        $war = $this->warRepo->findById($warId);
        if (!$war) {
            return ServiceResponse::error('War not found.');
        }

        // Ensure the viewer's alliance is part of this war
        if ($war->declarer_alliance_id !== $viewerAllianceId && $war->declared_against_alliance_id !== $viewerAllianceId) {
            return ServiceResponse::error('You are not a participant in this war.');
        }

        // Determine sides
        $isViewerDeclarer = ($war->declarer_alliance_id === $viewerAllianceId);
        $yourAllianceId = $viewerAllianceId;
        $opponentAllianceId = $isViewerDeclarer ? $war->declared_against_alliance_id : $war->declarer_alliance_id;

        $yourAlliance = $this->allianceRepo->findById($yourAllianceId);
        $opponentAlliance = $this->allianceRepo->findById($opponentAllianceId);

        if (!$yourAlliance || !$opponentAlliance) {
            return ServiceResponse::error('Could not load alliance data.');
        }

        // --- Overview Tab Data ---
        $warAggregates = $this->warBattleLogRepo->getWarAggregates($warId);
        $yourStrategicTargets = $this->allianceStructureRepo->findByAllianceId($yourAllianceId);
        $opponentStrategicTargets = $this->allianceStructureRepo->findByAllianceId($opponentAllianceId);

        $overviewData = [
            'war' => $war,
            'yourAlliance' => $yourAlliance,
            'opponentAlliance' => $opponentAlliance,
            'warAggregates' => $warAggregates,
            'yourStrategicTargets' => array_filter($yourStrategicTargets, fn($s) => $s->is_war_objective),
            'opponentStrategicTargets' => array_filter($opponentStrategicTargets, fn($s) => $s->is_war_objective),
        ];

        // --- Battle Log Tab Data (Placeholder for pagination) ---
        $battleLogs = $this->warBattleLogRepo->findByWarId($warId, 20, 0);
        $totalBattleLogs = $this->warBattleLogRepo->countByWarId($warId);

        // --- Intel & Espionage Tab Data (Placeholder for pagination) ---
        $spyLogs = $this->warSpyLogRepo->findByWarId($warId, 20, 0);
        $totalSpyLogs = $this->warSpyLogRepo->countByWarId($warId);

        // --- Performance Leaderboard Tab Data ---
        $leaderboardData = [
            'your_alliance' => [
                'vanguard' => $this->warBattleLogRepo->getTopPerformers($warId, $yourAllianceId, 'structure_damage'),
                'reapers' => $this->warBattleLogRepo->getTopPerformers($warId, $yourAllianceId, 'units_killed'),
                'marauders' => $this->warBattleLogRepo->getTopPerformers($warId, $yourAllianceId, 'credits_plundered'),
                'phantoms' => $this->warSpyLogRepo->getTopSpies($warId, $yourAllianceId),
            ],
            'opponent_alliance' => [
                'vanguard' => $this->warBattleLogRepo->getTopPerformers($warId, $opponentAllianceId, 'structure_damage'),
                'reapers' => $this->warBattleLogRepo->getTopPerformers($warId, $opponentAllianceId, 'units_killed'),
                'marauders' => $this->warBattleLogRepo->getTopPerformers($warId, $opponentAllianceId, 'credits_plundered'),
                'phantoms' => $this->warSpyLogRepo->getTopSpies($warId, $opponentAllianceId),
            ]
        ];

        return ServiceResponse::success('War Dashboard Data', [
            'overview' => $overviewData,
            'battleLogs' => [
                'logs' => $battleLogs,
                'total' => $totalBattleLogs
            ],
            'spyLogs' => [
                'logs' => $spyLogs,
                'total' => $totalSpyLogs
            ],
            'leaderboard' => $leaderboardData,
        ]);
    }
}
