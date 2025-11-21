<?php

namespace App\Models\Services;

use App\Core\Session;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\TreatyRepository;
use App\Models\Repositories\RivalryRepository;
use PDO;
use Throwable;

/**
 * Handles all business logic for Alliance Diplomacy (Treaties & Rivalries).
 * * Refactored for Strict Dependency Injection.
 */
class DiplomacyService
{
    private PDO $db;
    private Session $session;
    
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;
    private TreatyRepository $treatyRepo;
    private RivalryRepository $rivalryRepo;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Session $session
     * @param UserRepository $userRepo
     * @param AllianceRepository $allianceRepo
     * @param AllianceRoleRepository $roleRepo
     * @param TreatyRepository $treatyRepo
     * @param RivalryRepository $rivalryRepo
     */
    public function __construct(
        PDO $db,
        Session $session,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        TreatyRepository $treatyRepo,
        RivalryRepository $rivalryRepo
    ) {
        $this->db = $db;
        $this->session = $session;
        
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
        $this->treatyRepo = $treatyRepo;
        $this->rivalryRepo = $rivalryRepo;
    }

    /**
     * Gets all diplomacy data for an alliance.
     *
     * @param int $allianceId
     * @return array
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
     */
    public function proposeTreaty(int $proposerUserId, int $targetAllianceId, string $treatyType, string $terms): bool
    {
        $proposerUser = $this->userRepo->findById($proposerUserId);
        if (!$proposerUser || $proposerUser->alliance_id === null) {
            $this->session->setFlash('error', 'You are not in an alliance.');
            return false;
        }
        
        $proposerAllianceId = $proposerUser->alliance_id;

        if (!$this->checkPermission($proposerUserId, $proposerAllianceId, 'can_manage_diplomacy')) {
            $this->session->setFlash('error', 'You do not have permission to manage diplomacy.');
            return false;
        }

        if ($proposerAllianceId === $targetAllianceId) {
            $this->session->setFlash('error', 'You cannot propose a treaty with your own alliance.');
            return false;
        }
        if (!in_array($treatyType, ['peace', 'non_aggression', 'mutual_defense'])) {
            $this->session->setFlash('error', 'Invalid treaty type.');
            return false;
        }
        
        $this->treatyRepo->createTreaty($proposerAllianceId, $targetAllianceId, $treatyType, $terms);
        $this->session->setFlash('success', 'Treaty proposed successfully.');
        return true;
    }

    /**
     * Accepts a treaty proposal.
     */
    public function acceptTreaty(int $adminUserId, int $treatyId): bool
    {
        $adminUser = $this->userRepo->findById($adminUserId);
        $treaty = $this->treatyRepo->findById($treatyId);

        if (!$treaty) {
            $this->session->setFlash('error', 'Treaty not found.');
            return false;
        }
        
        // Check if user is an admin of the *target* alliance
        if (!$adminUser || $adminUser->alliance_id !== $treaty->alliance2_id) {
            $this->session->setFlash('error', 'You are not authorized to respond to this treaty.');
            return false;
        }
        
        if (!$this->checkPermission($adminUserId, $adminUser->alliance_id, 'can_manage_diplomacy')) {
            $this->session->setFlash('error', 'You do not have permission to manage diplomacy.');
            return false;
        }
        
        if ($treaty->status !== 'proposed') {
            $this->session->setFlash('error', 'This treaty is not in a proposed state.');
            return false;
        }

        $this->treatyRepo->updateTreatyStatus($treatyId, 'active');
        $this->session->setFlash('success', 'Treaty accepted and is now active.');
        return true;
    }

    /**
     * Declines or breaks a treaty.
     */
    public function endTreaty(int $adminUserId, int $treatyId, string $action): bool
    {
        $adminUser = $this->userRepo->findById($adminUserId);
        $treaty = $this->treatyRepo->findById($treatyId);

        if (!$treaty) {
            $this->session->setFlash('error', 'Treaty not found.');
            return false;
        }

        // User must be in one of the two alliances
        if (!$adminUser || ($adminUser->alliance_id !== $treaty->alliance1_id && $adminUser->alliance_id !== $treaty->alliance2_id)) {
            $this->session->setFlash('error', 'You are not a part of this treaty.');
            return false;
        }
        
        if (!$this->checkPermission($adminUserId, $adminUser->alliance_id, 'can_manage_diplomacy')) {
            $this->session->setFlash('error', 'You do not have permission to manage diplomacy.');
            return false;
        }

        if ($action === 'decline' && $treaty->status === 'proposed') {
            $this->treatyRepo->updateTreatyStatus($treatyId, 'declined');
            $this->session->setFlash('success', 'Treaty proposal declined.');
            return true;
        } elseif ($action === 'break' && $treaty->status === 'active') {
            $this->treatyRepo->updateTreatyStatus($treatyId, 'broken');
            $this->session->setFlash('success', 'Treaty has been broken.');
            return true;
        }
        
        $this->session->setFlash('error', 'Invalid action for this treaty\'s state.');
        return false;
    }

    /**
     * Declares a rivalry (or increases heat).
     */
    public function declareRivalry(int $userId, int $targetAllianceId): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to do this.');
            return false;
        }
        
        if ($user->alliance_id === $targetAllianceId) {
            $this->session->setFlash('error', 'You cannot declare a rivalry with yourself.');
            return false;
        }

        $this->rivalryRepo->createOrUpdateRivalry($user->alliance_id, $targetAllianceId);
        $this->session->setFlash('success', 'Rivalry has been declared/updated.');
        return true;
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