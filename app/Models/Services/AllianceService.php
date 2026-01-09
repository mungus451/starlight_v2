<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\Permissions;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceLoanRepository;
use PDO;

use App\Models\Repositories\StructureRepository;

/**
 * Handles all "read" logic for Alliances.
 * * Refactored Phase 2.3: View Logic Cleanup.
 * * Categorizes loans internally to keep the View dumb.
 */
class AllianceService
{
    private PDO $db;
    private Config $config;
    
    private AllianceRepository $allianceRepo;
    private UserRepository $userRepo;
    private ResourceRepository $resourceRepo;
    private ApplicationRepository $appRepo;
    private AllianceRoleRepository $roleRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private AllianceLoanRepository $loanRepo;
    private StructureRepository $structureRepo;

    public function __construct(
        PDO $db,
        Config $config,
        AllianceRepository $allianceRepo,
        UserRepository $userRepo,
        ResourceRepository $resourceRepo,
        ApplicationRepository $appRepo,
        AllianceRoleRepository $roleRepo,
        AllianceBankLogRepository $bankLogRepo,
        AllianceLoanRepository $loanRepo,
        StructureRepository $structureRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        
        $this->allianceRepo = $allianceRepo;
        $this->userRepo = $userRepo;
        $this->resourceRepo = $resourceRepo;
        $this->appRepo = $appRepo;
        $this->roleRepo = $roleRepo;
        $this->bankLogRepo = $bankLogRepo;
        $this->loanRepo = $loanRepo;
        $this->structureRepo = $structureRepo;
    }

    /**
     * Calculates the current status and targets for all available directives.
     */
    public function getDirectiveOptions(int $allianceId): array
    {
        $alliance = $this->allianceRepo->findById($allianceId);
        if (!$alliance) return [];

        // 1. Industry (Total Levels)
        $currentIndustry = $this->structureRepo->getAggregateStructureLevelForAlliance($allianceId);
        
        // 2. Military (Soldiers + Guards)
        $currentMilitary = $this->resourceRepo->getAggregateUnitsForAlliance($allianceId, ['soldiers', 'guards']);
        
        // 3. Intel (Spies + Sentries)
        $currentIntel = $this->resourceRepo->getAggregateUnitsForAlliance($allianceId, ['spies', 'sentries']);
        
        // 4. Treasury
        $currentTreasury = (int)$alliance->bank_credits;
        
        // 5. Recruitment
        $currentMembers = $this->userRepo->countAllianceMembers($allianceId);

        // Helper to calc target (+10% or min +1)
        $calcTarget = fn($val) => (int)ceil($val * 1.10) + ($val == 0 ? 1 : 0);
        $calcRecruitTarget = fn($val) => $val + 1; // +1 Member for recruitment usually

        return [
            'industry' => [
                'current' => $currentIndustry,
                'target' => $calcTarget($currentIndustry),
                'name' => 'Industrial Revolution',
                'desc' => 'Increase total structure levels.',
                'icon' => 'fa-industry'
            ],
            'military' => [
                'current' => $currentMilitary,
                'target' => $calcTarget($currentMilitary),
                'name' => 'Total Mobilization',
                'desc' => 'Train more soldiers and guards.',
                'icon' => 'fa-fighter-jet'
            ],
            'intel' => [
                'current' => $currentIntel,
                'target' => $calcTarget($currentIntel),
                'name' => 'Shadow Protocol',
                'desc' => 'Expand spy and sentry networks.',
                'icon' => 'fa-user-secret'
            ],
            'treasury' => [
                'current' => $currentTreasury,
                'target' => $calcTarget($currentTreasury),
                'name' => 'Treasury Tithe',
                'desc' => 'Deposit credits into the bank.',
                'icon' => 'fa-coins'
            ],
            'recruit' => [
                'current' => $currentMembers,
                'target' => $calcRecruitTarget($currentMembers),
                'name' => 'Mass Recruitment',
                'desc' => 'Recruit new members.',
                'icon' => 'fa-users'
            ]
        ];
    }

    /**
     * Sets the active directive for an alliance.
     *
     * @param int $allianceId
     * @param string $type
     * @return bool
     */
    public function setDirective(int $allianceId, string $type): bool
    {
        $options = $this->getDirectiveOptions($allianceId);
        
        if (!isset($options[$type])) {
            return false;
        }
        
        $data = $options[$type];
        
        return $this->allianceRepo->updateDirective(
            $allianceId,
            $type,
            $data['target'],
            $data['current']
        );
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
     * Now performs logic to sort loans and filter data.
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
        if ($viewerRole && $viewerRole->hasPermission(Permissions::CAN_MANAGE_APPLICATIONS)) {
            $applications = $this->appRepo->findByAllianceId($allianceId);
        }

        // Check if the viewer has a pending application
        $userApplication = $this->appRepo->findByUserAndAlliance($viewerId, $allianceId);

        // Get all available roles for this alliance (for dropdowns)
        $roles = $this->roleRepo->findByAllianceId($allianceId);
        
        // Get Bank Logs
        $bankLogs = $this->bankLogRepo->findLogsByAllianceId($allianceId);
        
        // Get Alliance Loans & Categorize them (Logic moved from View)
        $loans = $this->loanRepo->findByAllianceId($allianceId);
        $pendingLoans = [];
        $activeLoans = [];
        $historicalLoans = [];

        // Only process loans if the viewer is a member
        if ($viewer->alliance_id === $allianceId) {
            foreach ($loans as $loan) {
                if ($loan->status === 'pending') {
                    $pendingLoans[] = $loan;
                } elseif ($loan->status === 'active') {
                    $activeLoans[] = $loan;
                } else {
                    $historicalLoans[] = $loan;
                }
            }
        }

        return [
            'alliance' => $alliance,
            'members' => $members,
            'viewer' => $viewer,
            'viewerRole' => $viewerRole,
            'applications' => $applications,
            'userApplication' => $userApplication,
            'roles' => $roles,
            'bankLogs' => $bankLogs,
            // Explicitly pass the sorted arrays
            'loans' => $loans, // Kept for debug or raw access if needed
            'pendingLoans' => $pendingLoans,
            'activeLoans' => $activeLoans,
            'historicalLoans' => $historicalLoans
        ];
    }
}