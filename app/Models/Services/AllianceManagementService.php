<?php

namespace App\Models\Services;

use App\Core\Session;
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
 * Handles all "write" logic for managing alliance membership.
 * * Refactored for Strict Dependency Injection.
 */
class AllianceManagementService
{
    private PDO $db;
    private Session $session;
    
    private AllianceRepository $allianceRepo;
    private UserRepository $userRepo;
    private ApplicationRepository $appRepo;
    private AllianceRoleRepository $roleRepo;
    private AlliancePolicyService $policyService;
    private ResourceRepository $resourceRepo;
    private AllianceBankLogRepository $bankLogRepo;
    private AllianceLoanRepository $loanRepo;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Session $session
     * @param AllianceRepository $allianceRepo
     * @param UserRepository $userRepo
     * @param ApplicationRepository $appRepo
     * @param AllianceRoleRepository $roleRepo
     * @param AlliancePolicyService $policyService
     * @param ResourceRepository $resourceRepo
     * @param AllianceBankLogRepository $bankLogRepo
     * @param AllianceLoanRepository $loanRepo
     */
    public function __construct(
        PDO $db,
        Session $session,
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
        $this->session = $session;
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
     * A user applies to join an alliance.
     */
    public function applyToAlliance(int $userId, int $allianceId): bool
    {
        $user = $this->userRepo->findById($userId);
        if ($user->alliance_id !== null) {
            $this->session->setFlash('error', 'You are already in an alliance.');
            return false;
        }

        if ($this->appRepo->findByUserAndAlliance($userId, $allianceId)) {
            $this->session->setFlash('error', 'You have already applied to this alliance.');
            return false;
        }
        
        $alliance = $this->allianceRepo->findById($allianceId);
        if ($alliance && $alliance->is_joinable) {
            // If it's open, skip application and just join
            $this->session->setFlash('success', 'This alliance has open recruitment! You have joined.');
            return $this->acceptApplication($userId, null, $userId, $allianceId);
        }

        if ($this->appRepo->create($userId, $allianceId)) {
            $this->session->setFlash('success', 'Application sent!');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * A user cancels their own application.
     */
    public function cancelApplication(int $userId, int $appId): bool
    {
        $app = $this->appRepo->findById($appId);

        if (!$app || $app->user_id !== $userId) {
            $this->session->setFlash('error', 'Invalid application.');
            return false;
        }

        $this->appRepo->delete($appId);
        $this->session->setFlash('success', 'Application cancelled.');
        return true;
    }

    /**
     * A user (not a leader) leaves their current alliance.
     */
    public function leaveAlliance(int $userId): bool
    {
        $user = $this->userRepo->findById($userId);

        if ($user->alliance_id === null) {
            $this->session->setFlash('error', 'You are not in an alliance.');
            return false;
        }
        
        $role = $this->roleRepo->findById($user->alliance_role_id);

        if ($role && $role->name === 'Leader') {
            $this->session->setFlash('error', 'Leaders must disband the alliance (feature coming soon). You cannot leave.');
            return false;
        }

        $this->userRepo->leaveAlliance($userId);
        
        // Update the user's session
        $this->session->set('alliance_id', null);
        
        $this->session->setFlash('success', 'You have left the alliance.');
        return true;
    }

    /**
     * An alliance admin accepts a pending application.
     */
    public function acceptApplication(int $adminId, ?int $appId, ?int $targetUserId = null, ?int $targetAllianceId = null): bool
    {
        $targetAllianceId = $targetAllianceId ?? 0;
        $targetUserId = $targetUserId ?? 0;

        if ($appId !== null) {
            // Standard flow
            $app = $this->appRepo->findById($appId);
            if (!$app) {
                $this->session->setFlash('error', 'Application not found.');
                return false;
            }
            $targetAllianceId = $app->alliance_id;
            $targetUserId = $app->user_id;

            if (!$this->checkPermission($adminId, $targetAllianceId, 'can_manage_applications')) {
                $this->session->setFlash('error', 'You do not have permission to do this.');
                return false;
            }
        } else {
            // 'is_joinable' flow
            if ($adminId !== $targetUserId) {
                $this->session->setFlash('error', 'Invalid join operation.');
                return false;
            }
        }

        $targetUser = $this->userRepo->findById($targetUserId);
        if ($targetUser->alliance_id !== null) {
            $this->session->setFlash('error', 'This user has already joined another alliance.');
            if ($appId) $this->appRepo->delete($appId);
            return false;
        }
        
        $recruitRole = $this->roleRepo->findDefaultRole($targetAllianceId, 'Recruit');
        if (!$recruitRole) {
            $this->session->setFlash('error', 'Critical error: Default "Recruit" role not found for this alliance.');
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->userRepo->setAlliance($targetUser->id, $targetAllianceId, $recruitRole->id);
            $this->appRepo->deleteByUser($targetUser->id);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Accept Application Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred.');
            return false;
        }
        
        if ($adminId === $targetUserId) {
            $this->session->set('alliance_id', $targetAllianceId);
        }
        
        $this->session->setFlash('success', 'Member accepted!');
        return true;
    }

    /**
     * An alliance admin rejects a pending application.
     */
    public function rejectApplication(int $adminId, int $appId): bool
    {
        $app = $this->appRepo->findById($appId);
        if (!$app) {
            $this->session->setFlash('error', 'Application not found.');
            return false;
        }

        if (!$this->checkPermission($adminId, $app->alliance_id, 'can_manage_applications')) {
            $this->session->setFlash('error', 'You do not have permission to do this.');
            return false;
        }

        $this->appRepo->delete($appId);
        $this->session->setFlash('success', 'Application rejected.');
        return true;
    }

    /**
     * An alliance member with invite perms invites a user.
     */
    public function inviteUser(int $inviterId, int $targetUserId): bool
    {
        $inviterUser = $this->userRepo->findById($inviterId);
        if (!$inviterUser || $inviterUser->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to invite players.');
            return false;
        }

        if (!$this->checkPermission($inviterId, $inviterUser->alliance_id, 'can_invite_members')) {
            $this->session->setFlash('error', 'You do not have permission to invite members.');
            return false;
        }
        
        $targetUser = $this->userRepo->findById($targetUserId);
        if (!$targetUser) {
            $this->session->setFlash('error', 'Target player not found.');
            return false;
        }
        if ($targetUser->alliance_id !== null) {
            $this->session->setFlash('error', 'That player is already in an alliance.');
            return false;
        }
        
        $recruitRole = $this->roleRepo->findDefaultRole($inviterUser->alliance_id, 'Recruit');
        if (!$recruitRole) {
            $this->session->setFlash('error', 'Critical error: Default "Recruit" role not found for your alliance.');
            return false;
        }
        
        $this->db->beginTransaction();
        try {
            $this->userRepo->setAlliance($targetUser->id, $inviterUser->alliance_id, $recruitRole->id);
            $this->appRepo->deleteByUser($targetUser->id);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Invite User Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred while sending the invite.');
            return false;
        }
        
        if ($inviterId === $targetUserId) {
            $this->session->set('alliance_id', $inviterUser->alliance_id);
        }
        
        $this->session->setFlash('success', $targetUser->characterName . ' has accepted your invite and joined the alliance!');
        return true;
    }

    /**
     * Handles updating the alliance's public profile.
     */
    public function updateProfile(int $adminId, int $allianceId, string $description, string $pfpUrl, bool $isJoinable): bool
    {
        if (!$this->checkPermission($adminId, $allianceId, 'can_edit_profile')) {
            $this->session->setFlash('error', 'You do not have permission to edit the profile.');
            return false;
        }
        
        if (!empty($pfpUrl) && !filter_var($pfpUrl, FILTER_VALIDATE_URL)) {
            $this->session->setFlash('error', 'Profile picture must be a valid URL.');
            return false;
        }

        $this->allianceRepo->updateProfile($allianceId, $description, $pfpUrl, $isJoinable);
        $this->session->setFlash('success', 'Alliance profile updated.');
        return true;
    }

    /**
     * Kicks a member from an alliance.
     */
    public function kickMember(int $adminId, int $targetUserId): bool
    {
        $adminUser = $this->userRepo->findById($adminId);
        $targetUser = $this->userRepo->findById($targetUserId);
        
        if (!$adminUser || !$targetUser) {
            $this->session->setFlash('error', 'Invalid operation.');
            return false;
        }

        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        $targetRole = $this->roleRepo->findById($targetUser->alliance_role_id);

        if (!$adminRole) {
            $this->session->setFlash('error', 'You do not have an administrative role.');
            return false;
        }
        
        $authError = $this->policyService->canKick($adminUser, $adminRole, $targetUser, $targetRole);
        if ($authError !== null) {
            $this->session->setFlash('error', $authError);
            return false;
        }

        $this->userRepo->leaveAlliance($targetUserId);
        
        if ($adminId === $targetUserId) {
            $this->session->set('alliance_id', null);
        }
        
        $this->session->setFlash('success', $targetUser->characterName . ' has been kicked from the alliance.');
        return true;
    }

    /**
     * Changes a member's role.
     */
    public function changeMemberRole(int $adminId, int $targetUserId, int $newRoleId): bool
    {
        $adminUser = $this->userRepo->findById($adminId);
        $targetUser = $this->userRepo->findById($targetUserId);
        
        if (!$adminUser || !$targetUser) {
            $this->session->setFlash('error', 'Invalid operation.');
            return false;
        }

        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        $targetRole = $this->roleRepo->findById($targetUser->alliance_role_id);
        $newRole = $this->roleRepo->findById($newRoleId);

        if (!$adminRole) {
            $this->session->setFlash('error', 'You do not have an administrative role.');
            return false;
        }
        if (!$newRole) {
            $this->session->setFlash('error', 'The selected role does not exist.');
            return false;
        }

        $authError = $this->policyService->canAssignRole($adminUser, $adminRole, $targetUser, $targetRole, $newRole);
        if ($authError !== null) {
            $this->session->setFlash('error', $authError);
            return false;
        }

        $this->userRepo->setAllianceRole($targetUserId, $newRoleId);
        $this->session->setFlash('success', $targetUser->characterName . "'s role has been updated to " . $newRole->name . ".");
        return true;
    }

    /**
     * Creates a new custom role.
     */
    public function createRole(int $adminId, int $allianceId, string $name, array $permissions): bool
    {
        if (!$this->checkPermission($adminId, $allianceId, 'can_manage_roles')) {
            $this->session->setFlash('error', 'You do not have permission to create roles.');
            return false;
        }
        
        $this->roleRepo->create($allianceId, $name, 100, $permissions);
        $this->session->setFlash('success', "Role '{$name}' created.");
        return true;
    }

    /**
     * Updates an existing custom role.
     */
    public function updateRole(int $adminId, int $roleId, string $name, array $permissions): bool
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            $this->session->setFlash('error', 'Role not found.');
            return false;
        }

        if (!$this->checkPermission($adminId, $role->alliance_id, 'can_manage_roles')) {
            $this->session->setFlash('error', 'You do not have permission to edit roles.');
            return false;
        }
        
        if (in_array($role->name, ['Leader', 'Recruit', 'Member'])) {
            $this->session->setFlash('error', 'You cannot edit default roles.');
            return false;
        }

        $this->roleRepo->update($roleId, $name, $permissions);
        $this->session->setFlash('success', "Role '{$name}' updated.");
        return true;
    }

    /**
     * Deletes a custom role.
     */
    public function deleteRole(int $adminId, int $roleId): bool
    {
        $role = $this->roleRepo->findById($roleId);
        if (!$role) {
            $this->session->setFlash('error', 'Role not found.');
            return false;
        }

        if (!$this->checkPermission($adminId, $role->alliance_id, 'can_manage_roles')) {
            $this->session->setFlash('error', 'You do not have permission to delete roles.');
            return false;
        }

        if (in_array($role->name, ['Leader', 'Recruit', 'Member'])) {
            $this->session->setFlash('error', 'You cannot delete default roles.');
            return false;
        }
        
        $recruitRole = $this->roleRepo->findDefaultRole($role->alliance_id, 'Recruit');
        if (!$recruitRole) {
            $this->session->setFlash('error', 'Critical error: Default "Recruit" role not found.');
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->roleRepo->reassignRoleMembers($roleId, $recruitRole->id);
            $this->roleRepo->delete($roleId);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Delete Role Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred.');
            return false;
        }

        $this->session->setFlash('success', "Role '{$role->name}' deleted. Members were reassigned to Recruit.");
        return true;
    }

    /**
     * A user donates their personal credits to the alliance bank.
     */
    public function donateToAlliance(int $donatorUserId, int $amount): bool
    {
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Donation amount must be a positive number.');
            return false;
        }

        $user = $this->userRepo->findById($donatorUserId);
        if (!$user || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to donate.');
            return false;
        }
        $allianceId = $user->alliance_id;

        $resources = $this->resourceRepo->findByUserId($donatorUserId);
        if ($resources->credits < $amount) {
            $this->session->setFlash('error', 'You do not have enough credits on hand to donate.');
            return false;
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
            $this->session->setFlash('error', 'A database error occurred during the donation.');
            return false;
        }

        $this->session->setFlash('success', 'You have successfully donated ' . number_format($amount) . ' credits to the alliance.');
        return true;
    }

    /**
     * A user requests a loan from the alliance bank.
     */
    public function requestLoan(int $userId, int $amount): bool
    {
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Loan amount must be a positive number.');
            return false;
        }

        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to request a loan.');
            return false;
        }
        
        $this->loanRepo->createLoanRequest($user->alliance_id, $userId, $amount);
        $this->session->setFlash('success', 'Loan request for ' . number_format($amount) . ' credits has been submitted.');
        return true;
    }

    /**
     * An admin approves a loan, transferring funds.
     */
    public function approveLoan(int $adminUserId, int $loanId): bool
    {
        $loan = $this->loanRepo->findById($loanId);
        if (!$loan) {
            $this->session->setFlash('error', 'Loan not found.');
            return false;
        }

        if (!$this->checkPermission($adminUserId, $loan->alliance_id, 'can_manage_bank')) {
            $this->session->setFlash('error', 'You do not have permission to manage the bank.');
            return false;
        }
        
        if ($loan->status !== 'pending') {
            $this->session->setFlash('error', 'This loan is not pending approval.');
            return false;
        }
        
        $alliance = $this->allianceRepo->findById($loan->alliance_id);
        if ($alliance->bank_credits < $loan->amount_requested) {
            $this->session->setFlash('error', 'The alliance bank does not have enough credits to approve this loan.');
            return false;
        }
        
        $borrower = $this->userRepo->findById($loan->user_id);
        $borrowerResources = $this->resourceRepo->findByUserId($loan->user_id);
        if (!$borrower || !$borrowerResources) {
            $this->session->setFlash('error', 'Borrower not found.');
            return false;
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
            $this->session->setFlash('error', 'A database error occurred while approving the loan.');
            return false;
        }
        
        $this->session->setFlash('success', 'Loan approved. ' . number_format($loan->amount_requested) . ' credits sent to ' . $borrower->characterName . '.');
        return true;
    }

    /**
     * An admin denies a loan request.
     */
    public function denyLoan(int $adminUserId, int $loanId): bool
    {
        $loan = $this->loanRepo->findById($loanId);
        if (!$loan) {
            $this->session->setFlash('error', 'Loan not found.');
            return false;
        }

        if (!$this->checkPermission($adminUserId, $loan->alliance_id, 'can_manage_bank')) {
            $this->session->setFlash('error', 'You do not have permission to manage the bank.');
            return false;
        }
        
        if ($loan->status !== 'pending') {
            $this->session->setFlash('error', 'This loan is not pending approval.');
            return false;
        }
        
        $this->loanRepo->updateLoan($loanId, 'denied', 0);
        $this->session->setFlash('success', 'Loan request has been denied.');
        return true;
    }

    /**
     * A user repays part or all of a loan.
     */
    public function repayLoan(int $userId, int $loanId, int $amount): bool
    {
        $loan = $this->loanRepo->findById($loanId);
        
        if ($amount <= 0) {
            $this->session->setFlash('error', 'Repayment amount must be positive.');
            return false;
        }
        if (!$loan || $loan->user_id !== $userId) {
            $this->session->setFlash('error', 'This is not your loan.');
            return false;
        }
        if ($loan->status !== 'active') {
            $this->session->setFlash('error', 'This loan is not active.');
            return false;
        }
        
        $user = $this->userRepo->findById($userId);
        $resources = $this->resourceRepo->findByUserId($userId);
        
        if ($resources->credits < $amount) {
            $this->session->setFlash('error', 'You do not have enough credits on hand for this repayment.');
            return false;
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
            $this->session->setFlash('error', 'A database error occurred during repayment.');
            return false;
        }
        
        $this->session->setFlash('success', 'Thank you for your payment of ' . number_format($repayment) . ' credits.');
        if ($newStatus === 'paid') {
            $this->session->setFlash('success', 'Your loan has been fully repaid!');
        }
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