<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceBankLogRepository;
// --- NEW REPOSITORY TO INJECT ---
use App\Models\Repositories\AllianceLoanRepository;
use PDO;
use Throwable;

/**
 * Handles all "read" logic for Alliances.
 */
class AllianceService
{
    private PDO $db;
    private Session $session;
    private Config $config;
    private AllianceRepository $allianceRepo;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private ApplicationRepository $appRepo;
    private AllianceRoleRepository $roleRepo;
    private AllianceBankLogRepository $bankLogRepo;
    
    // --- NEW PROPERTY ---
    private AllianceLoanRepository $loanRepo;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        $this->config = new Config();
        
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $this->resourceRepo = new ResourceRepository($this->db);
        $this->appRepo = new ApplicationRepository($this->db);
        $this->roleRepo = new AllianceRoleRepository($this->db);
        $this->bankLogRepo = new AllianceBankLogRepository($this->db);
        
        // --- NEW REPOSITORY ---
        $this->loanRepo = new AllianceLoanRepository($this->db);
    }

    /**
     * Gets all data needed for the paginated alliance list page.
     */
    public function getAlliancePageData(int $page): array
    {
        $perPage = $this->config->get('app.alliance_list.per_page', 25);
        $totalAlliances = $this->allianceRepo->getTotalCount();
        $totalPages = (int)ceil($totalAlliances / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $perPage;

        $alliances = $this->allianceRepo->getPaginatedAlliances($perPage, $offset);

        return [
            'alliances' => $alliances,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages
            ],
            'perPage' => $perPage
        ];
    }

    /**
     * Gets the data needed to show the "Create Alliance" form.
     */
    public function getCreateAllianceData(int $userId): array
    {
        $cost = $this->config->get('game_balance.alliance.creation_cost', 50000000);
        $user = $this->userRepo->findById($userId);

        return [
            'cost' => $cost,
            'user' => $user
        ];
    }

    /**
     * Gets the data for a public alliance profile.
     */
    public function getPublicProfileData(int $allianceId, int $viewerId): ?array
    {
        $alliance = $this->allianceRepo->findById($allianceId);
        if (!$alliance) {
            return null; // Not found
        }

        // Get all members
        $members = $this->userRepo->findAllByAllianceId($allianceId);

        // Get the person viewing the page
        $viewer = $this->userRepo->findById($viewerId); 
        $viewerRole = null;
        if ($viewer && $viewer->alliance_id === $allianceId) {
            $viewerRole = $this->roleRepo->findById($viewer->alliance_role_id);
        }

        // Get pending applications (only relevant for those with permission)
        $applications = [];
        if ($viewerRole && $viewerRole->can_manage_applications) {
            $applications = $this->appRepo->findByAllianceId($allianceId);
        }

        // Check if the viewer has a pending application
        $userApplication = $this->appRepo->findByUserAndAlliance($viewerId, $allianceId);

        // Get all available roles for this alliance (for dropdowns)
        $roles = $this->roleRepo->findByAllianceId($allianceId);
        
        // Get Bank Logs
        $bankLogs = $this->bankLogRepo->findLogsByAllianceId($allianceId);
        
        // --- NEW: Get Alliance Loans ---
        $loans = $this->loanRepo->findByAllianceId($allianceId);
        // --- END NEW ---

        return [
            'alliance' => $alliance,
            'members' => $members,
            'viewer' => $viewer,
            'viewerRole' => $viewerRole, // The viewer's role object (or null)
            'applications' => $applications,
            'userApplication' => $userApplication,
            'roles' => $roles,
            'bankLogs' => $bankLogs,
            'loans' => $loans // --- NEW ---
        ];
    }

    /**
     * Attempts to create a new alliance.
     */
    public function createAlliance(int $userId, string $name, string $tag): ?int
    {
        // 1. Validation
        if (empty(trim($name)) || empty(trim($tag))) {
            $this->session->setFlash('error', 'Alliance name and tag cannot be empty.');
            return null;
        }
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $this->session->setFlash('error', 'Alliance name must be between 3 and 100 characters.');
            return null;
        }
        if (mb_strlen($tag) < 3 || mb_strlen($tag) > 5) {
            $this->session->setFlash('error', 'Alliance tag must be between 3 and 5 characters.');
            return null;
        }

        if ($this->allianceRepo->findByName($name)) {
            $this->session->setFlash('error', 'An alliance with this name already exists.');
            return null;
        }
        if ($this->allianceRepo->findByTag($tag)) {
            $this->session->setFlash('error', 'An alliance with this tag already exists.');
            return null;
        }

        $user = $this->userRepo->findById($userId);
        if ($user->alliance_id !== null) {
            $this->session->setFlash('error', 'You are already in an alliance.');
            return null;
        }

        $cost = $this->config->get('game_balance.alliance.creation_cost', 50000000);
        $resources = $this->resourceRepo->findByUserId($userId);

        if ($resources->credits < $cost) {
            $this->session->setFlash('error', 'You do not have enough credits to found an alliance.');
            return null;
        }

        // 2. Transaction
        $this->db->beginTransaction();
        try {
            // 2a. Create the alliance
            $newAllianceId = $this->allianceRepo->create($name, $tag, $userId);

            // 2b. Create the default roles
            $leaderRoleId = $this->roleRepo->create($newAllianceId, 'Leader', 1, [
                'can_edit_profile' => 1, 'can_manage_applications' => 1, 'can_invite_members' => 1,
                'can_kick_members' => 1, 'can_manage_roles' => 1, 'can_see_private_board' => 1,
                'can_manage_forum' => 1, 'can_manage_bank' => 1, 'can_manage_structures' => 1
            ]);
            
            $this->roleRepo->create($newAllianceId, 'Recruit', 10, ['can_invite_members' => 1]);
            $this->roleRepo->create($newAllianceId, 'Member', 9, []); // No perms by default

            // 2c. Deduct credits
            $newCredits = $resources->credits - $cost;
            $this->resourceRepo->updateCredits($userId, $newCredits);

            // 2d. Update the user to be the leader
            $this->userRepo->setAlliance($userId, $newAllianceId, $leaderRoleId);

            // 2e. Commit
            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Alliance Creation Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred while creating the alliance.');
            return null;
        }

        // 3. Success
        $this->session->setFlash('success', 'You have successfully founded the alliance: ' . $name);
        return $newAllianceId;
    }
}