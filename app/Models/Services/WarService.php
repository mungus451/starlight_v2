<?php

namespace App\Models\Services;

use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\WarRepository;
use App\Models\Repositories\WarBattleLogRepository;
use App\Models\Repositories\WarHistoryRepository;
use App\Models\Entities\User;
use PDO;

/**
 * Handles all business logic for Alliance Wars.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
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

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param UserRepository $userRepo
     * @param AllianceRepository $allianceRepo
     * @param AllianceRoleRepository $roleRepo
     * @param WarRepository $warRepo
     * @param WarBattleLogRepository $warLogRepo
     * @param WarHistoryRepository $warHistoryRepo
     */
    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        WarRepository $warRepo,
        WarBattleLogRepository $warLogRepo,
        WarHistoryRepository $warHistoryRepo
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
        $this->warRepo = $warRepo;
        $this->warLogRepo = $warLogRepo;
        $this->warHistoryRepo = $warHistoryRepo;
    }

    /**
     * Declares a new war against another alliance.
     *
     * @param int $adminUserId
     * @param int $targetAllianceId
     * @param string $name
     * @param string $casusBelli
     * @param string $goalKey
     * @param int $goalThreshold
     * @return ServiceResponse
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
        return ServiceResponse::success('War has been successfully declared!');
    }

    /**
     * Logs a battle's results into the war system.
     * This is called by WarLoggerListener (Event System), so it returns void.
     *
     * @param int $battleReportId
     * @param User $attacker
     * @param User $defender
     * @param string $attackResult ('victory', 'defeat', 'stalemate')
     * @param int $prestigeGained
     * @param int $unitsKilled (Defender guards lost)
     * @param int $creditsPlundered
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
        // 1. Check if both parties are in alliances
        if ($attacker->alliance_id === null || $defender->alliance_id === null) {
            return;
        }

        // 2. Find the active war
        $war = $this->warRepo->findActiveWarBetween($attacker->alliance_id, $defender->alliance_id);
        if ($war === null) {
            return; // No active war, do nothing
        }

        // 3. Determine scoring
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

        // 4. Create the battle log
        $this->warLogRepo->createLog(
            $war->id,
            $battleReportId,
            $attacker->id,
            $attacker->alliance_id,
            $prestigeGained,
            $unitsKilled,
            $creditsPlundered
        );
        
        // 5. Update the war score if a goal was progressed
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