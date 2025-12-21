<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Repositories\TreatyRepository;
use App\Models\Repositories\RivalryRepository;
use App\Models\Services\NotificationService;
use PDO;

/**
 * Handles all business logic for Alliance Diplomacy (Treaties & Rivalries).
 * * Refactored Phase 2.2: View Logic Cleanup.
 * * Categorizes treaties internally to keep the View dumb.
 */
class DiplomacyService
{
    private PDO $db;
    
    private UserRepository $userRepo;
    private AllianceRepository $allianceRepo;
    private AllianceRoleRepository $roleRepo;
    private TreatyRepository $treatyRepo;
    private RivalryRepository $rivalryRepo;
    private NotificationService $notificationService;

    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        AllianceRepository $allianceRepo,
        AllianceRoleRepository $roleRepo,
        TreatyRepository $treatyRepo,
        RivalryRepository $rivalryRepo,
        NotificationService $notificationService
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->allianceRepo = $allianceRepo;
        $this->roleRepo = $roleRepo;
        $this->treatyRepo = $treatyRepo;
        $this->rivalryRepo = $rivalryRepo;
        $this->notificationService = $notificationService;
    }

    /**
     * Gets all diplomacy data for the view, ensuring the user is authorized to see it.
     * Categorizes treaties into 'pending' (incoming) and 'active'.
     *
     * @param int $userId
     * @return ServiceResponse
     */
    public function getDiplomacyData(int $userId): ServiceResponse
    {
        // 1. Validate User & Alliance
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to view diplomacy.');
        }
        
        $allianceId = $user->alliance_id;

        // 2. Check Permissions
        $role = $this->roleRepo->findById($user->alliance_role_id);
        $canManage = ($role && $role->can_manage_diplomacy);

        // 3. Fetch Data
        $rawTreaties = $this->treatyRepo->findByAllianceId($allianceId);
        $rivalries = $this->rivalryRepo->findByAllianceId($allianceId);
        $allAlliances = $this->allianceRepo->getAllAlliances();

        // 4. Process Data (Logic moved from View)
        $pendingTreaties = [];
        $activeTreaties = [];

        foreach ($rawTreaties as $treaty) {
            if ($treaty->status === 'proposed') {
                // Only show proposals sent TO us in the "Pending" list to accept/decline.
                // Proposals WE sent are waiting on them (could be a separate list, but for now we filter).
                if ($treaty->alliance2_id === $allianceId) {
                    $pendingTreaties[] = $treaty;
                }
            } elseif ($treaty->status === 'active') {
                $activeTreaties[] = $treaty;
            }
            // Historical/Broken/Declined treaties are currently ignored by the view, so we filter them out here.
        }

        // Filter out own alliance from target list for "Declare War/Rivalry" dropdowns
        $otherAlliances = array_filter($allAlliances, function($alliance) use ($allianceId) {
            return $alliance->id !== $allianceId;
        });

        return ServiceResponse::success('Data retrieved', [
            'pendingTreaties' => $pendingTreaties,
            'activeTreaties' => $activeTreaties,
            'rivalries' => $rivalries,
            'otherAlliances' => $otherAlliances,
            'canManage' => $canManage,
            'allianceId' => $allianceId,
            'viewer' => $user
        ]);
    }

    /**
     * Proposes a new treaty with another alliance.
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
        
        // Notify the target alliance about the treaty proposal
        $proposerAlliance = $this->allianceRepo->findById($proposerAllianceId);
        $this->notificationService->notifyAllianceMembers(
            $targetAllianceId,
            0, // No user to exclude since this is from another alliance
            'Treaty Proposal',
            "Alliance [{$proposerAlliance->tag}] {$proposerAlliance->name} proposed a {$treatyType} treaty",
            "/alliance/diplomacy"
        );
        
        return ServiceResponse::success('Treaty proposed successfully.');
    }

    /**
     * Accepts a treaty proposal.
     */
    public function acceptTreaty(int $adminUserId, int $treatyId): ServiceResponse
    {
        $adminUser = $this->userRepo->findById($adminUserId);
        $treaty = $this->treatyRepo->findById($treatyId);

        if (!$treaty) {
            return ServiceResponse::error('Treaty not found.');
        }
        
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
        
        // Notify both alliances about the treaty acceptance
        $alliance1 = $this->allianceRepo->findById($treaty->alliance1_id);
        $alliance2 = $this->allianceRepo->findById($treaty->alliance2_id);
        
        $this->notificationService->notifyAllianceMembers(
            $treaty->alliance1_id,
            0,
            'Treaty Accepted',
            "Alliance [{$alliance2->tag}] {$alliance2->name} accepted the {$treaty->type} treaty",
            "/alliance/diplomacy"
        );
        
        $this->notificationService->notifyAllianceMembers(
            $treaty->alliance2_id,
            $adminUserId,
            'Treaty Accepted',
            "Your alliance accepted the {$treaty->type} treaty with [{$alliance1->tag}] {$alliance1->name}",
            "/alliance/diplomacy"
        );
        
        return ServiceResponse::success('Treaty accepted and is now active.');
    }

    /**
     * Declines or breaks a treaty.
     */
    public function endTreaty(int $adminUserId, int $treatyId, string $action): ServiceResponse
    {
        $adminUser = $this->userRepo->findById($adminUserId);
        $treaty = $this->treatyRepo->findById($treatyId);

        if (!$treaty) {
            return ServiceResponse::error('Treaty not found.');
        }

        if (!$adminUser || ($adminUser->alliance_id !== $treaty->alliance1_id && $adminUser->alliance_id !== $treaty->alliance2_id)) {
            return ServiceResponse::error('You are not a part of this treaty.');
        }
        
        if (!$this->checkPermission($adminUserId, $adminUser->alliance_id, 'can_manage_diplomacy')) {
            return ServiceResponse::error('You do not have permission to manage diplomacy.');
        }

        if ($action === 'decline' && $treaty->status === 'proposed') {
            $this->treatyRepo->updateTreatyStatus($treatyId, 'declined');
            
            // Notify the proposing alliance about the rejection
            $decliningAlliance = $this->allianceRepo->findById($adminUser->alliance_id);
            $otherAllianceId = ($treaty->alliance1_id === $adminUser->alliance_id) ? $treaty->alliance2_id : $treaty->alliance1_id;
            
            $this->notificationService->notifyAllianceMembers(
                $otherAllianceId,
                0,
                'Treaty Rejected',
                "Alliance [{$decliningAlliance->tag}] {$decliningAlliance->name} rejected the treaty proposal",
                "/alliance/diplomacy"
            );
            
            return ServiceResponse::success('Treaty proposal declined.');
        } elseif ($action === 'break' && $treaty->status === 'active') {
            $this->treatyRepo->updateTreatyStatus($treatyId, 'broken');
            
            // Notify both alliances about the treaty cancellation
            $breakingAlliance = $this->allianceRepo->findById($adminUser->alliance_id);
            $otherAllianceId = ($treaty->alliance1_id === $adminUser->alliance_id) ? $treaty->alliance2_id : $treaty->alliance1_id;
            
            $this->notificationService->notifyAllianceMembers(
                $otherAllianceId,
                0,
                'Treaty Cancelled',
                "Alliance [{$breakingAlliance->tag}] {$breakingAlliance->name} cancelled the treaty",
                "/alliance/diplomacy"
            );
            
            $this->notificationService->notifyAllianceMembers(
                $adminUser->alliance_id,
                $adminUserId,
                'Treaty Cancelled',
                "Your alliance cancelled the treaty with the other alliance",
                "/alliance/diplomacy"
            );
            
            return ServiceResponse::success('Treaty has been broken.');
        }
        
        return ServiceResponse::error('Invalid action for this treaty\'s state.');
    }

    /**
     * Declares a rivalry (or increases heat).
     */
    public function declareRivalry(int $userId, int $targetAllianceId): ServiceResponse
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to do this.');
        }
        
        if (!$this->checkPermission($userId, $user->alliance_id, 'can_manage_diplomacy')) {
            return ServiceResponse::error('You do not have permission to declare rivalries.');
        }
        
        if ($user->alliance_id === $targetAllianceId) {
            return ServiceResponse::error('You cannot declare a rivalry with yourself.');
        }

        $this->rivalryRepo->createOrUpdateRivalry($user->alliance_id, $targetAllianceId);
        
        // Notify both alliances about the rivalry declaration
        $declaringAlliance = $this->allianceRepo->findById($user->alliance_id);
        $targetAlliance = $this->allianceRepo->findById($targetAllianceId);
        
        $this->notificationService->notifyAllianceMembers(
            $targetAllianceId,
            0,
            'Rivalry Declared',
            "Alliance [{$declaringAlliance->tag}] {$declaringAlliance->name} declared a rivalry against you",
            "/alliance/diplomacy"
        );
        
        $this->notificationService->notifyAllianceMembers(
            $user->alliance_id,
            $userId,
            'Rivalry Declared',
            "Your alliance declared a rivalry against [{$targetAlliance->tag}] {$targetAlliance->name}",
            "/alliance/diplomacy"
        );
        
        return ServiceResponse::success('Rivalry has been declared/updated.');
    }

    private function checkPermission(int $userId, int $allianceId, string $permissionName): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id !== $allianceId) return false;
        
        $role = $this->roleRepo->findById($user->alliance_role_id);
        return $role && property_exists($role, $permissionName) && $role->{$permissionName} === true;
    }
}