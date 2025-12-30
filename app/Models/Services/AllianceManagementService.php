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
use App\Core\Logger;
use PDO;
use Throwable;

/**
 * Handles all "write" logic for managing alliances.
 * * Refactored Phase 1.4: Strict MVC Compliance.
 * * Updated Phase 16: Added Alliance Profile Picture Upload support.
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
    private Logger $logger;

    private string $storageRoot;

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
        AllianceLoanRepository $loanRepo,
        Logger $logger
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
        $this->logger = $logger;

        // Define storage root relative to this file
        $this->storageRoot = realpath(__DIR__ . '/../../../storage');
    }

    /**
     * Updates public profile with file upload support.
     * 
     * @param int $adminId
     * @param int $allianceId
     * @param string $description
     * @param array $file $_FILES array for the image
     * @param bool $removePhoto Checkbox state
     * @param bool $isJoinable Recruitment state
     * @return ServiceResponse
     */
    public function updateProfile(int $adminId, int $allianceId, string $description, array $file, bool $removePhoto, bool $isJoinable): ServiceResponse
    {
        if (!$this->checkPermission($adminId, $allianceId, 'can_edit_profile')) {
            return ServiceResponse::error('You do not have permission to edit the profile.');
        }

        // Get current alliance data to handle file cleanup
        $alliance = $this->allianceRepo->findById($allianceId);
        if (!$alliance) {
            return ServiceResponse::error('Alliance not found.');
        }

        $currentPfpFilename = $alliance->profile_picture_url;
        $newPfpFilename = $currentPfpFilename; // Keep existing by default

        // Logic Branch 1: Remove Photo
        if ($removePhoto) {
            $this->deleteAvatarFile($currentPfpFilename);
            $newPfpFilename = null;
        }
        // Logic Branch 2: New File Uploaded
        elseif (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->processUploadedAvatar($file, $allianceId, $currentPfpFilename);
            
            if (!$uploadResult['success']) {
                return ServiceResponse::error($uploadResult['message']);
            }
            $newPfpFilename = $uploadResult['filename'];
        }
        // Check for upload errors other than "no file"
        elseif (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            return ServiceResponse::error('An error occurred during file upload. Code: ' . $file['error']);
        }

        $this->allianceRepo->updateProfile($allianceId, $description, $newPfpFilename ?? '', $isJoinable);
        return ServiceResponse::success('Alliance profile updated.');
    }

    // --- File Handling Helpers ---

    /**
     * Validates and saves a new alliance avatar.
     */
    private function processUploadedAvatar(array $file, int $allianceId, ?string $currentPfpFilename): array
    {
        // 1. Validate Size (2MB limit)
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File is too large. Maximum size is 2MB.', 'filename' => null];
        }

        // 2. Validate Type
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['success' => false, 'message' => 'Uploaded file is not a valid image.', 'filename' => null];
        }

        $mime = $imageInfo['mime'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        if (!in_array($mime, $allowedMimes)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed types: JPEG, PNG, GIF, WebP, AVIF.', 'filename' => null];
        }
        
        // 3. Ensure Directory Exists
        $uploadDir = $this->storageRoot . '/alliance_avatars/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Server configuration error: Cannot create storage directory.', 'filename' => null];
            }
        }

        // 4. Generate New Path
        $extension = image_type_to_extension($imageInfo[2]);
        $filename = "alliance_{$allianceId}_" . time() . $extension;
        $filePath = $uploadDir . $filename;

        // 5. Move File
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file. Check server permissions.', 'filename' => null];
        }
        
        // 6. Delete old file to save space
        $this->deleteAvatarFile($currentPfpFilename);

        return ['success' => true, 'message' => null, 'filename' => $filename];
    }

    private function deleteAvatarFile(?string $filename): void
    {
        if (empty($filename)) return;
        
        // Check if it's a local file (doesn't start with http)
        if (str_starts_with($filename, 'http')) return; 

        $filePath = $this->storageRoot . '/alliance_avatars/' . $filename;
        if (file_exists($filePath) && is_writable($filePath)) {
            @unlink($filePath);
        }
    }

    // --- Existing Service Methods ---

    public function getRoleManagementData(int $userId): ServiceResponse
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id === null) {
            return ServiceResponse::error('You must be in an alliance to manage roles.');
        }
        $allianceId = $user->alliance_id;

        $userRole = $this->roleRepo->findById($user->alliance_role_id);
        if (!$userRole || !$userRole->can_manage_roles) {
            return ServiceResponse::error('You do not have permission to manage roles.');
        }

        $roles = $this->roleRepo->findByAllianceId($allianceId);

        return ServiceResponse::success('Data retrieved', [
            'roles' => $roles,
            'alliance_id' => $allianceId
        ]);
    }

    public function createAlliance(int $userId, string $name, string $tag): ServiceResponse
    {
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

        $this->db->beginTransaction();
        try {
            $newAllianceId = $this->allianceRepo->create($name, $tag, $userId);

            $leaderRoleId = $this->roleRepo->create($newAllianceId, 'Leader', 1, [
                'can_edit_profile' => 1, 'can_manage_applications' => 1, 'can_invite_members' => 1,
                'can_kick_members' => 1, 'can_manage_roles' => 1, 'can_see_private_board' => 1,
                'can_manage_forum' => 1, 'can_manage_bank' => 1, 'can_manage_structures' => 1,
                'can_manage_diplomacy' => 1, 'can_declare_war' => 1
            ]);
            
            $this->roleRepo->create($newAllianceId, 'Recruit', 10, ['can_invite_members' => 1]);
            $this->roleRepo->create($newAllianceId, 'Member', 9, []);

            $newCredits = $resources->credits - $cost;
            $this->resourceRepo->updateCredits($userId, $newCredits);

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
            $this->logger->error('Alliance Creation Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred while creating the alliance.');
        }
    }

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
            return $this->acceptApplication($userId, null, $userId, $allianceId);
        }

        if ($this->appRepo->create($userId, $allianceId)) {
            return ServiceResponse::success('Application sent!');
        }

        return ServiceResponse::error('A database error occurred.');
    }

    public function cancelApplication(int $userId, int $appId): ServiceResponse
    {
        $app = $this->appRepo->findById($appId);

        if (!$app || $app->user_id !== $userId) {
            return ServiceResponse::error('Invalid application.');
        }

        $this->appRepo->delete($appId);
        return ServiceResponse::success('Application cancelled.');
    }

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

    public function acceptApplication(int $adminId, ?int $appId, ?int $targetUserId = null, ?int $targetAllianceId = null): ServiceResponse
    {
        $targetAllianceId = $targetAllianceId ?? 0;
        $targetUserId = $targetUserId ?? 0;

        if ($appId !== null) {
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
            $this->logger->error('Accept Application Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
        
        return ServiceResponse::success('Member accepted!', ['new_alliance_id' => $targetAllianceId]);
    }

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
            $this->logger->error('Invite User Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred while sending the invite.');
        }
        
        return ServiceResponse::success("{$targetUser->characterName} has accepted your invite!");
    }

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
            $this->logger->error('Delete Role Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }

        return ServiceResponse::success("Role '{$role->name}' deleted. Members reassigned to Recruit.");
    }

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
            $this->logger->error('Alliance Donation Error: ' . $e->getMessage());
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
            $this->logger->error('Alliance Loan Approve Error: ' . $e->getMessage());
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
            $this->logger->error('Alliance Loan Repay Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }
        
        $msg = 'Thank you for your payment of ' . number_format($repayment) . ' credits.';
        if ($newStatus === 'paid') {
            $msg .= ' Your loan has been fully repaid!';
        }
        return ServiceResponse::success($msg);
    }

    public function forgiveLoan(int $adminUserId, int $loanId): ServiceResponse
    {
        $loan = $this->loanRepo->findById($loanId);
        if (!$loan) {
            return ServiceResponse::error('Loan not found.');
        }

        // Strict Check: Only the Alliance Leader can forgive loans
        $alliance = $this->allianceRepo->findById($loan->alliance_id);
        if (!$alliance || $alliance->leader_id !== $adminUserId) {
            return ServiceResponse::error('Only the Alliance Leader can forgive loans.');
        }

        if ($loan->status !== 'active') {
            return ServiceResponse::error('This loan is not active.');
        }

        $borrower = $this->userRepo->findById($loan->user_id);
        if (!$borrower) {
            return ServiceResponse::error('Borrower not found.');
        }

        $this->db->beginTransaction();
        try {
            // Update loan to paid, 0 debt
            $this->loanRepo->updateLoan($loanId, 'paid', 0);
            
            // Log the forgiveness (Amount 0 as no credits moved)
            $message = "Loan for {$borrower->characterName} forgiven by leader.";
            $this->bankLogRepo->createLog($loan->alliance_id, $adminUserId, 'loan_forgiveness', 0, $message);
            
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->logger->error('Alliance Loan Forgive Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred.');
        }

        return ServiceResponse::success("Loan for {$borrower->characterName} has been forgiven.");
    }

    private function checkPermission(int $userId, int $allianceId, string $permissionName): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->alliance_id !== $allianceId) return false;
        
        $role = $this->roleRepo->findById($user->alliance_role_id);
        return $role && property_exists($role, $permissionName) && $role->{$permissionName} === true;
    }
}