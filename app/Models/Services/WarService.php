<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\WarHistoryRepository;
use App\Models\Services\NotificationService;
use App\Models\Entities\User;
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

    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        WarRepository $warRepo,
        WarBattleLogRepository $warLogRepo,
        WarHistoryRepository $warHistoryRepo,
        NotificationService $notificationService
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
        $this->warRepo = $warRepo;
        $this->warLogRepo = $warLogRepo;
        $this->warHistoryRepo = $warHistoryRepo;
        $this->notificationService = $notificationService;
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
        $canDeclareWar = ($role && $role->can_declare_war);

        // 3. Fetch Data
        // Fetch alliances for the "Declare War" dropdown (excluding own)
        $allAlliances = $this->allianceRepo->getAllAlliances();
        $otherAlliances = array_filter($allAlliances, function($alliance) use ($allianceId) {
            return $alliance->id !== $allianceId;
        });

        // In a real app, we'd filter these by alliance_id, but for now we return placeholders/global lists
        // as per the original Controller implementation structure.
        // TODO: Add repository methods to fetch specific wars for this alliance if needed.
        $activeWars = []; // Placeholder: $this->warRepo->findActiveWarsByAlliance($allianceId);
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
        string $name,
        string $casusBelli,
        string $goalKey,
        int $goalThreshold
    ): ServiceResponse {
        $adminUser = $this->userRepo->findById($adminUserId);
        if (!$adminUser || $adminUser->alliance_id === null) {
            return ServiceResponse::error('You are not in an alliance.');
        }
        
        $declarerAllianceId = $adminUser->alliance_id;

        // 1. Permission Check
        if (!$this->checkPermission($adminUserId, $declarerAllianceId, 'can_declare_war')) {
            return ServiceResponse::error('You do not have permission to declare war.');
        }

        // 2. Validation
        if ($declarerAllianceId === $targetAllianceId) {
            return ServiceResponse::error('You cannot declare war on your own alliance.');
        }
        if (empty(trim($name))) {
            return ServiceResponse::error('War name cannot be empty.');
        }
        if ($goalThreshold <= 0) {
            return ServiceResponse::error('Goal threshold must be a positive number.');
        }
        
        // 3. Check for existing active war
        $activeWar = $this->warRepo->findActiveWarBetween($declarerAllianceId, $targetAllianceId);
        if ($activeWar) {
            return ServiceResponse::error('There is already an active war between your alliances.');
        }
        
        // 4. Create War
        $this->warRepo->createWar($name, $declarerAllianceId, $targetAllianceId, $casusBelli, $goalKey, $goalThreshold);
        
        // Notify both alliances about the war declaration
        $declaringAlliance = $this->allianceRepo->findById($declarerAllianceId);
        $targetAlliance = $this->allianceRepo->findById($targetAllianceId);
        
        if ($declaringAlliance && $targetAlliance) {
            $this->notificationService->notifyAllianceMembers(
                $targetAllianceId,
                0,
                'War Declared!',
                "Alliance [{$declaringAlliance->tag}] {$declaringAlliance->name} declared war: {$name}",
                "/alliance/war"
            );
            
            $this->notificationService->notifyAllianceMembers(
                $declarerAllianceId,
                $adminUserId,
                'War Declared!',
                "Your alliance declared war against [{$targetAlliance->tag}] {$targetAlliance->name}: {$name}",
                "/alliance/war"
            );
        }
        
        return ServiceResponse::success('War has been successfully declared!');
    }

    /**
     * Logs a battle's results into the war system.
     */
    public function logBattle(
        int $battleReportId,
        User $attacker,
        User $defender,
        string $attackResult,
        int $prestigeGained,
        int $unitsKilled,
        int $creditsPlundered
    ): void {
        if ($attacker->alliance_id === null || $defender->alliance_id === null) {
            return;
        }

        $war = $this->warRepo->findActiveWarBetween($attacker->alliance_id, $defender->alliance_id);
        if ($war === null) {
            return;
        }

        $scoringAllianceId = null;
        $scoreGained = 0;
        $isDeclarer = false;

        if ($attackResult === 'victory') {
            $scoringAllianceId = $attacker->alliance_id;
            
            if ($war->goal_key === 'credits_plundered') {
                $scoreGained = $creditsPlundered;
            } elseif ($war->goal_key === 'units_killed') {
                $scoreGained = $unitsKilled;
            }
            
            $isDeclarer = ($scoringAllianceId === $war->declarer_alliance_id);
        }

        $this->warLogRepo->createLog(
            $war->id,
            $battleReportId,
            $attacker->id,
            $attacker->alliance_id,
            $prestigeGained,
            $unitsKilled,
            $creditsPlundered
        );
        
        if ($scoreGained > 0 && $scoringAllianceId !== null) {
            $this->warRepo->updateWarScore($war->id, $isDeclarer, $scoreGained);
        }
    }

    /**
     * Helper function to check if a user has a specific permission.
     */
    private function checkPermission(int $userId, int $allianceId, string $permissionName): bool
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user || $user->alliance_id !== $allianceId) {
            return false;
        }
        
        $role = $this->roleRepo->findById($user->alliance_role_id);

        return $role && property_exists($role, $permissionName) && $role->{$permissionName} === true;
    }
}