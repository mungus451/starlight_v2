<?php

namespace App\Models\Services;

use App\Core\Database;
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

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        $this->userRepo = new UserRepository($this->db);
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->roleRepo = new AllianceRoleRepository($this->db);
        $this->treatyRepo = new TreatyRepository($this->db);
        $this->rivalryRepo = new RivalryRepository($this->db);
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
        $allAlliances = $this->allianceRepo->getAllAlliances(); // For dropdowns

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
     * @param int $proposerUserId
     * @param int $targetAllianceId
     * @param string $treatyType
     * @param string $terms
     * @return bool
     */
    public function proposeTreaty(int $proposerUserId, int $targetAllianceId, string $treatyType, string $terms): bool
    {
        $proposerUser = $this->userRepo->findById($proposerUserId);
        if (!$proposerUser || $proposerUser->alliance_id === null) {
            $this->session->setFlash('error', 'You are not in an alliance.');
            return false;
        }
        
        $proposerAllianceId = $proposerUser->alliance_id;

        // 1. Permission Check
        if (!$this->checkPermission($proposerUserId, $proposerAllianceId, 'can_manage_diplomacy')) {
            $this->session->setFlash('error', 'You do not have permission to manage diplomacy.');
            return false;
        }

        // 2. Validation
        if ($proposerAllianceId === $targetAllianceId) {
            $this->session->setFlash('error', 'You cannot propose a treaty with your own alliance.');
            return false;
        }
        if (!in_array($treatyType, ['peace', 'non_aggression', 'mutual_defense'])) {
            $this->session->setFlash('error', 'Invalid treaty type.');
            return false;
        }
        
        // TODO: Check for existing pending/active treaties with this alliance

        // 3. Create Proposal
        $this->treatyRepo->createTreaty($proposerAllianceId, $targetAllianceId, $treatyType, $terms);
        $this->session->setFlash('success', 'Treaty proposed successfully.');
        return true;
    }

    /**
     * Accepts a treaty proposal.
     *
     * @param int $adminUserId
     * @param int $treatyId
     * @return bool
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
        
        // Permission Check
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
     *
     * @param int $adminUserId
     * @param int $treatyId
     * @param string $action ('decline' or 'break')
     * @return bool
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
        
        // Permission Check
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
     *
     * @param int $userId
     * @param int $targetAllianceId
     * @return bool
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

        // Note: No permission check, as this is a hostile action any member can take.
        // We can add a permission for this later if needed.
        
        $this->rivalryRepo->createOrUpdateRivalry($user->alliance_id, $targetAllianceId);
        $this->session->setFlash('success', 'Rivalry has been declared/updated.');
        return true;
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