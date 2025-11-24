<?php

namespace App\Models\Services;

use App\Core\Config;
use App\Core\ServiceResponse;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Services\AlliancePolicyService;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\AllianceBankLogRepository;
use App\Models\Repositories\AllianceLoanRepository;
use PDO;
use Throwable;

/**
 * Handles all "write" logic for managing alliances.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 * * Now includes createAlliance (moved from AllianceService).
 */
class AllianceManagementService
{
    private PDO $db;
    private Config $config;
    
    private AllianceRepository $allianceRepo;
    private UserRepository $userRepo;
    private ApplicationRepository $appRepo;
    private AllianceRoleRepository $roleRepo;
    private AlliancePolicyService $policyService;
    private ResourceRepository $resourceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private AllianceLoanRepository $loanRepo;

    public function __construct(
        PDO $db,
        Config $config,
        AllianceRepository $allianceRepo,
        UserRepository $userRepo,
        ApplicationRepository $appRepo,
        AllianceRoleRepository $roleRepo,
        AlliancePolicyService $policyService,
        ResourceRepository $resourceRepo,
        AllianceBankLogRepository $bankLogRepo,
        AllianceLoanRepository $loanRepo
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->allianceRepo = $allianceRepo;
        $this->userRepo = $userRepo;
        $this->appRepo = $appRepo;
        $this->roleRepo = $roleRepo;
        $this->policyService = $policyService;
        $this->resourceRepo = $resourceRepo;
        $this->bankLogRepo = $bankLogRepo;
        $this->loanRepo = $loanRepo;
    }

    /**
     * Attempts to create a new alliance.
     * 
     * @param int $userId
     * @param string $name
     * @param string $tag
     * @return ServiceResponse
     */
    public function createAlliance(int $userId, string $name, string $tag): ServiceResponse
    {
        // 1. Validation
        if (empty(trim($name)) || empty(trim($tag))) {
            return ServiceResponse::error('Alliance name and tag cannot be empty.');
        }
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            return ServiceResponse::error('Alliance name must be between 3 and 100 characters.');
        }
        if (mb_strlen($tag) < 3 || mb_strlen($tag) > 5) {
            return ServiceResponse::error('Alliance tag must be between 3 and 5 characters.');
        }

        if ($this->allianceRepo->findByName($name)) {
            return ServiceResponse::error('An alliance with this name already exists.');
        }
        if ($this->allianceRepo->findByTag($tag)) {
            return ServiceResponse::error('An alliance with this tag already exists.');
        }

        $user = $this->userRepo->findById($userId);
        if ($user->alliance_id !== null) {
            return ServiceResponse::error('You are already in an alliance.');
        }

        $cost = $this->config->get('game_balance.alliance.creation_cost', 50000000);
        $resources = $this->resourceRepo->findByUserId($userId);

        if ($resources->credits < $cost) {
            return ServiceResponse::error('You do not have enough credits to found an alliance.');
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
                'can_manage_forum' => 1, 'can_manage_bank' => 1, 'can_manage_structures' => 1,
                'can_manage_diplomacy' => 1, 'can_declare_war' => 1
            ]);
            
            $this->roleRepo->create($newAllianceId, 'Recruit', 10, ['can_invite_members' => 1]);
            $this->roleRepo->create($newAllianceId, 'Member', 9, []);

            // 2c. Deduct credits
            $newCredits = $resources->credits - $cost;
            $this->resourceRepo->updateCredits($userId, $newCredits);

            // 2d. Update the user to be the leader
            $this->userRepo->setAlliance($userId, $newAllianceId, $leaderRoleId);

            $this->db->commit();

            return ServiceResponse::success(
                'You have successfully founded the alliance: ' . $name,
                ['alliance_id' => $newAllianceId]
            );

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Alliance Creation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred while creating the alliance.');
        }
    }

    /**
     * A user applies to join an alliance.
     */
    public function applyToAlliance(int $userId, int $allianceId): ServiceResponse
    {
        $user = $this->userRepo->findById($userId);
        if ($user->alliance_id !== null) {
            return ServiceResponse::error('You are already in an alliance.');
        }

        if ($this->appRepo->findByUserAndAlliance($userId, $allianceId)) {
            return ServiceResponse::error('You have already applied to this alliance.');
        }
        
        $alliance = $this->allianceRepo->findById($allianceId);
        if ($alliance && $alliance->is_joinable) {
            // Recursively accept self if open recruitment
            return $this->acceptApplication($userId, null, $userId, $allianceId);
        }

        if ($this->appRepo->create($userId, $allianceId)) {
            return ServiceResponse::success('Application sent!');
        }

        return ServiceResponse::error('A database error occurred.');
    }

    /**
     * A user cancels their own application.
     */
    public function cancelApplication(int $userId, int $appId): ServiceResponse
    {
        $app = $this->appRepo->findById($appId);

        if (!$app || $app->user_id !== $userId) {
            return ServiceResponse::error('Invalid application.');
        }

        $this->appRepo->delete($appId);
        return ServiceResponse::success('Application cancelled.');
    }

    /**
     * A user leaves their current alliance.
     */
    public function leaveAlliance(int $userId): ServiceResponse
    {
        $user = $this->userRepo->findById($userId);

        if ($user->alliance_id === null) {
            return ServiceResponse::error('You are not in an alliance.');
        }
        
        $role = $this->roleRepo->findById($user->alliance_role_id);

        if ($role && $role->name === 'Leader') {
            return ServiceResponse::error('Leaders must disband the alliance or transfer leadership. You cannot leave.');
        }

        $this->userRepo->leaveAlliance($userId);
        return ServiceResponse::success('You have left the alliance.');
    }

    /**
     * An alliance admin accepts a pending application.
     */
    public function acceptApplication(int $adminId, ?int $appId, ?int $targetUserId = null, ?int $targetAllianceId = null): ServiceResponse
    {
        $targetAllianceId = $targetAllianceId ?? 0;
        $targetUserId = $targetUserId ?? 0;

        if ($appId !== null) {
            // Standard flow
            $app = $this->appRepo->findById($appId);
            if (!$app) {
                return ServiceResponse::error('Application not found.');
            }
            $targetAllianceId = $app->alliance_id;
            $targetUserId = $app->user_id;

            if (!$this->checkPermission($adminId, $targetAllianceId, 'can_manage_applications')) {
                return ServiceResponse::error('You do not have permission to do this.');
            }
        } else {
            // 'is_joinable' flow (Self-join)
            if ($adminId !== $targetUserId) {
                return ServiceResponse::error('Invalid join operation.');
            }
        }

        $targetUser = $this->userRepo->findById($targetUserId);
        if ($targetUser->alliance_id !== null) {
            if ($appId) $this->appRepo->delete($appId);
            return ServiceResponse::error('This user has already joined another alliance.');
        }
        
        $recruitRole = $this->roleRepo->findDefaultRole($targetAllianceId, 'Recruit');
        if (!$recruitRole) {
            return ServiceResponse::error('Critical error: Default "Recruit" role not found.');
        }

        $this->db->beginTransaction();
        try {
            $this->userRepo->setAlliance($targetUser->id, $targetAllianceId, $recruitRole->id);
            $this->appRepo->deleteByUser($targetUser->id);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Accept Application Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
        
        return ServiceResponse::success('Member accepted!', ['new_alliance_id' => $targetAllianceId]);
    }

    /**
     * An alliance admin rejects a pending application.
     */
    public function rejectApplication(int $adminId, int $appId): ServiceResponse
    {
        $app = $this->appRepo->findById($appId);
        if (!$app) {
            return ServiceResponse::error('Application not found.');
        }

        if (!$this->checkPermission($adminId, $app->alliance_id, 'can_manage_applications')) {
            return ServiceResponse::error('You do not have permission to do this.');
        }

        $this->appRepo->delete($appId);
        return ServiceResponse::success('Application rejected.');
    }

    /**
     * An alliance member with invite perms invites a user.
     */
    public function inviteUser(int $inviterId, int $targetUserId): ServiceResponse
    {
        $inviterUser = $this->userRepo->findById($inviterId);
        if (!$inviterUser || $inviterUser->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to invite players.');
        }

        if (!$this->checkPermission($inviterId, $inviterUser->alliance_id, 'can_invite_members')) {
            return ServiceResponse::error('You do not have permission to invite members.');
        }
        
        $targetUser = $this->userRepo->findById($targetUserId);
        if (!$targetUser) {
            return ServiceResponse::error('Target player not found.');
        }
        if ($targetUser->alliance_id !== null) {
            return ServiceResponse::error('That player is already in an alliance.');
        }
        
        $recruitRole = $this->roleRepo->findDefaultRole($inviterUser->alliance_id, 'Recruit');
        if (!$recruitRole) {
            return ServiceResponse::error('Critical error: Default "Recruit" role not found.');
        }
        
        $this->db->beginTransaction();
        try {
            $this->userRepo->setAlliance($targetUser->id, $inviterUser->alliance_id, $recruitRole->id);
            $this->appRepo->deleteByUser($targetUser->id);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Invite User Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred while sending the invite.');
        }
        
        return ServiceResponse::success("{$targetUser->characterName} has accepted your invite!");
    }

    /**
     * Updates public profile.
     */
    public function updateProfile(int $adminId, int $allianceId, string $description, string $pfpUrl, bool $isJoinable): ServiceResponse
    {
        if (!$this->checkPermission($adminId, $allianceId, 'can_edit_profile')) {
            return ServiceResponse::error('You do not have permission to edit the profile.');
        }
        
        if (!empty($pfpUrl) && !filter_var($pfpUrl, FILTER_VALIDATE_URL)) {
            return ServiceResponse::error('Profile picture must be a valid URL.');
        }

        $this->allianceRepo->updateProfile($allianceId, $description, $pfpUrl, $isJoinable);
        return ServiceResponse::success('Alliance profile updated.');
    }

    /**
     * Kicks a member.
     */
    public function kickMember(int $adminId, int $targetUserId): ServiceResponse
    {
        $adminUser = $this->userRepo->findById($adminId);
        $targetUser = $this->userRepo->findById($targetUserId);
        
        if (!$adminUser || !$targetUser) {
            return ServiceResponse::error('Invalid operation.');
        }

        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        $targetRole = $this->roleRepo->findById($targetUser->alliance_role_id);

        if (!$adminRole) {
            return ServiceResponse::error('You do not have an administrative role.');
        }
        
        $authError = $this->policyService->canKick($adminUser, $adminRole, $targetUser, $targetRole);
        if ($authError !== null) {
            return ServiceResponse::error($authError);
        }

        $this->userRepo->leaveAlliance($targetUserId);
        return ServiceResponse::success("{$targetUser->characterName} has been kicked from the alliance.");
    }

    /**
     * Changes a member's role.
     */
    public function changeMemberRole(int $adminId, int $targetUserId, int $newRoleId): ServiceResponse
    {
        $adminUser = $this->userRepo->findById($adminId);
        $targetUser = $this->userRepo->findById($targetUserId);
        
        if (!$adminUser || !$targetUser) {
            return ServiceResponse::error('Invalid operation.');
        }

        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        $targetRole = $this->roleRepo->findById($targetUser->alliance_role_id);
        $newRole = $this->roleRepo->findById($newRoleId);

        if (!$adminRole) {
            return ServiceResponse::error('You do not have an administrative role.');
        }
        if (!$newRole) {
            return ServiceResponse::error('The selected role does not exist.');
        }

        $authError = $this->policyService->canAssignRole($adminUser, $adminRole, $targetUser, $targetRole, $newRole);
        if ($authError !== null) {
            return ServiceResponse::error($authError);
        }

        $this->userRepo->setAllianceRole($targetUserId, $newRoleId);
        return ServiceResponse::success("{$targetUser->characterName}'s role has been updated to {$newRole->name}.");
    }

    // --- Role Management ---

    public function createRole(int $adminId, int $allianceId, string $name, array $permissions): ServiceResponse
    {
        if (!$this->checkPermission($adminId, $allianceId, 'can_manage_roles')) {
            return ServiceResponse::error('You do not have permission to create roles.');
        }
        
        $this->roleRepo->create($allianceId, $name, 100, $permissions);
        return ServiceResponse::success("Role '{$name}' created.");
    }

    public function updateRole(int $adminId, int $roleId, string $name, array $permissions): ServiceResponse
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            return ServiceResponse::error('Role not found.');
        }

        if (!$this->checkPermission($adminId, $role->alliance_id, 'can_manage_roles')) {
            return ServiceResponse::error('You do not have permission to edit roles.');
        }
        
        if (in_array($role->name, ['Leader', 'Recruit', 'Member'])) {
            return ServiceResponse::error('You cannot edit default roles.');
        }

        $this->roleRepo->update($roleId, $name, $permissions);
        return ServiceResponse::success("Role '{$name}' updated.");
    }

    public function deleteRole(int $adminId, int $roleId): ServiceResponse
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            return ServiceResponse::error('Role not found.');
        }

        if (!$this->checkPermission($adminId, $role->alliance_id, 'can_manage_roles')) {
            return ServiceResponse::error('You do not have permission to delete roles.');
        }

        if (in_array($role->name, ['Leader', 'Recruit', 'Member'])) {
            return ServiceResponse::error('You cannot delete default roles.');
        }
        
        $recruitRole = $this->roleRepo->findDefaultRole($role->alliance_id, 'Recruit');
        if (!$recruitRole) {
            return ServiceResponse::error('Critical error: Default "Recruit" role not found.');
        }

        $this->db->beginTransaction();
        try {
            $this->roleRepo->reassignRoleMembers($roleId, $recruitRole->id);
            $this->roleRepo->delete($roleId);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Delete Role Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }

        return ServiceResponse::success("Role '{$role->name}' deleted. Members reassigned to Recruit.");
    }

    // --- Funding ---

    public function donateToAlliance(int $donatorUserId, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::error('Donation amount must be a positive number.');
        }

        $user = $this->userRepo->findById($donatorUserId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to donate.');
        }
        $allianceId = $user->alliance_id;

        $resources = $this->resourceRepo->findByUserId($donatorUserId);
        if ($resources->credits < $amount) {
            return ServiceResponse::error('You do not have enough credits on hand to donate.');
        }

        $this->db->beginTransaction();
        try {
            $this->resourceRepo->updateCredits($donatorUserId, $resources->credits - $amount);
            $this->allianceRepo->updateBankCreditsRelative($allianceId, $amount);
            $message = "Donation from " . $user->characterName;
            $this->bankLogRepo->createLog($allianceId, $donatorUserId, 'donation', $amount, $message);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Alliance Donation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred during the donation.');
        }

        return ServiceResponse::success('You have successfully donated ' . number_format($amount) . ' credits to the alliance.');
    }

    public function requestLoan(int $userId, int $amount): ServiceResponse
    {
        if ($amount <= 0) {
            return ServiceResponse::error('Loan amount must be a positive number.');
        }

        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to request a loan.');
        }
        
        $this->loanRepo->createLoanRequest($user->alliance_id, $userId, $amount);
        return ServiceResponse::success('Loan request for ' . number_format($amount) . ' credits has been submitted.');
    }

    public function approveLoan(int $adminUserId, int $loanId): ServiceResponse
    {
        $loan = $this->loanRepo->findById($loanId);
        if (!$loan) {
            return ServiceResponse::error('Loan not found.');
        }

        if (!$this->checkPermission($adminUserId, $loan->alliance_id, 'can_manage_bank')) {
            return ServiceResponse::error('You do not have permission to manage the bank.');
        }
        
        if ($loan->status !== 'pending') {
            return ServiceResponse::error('This loan is not pending approval.');
        }
        
        $alliance = $this->allianceRepo->findById($loan->alliance_id);
        if ($alliance->bank_credits < $loan->amount_requested) {
            return ServiceResponse::error('The alliance bank does not have enough credits.');
        }
        
        $borrower = $this->userRepo->findById($loan->user_id);
        $borrowerResources = $this->resourceRepo->findByUserId($loan->user_id);
        if (!$borrower || !$borrowerResources) {
            return ServiceResponse::error('Borrower not found.');
        }

        $this->db->beginTransaction();
        try {
            $this->allianceRepo->updateBankCreditsRelative($loan->alliance_id, -$loan->amount_requested);
            $this->resourceRepo->updateCredits($loan->user_id, $borrowerResources->credits + $loan->amount_requested);
            
            $message = "Loan for {$borrower->characterName} approved by admin.";
            $this->bankLogRepo->createLog($loan->alliance_id, $adminUserId, 'loan_approved', -$loan->amount_requested, $message);
            
            $this->loanRepo->updateLoan($loanId, 'active', $loan->amount_to_repay);
            
            $this->db->commit();
            
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Alliance Loan Approve Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
        
        return ServiceResponse::success("Loan approved. Credits sent to {$borrower->characterName}.");
    }

    public function denyLoan(int $adminUserId, int $loanId): ServiceResponse
    {
        $loan = $this->loanRepo->findById($loanId);
        if (!$loan) {
            return ServiceResponse::error('Loan not found.');
        }

        if (!$this->checkPermission($adminUserId, $loan->alliance_id, 'can_manage_bank')) {
            return ServiceResponse::error('You do not have permission to manage the bank.');
        }
        
        if ($loan->status !== 'pending') {
            return ServiceResponse::error('This loan is not pending approval.');
        }
        
        $this->loanRepo->updateLoan($loanId, 'denied', 0);
        return ServiceResponse::success('Loan request has been denied.');
    }

    public function repayLoan(int $userId, int $loanId, int $amount): ServiceResponse
    {
        $loan = $this->loanRepo->findById($loanId);
        
        if ($amount <= 0) {
            return ServiceResponse::error('Repayment amount must be positive.');
        }
        if (!$loan || $loan->user_id !== $userId) {
            return ServiceResponse::error('This is not your loan.');
        }
        if ($loan->status !== 'active') {
            return ServiceResponse::error('This loan is not active.');
        }
        
        $user = $this->userRepo->findById($userId);
        $resources = $this->resourceRepo->findByUserId($userId);
        
        if ($resources->credits < $amount) {
            return ServiceResponse::error('You do not have enough credits on hand.');
        }
        
        $repayment = min($amount, $loan->amount_to_repay);
        $newAmountOwed = $loan->amount_to_repay - $repayment;
        $newStatus = ($newAmountOwed == 0) ? 'paid' : 'active';
        
        $this->db->beginTransaction();
        try {
            $this->resourceRepo->updateCredits($userId, $resources->credits - $repayment);
            $this->allianceRepo->updateBankCreditsRelative($loan->alliance_id, $repayment);
            
            $message = "Loan repayment from " . $user->characterName;
            $this->bankLogRepo->createLog($loan->alliance_id, $userId, 'loan_repayment', $repayment, $message);
            
            $this->loanRepo->updateLoan($loanId, $newStatus, $newAmountOwed);
            
            $this->db->commit();
            
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Alliance Loan Repay Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
        
        $msg = 'Thank you for your payment of ' . number_format($repayment) . ' credits.';
        if ($newStatus === 'paid') {
            $msg .= ' Your loan has been fully repaid!';
        }
        return ServiceResponse::success($msg);
    }

    private function checkPermission(int $userId, int $allianceId, string $permissionName): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id !== $allianceId) return false;
        
        $role = $this->roleRepo->findById($user->alliance_role_id);
        return $role && property_exists($role, $permissionName) && $role->{$permissionName} === true;
    }
}