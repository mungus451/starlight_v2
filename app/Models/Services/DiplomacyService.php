<?php

namespace App\Models\Services;

use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\TreatyRepository;
use App\Models\Repositories\RivalryRepository;
use PDO;

/**
 * Handles all business logic for Alliance Diplomacy (Treaties & Rivalries).
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class DiplomacyService
{
    private PDO $db;
    
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;
    private TreatyRepository $treatyRepo;
    private RivalryRepository $rivalryRepo;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param UserRepository $userRepo
     * @param AllianceRepository $allianceRepo
     * @param AllianceRoleRepository $roleRepo
     * @param TreatyRepository $treatyRepo
     * @param RivalryRepository $rivalryRepo
     */
    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        TreatyRepository $treatyRepo,
        RivalryRepository $rivalryRepo
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
        $this->treatyRepo = $treatyRepo;
        $this->rivalryRepo = $rivalryRepo;
    }

    /**
     * Gets all diplomacy data for an alliance.
     */
    public function getDiplomacyData(int $allianceId): array
    {
        $treaties = $this->treatyRepo->findByAllianceId($allianceId);
        $rivalries = $this->rivalryRepo->findByAllianceId($allianceId);
        $allAlliances = $this->allianceRepo->getAllAlliances();

        // Filter out own alliance from target list
        $otherAlliances = array_filter($allAlliances, function($alliance) use ($allianceId) {
            return $alliance->id !== $allianceId;
        });

        return [
            'treaties' => $treaties,
            'rivalries' => $rivalries,
            'otherAlliances' => $otherAlliances
        ];
    }

    /**
     * Proposes a new treaty with another alliance.
     * 
     * @return ServiceResponse
     */
    public function proposeTreaty(int $proposerUserId, int $targetAllianceId, string $treatyType, string $terms): ServiceResponse
    {
        $proposerUser = $this->userRepo->findById($proposerUserId);
        if (!$proposerUser || $proposerUser->alliance_id === null) {
            return ServiceResponse::error('You are not in an alliance.');
        }
        
        $proposerAllianceId = $proposerUser->alliance_id;

        if (!$this->checkPermission($proposerUserId, $proposerAllianceId, 'can_manage_diplomacy')) {
            return ServiceResponse::error('You do not have permission to manage diplomacy.');
        }

        if ($proposerAllianceId === $targetAllianceId) {
            return ServiceResponse::error('You cannot propose a treaty with your own alliance.');
        }
        if (!in_array($treatyType, ['peace', 'non_aggression', 'mutual_defense'])) {
            return ServiceResponse::error('Invalid treaty type.');
        }
        
        $this->treatyRepo->createTreaty($proposerAllianceId, $targetAllianceId, $treatyType, $terms);
        return ServiceResponse::success('Treaty proposed successfully.');
    }

    /**
     * Accepts a treaty proposal.
     * 
     * @return ServiceResponse
     */
    public function acceptTreaty(int $adminUserId, int $treatyId): ServiceResponse
    {
        $adminUser = $this->userRepo->findById($adminUserId);
        $treaty = $this->treatyRepo->findById($treatyId);

        if (!$treaty) {
            return ServiceResponse::error('Treaty not found.');
        }
        
        // Check if user is an admin of the *target* alliance
        if (!$adminUser || $adminUser->alliance_id !== $treaty->alliance2_id) {
            return ServiceResponse::error('You are not authorized to respond to this treaty.');
        }
        
        if (!$this->checkPermission($adminUserId, $adminUser->alliance_id, 'can_manage_diplomacy')) {
            return ServiceResponse::error('You do not have permission to manage diplomacy.');
        }
        
        if ($treaty->status !== 'proposed') {
            return ServiceResponse::error('This treaty is not in a proposed state.');
        }

        $this->treatyRepo->updateTreatyStatus($treatyId, 'active');
        return ServiceResponse::success('Treaty accepted and is now active.');
    }

    /**
     * Declines or breaks a treaty.
     * 
     * @return ServiceResponse
     */
    public function endTreaty(int $adminUserId, int $treatyId, string $action): ServiceResponse
    {
        $adminUser = $this->userRepo->findById($adminUserId);
        $treaty = $this->treatyRepo->findById($treatyId);

        if (!$treaty) {
            return ServiceResponse::error('Treaty not found.');
        }

        // User must be in one of the two alliances
        if (!$adminUser || ($adminUser->alliance_id !== $treaty->alliance1_id && $adminUser->alliance_id !== $treaty->alliance2_id)) {
            return ServiceResponse::error('You are not a part of this treaty.');
        }
        
        if (!$this->checkPermission($adminUserId, $adminUser->alliance_id, 'can_manage_diplomacy')) {
            return ServiceResponse::error('You do not have permission to manage diplomacy.');
        }

        if ($action === 'decline' && $treaty->status === 'proposed') {
            $this->treatyRepo->updateTreatyStatus($treatyId, 'declined');
            return ServiceResponse::success('Treaty proposal declined.');
        } elseif ($action === 'break' && $treaty->status === 'active') {
            $this->treatyRepo->updateTreatyStatus($treatyId, 'broken');
            return ServiceResponse::success('Treaty has been broken.');
        }
        
        return ServiceResponse::error('Invalid action for this treaty\'s state.');
    }

    /**
     * Declares a rivalry (or increases heat).
     * 
     * @return ServiceResponse
     */
    public function declareRivalry(int $userId, int $targetAllianceId): ServiceResponse
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to do this.');
        }
        
        if ($user->alliance_id === $targetAllianceId) {
            return ServiceResponse::error('You cannot declare a rivalry with yourself.');
        }

        $this->rivalryRepo->createOrUpdateRivalry($user->alliance_id, $targetAllianceId);
        return ServiceResponse::success('Rivalry has been declared/updated.');
    }

    /**
     * Helper function to check permissions.
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