<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\AllianceRepository;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ApplicationRepository;
use App\Models\Repositories\AllianceRoleRepository;
use App\Models\Services\AlliancePolicyService;
use PDO;
use Throwable;

/**
 * Handles all "write" logic for managing alliance membership.
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
    
    /**
     * --- NEW ---
     * Defines the server-side base path for the private 'storage' directory.
     */
    private string $storageRoot;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        $this->allianceRepo = new AllianceRepository($this->db);
        $this->userRepo = new UserRepository($this->db);
        $this->appRepo = new ApplicationRepository($this->db);
        $this->roleRepo = new AllianceRoleRepository($this->db);
        $this->policyService = new AlliancePolicyService();
        
        // --- NEW ---
        // Define the storage root relative to this file
        $this->storageRoot = realpath(__DIR__ . '/../../../storage');
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
        
        // Find their role
        $role = $this->roleRepo->findById($user->alliance_role_id);

        if ($role && $role->name === 'Leader') {
            $this->session->setFlash('error', 'Leaders must disband the alliance (feature coming soon). You cannot leave.');
            return false;
        }

        $this->userRepo->leaveAlliance($userId);
        $this->session->setFlash('success', 'You have left the alliance.');
        return true;
    }

    /**
     * An alliance admin accepts a pending application.
     */
    public function acceptApplication(int $adminId, int $appId): bool
    {
        $app = $this->appRepo->findById($appId);
        if (!$app) {
            $this->session->setFlash('error', 'Application not found.');
            return false;
        }

        // Check if admin has permission for this alliance
        if (!$this->checkPermission($adminId, $app->alliance_id, 'can_manage_applications')) {
            $this->session->setFlash('error', 'You do not have permission to do this.');
            return false;
        }

        $targetUser = $this->userRepo->findById($app->user_id);
        if ($targetUser->alliance_id !== null) {
            $this->session->setFlash('error', 'This user has already joined another alliance.');
            $this->appRepo->delete($appId); // Clean up the app
            return false;
        }
        
        // Find the default "Recruit" role for this alliance
        $recruitRole = $this->roleRepo->findDefaultRole($app->alliance_id, 'Recruit');
        if (!$recruitRole) {
            $this->session->setFlash('error', 'Critical error: Default "Recruit" role not found for this alliance.');
            return false;
        }

        // --- Transaction ---
        $this->db->beginTransaction();
        try {
            // 1. Set the user's alliance and role
            $this->userRepo->setAlliance($targetUser->id, $app->alliance_id, $recruitRole->id);

            // 2. Delete ALL applications for this user
            $this->appRepo->deleteByUser($targetUser->id);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Accept Application Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred.');
            return false;
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

        // Check if admin has permission for this alliance
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
     * This bypasses the application system and adds them directly.
     */
    public function inviteUser(int $inviterId, int $targetUserId): bool
    {
        // 1. Get Inviter and check permissions
        $inviterUser = $this->userRepo->findById($inviterId);
        if (!$inviterUser || $inviterUser->alliance_id === null) {
            $this->session->setFlash('error', 'You must be in an alliance to invite players.');
            return false;
        }

        if (!$this->checkPermission($inviterId, $inviterUser->alliance_id, 'can_invite_members')) {
            $this->session->setFlash('error', 'You do not have permission to invite members.');
            return false;
        }
        
        // 2. Get Target User and check eligibility
        $targetUser = $this->userRepo->findById($targetUserId);
        if (!$targetUser) {
            $this->session->setFlash('error', 'Target player not found.');
            return false;
        }
        if ($targetUser->alliance_id !== null) {
            $this.session->setFlash('error', 'That player is already in an alliance.');
            return false;
        }
        
        // 3. Get the default "Recruit" role for the inviter's alliance
        $recruitRole = $this->roleRepo->findDefaultRole($inviterUser->alliance_id, 'Recruit');
        if (!$recruitRole) {
            $this->session->setFlash('error', 'Critical error: Default "Recruit" role not found for your alliance.');
            return false;
        }
        
        // 4. Transaction: Add user to alliance, clear their old apps
        $this->db->beginTransaction();
        try {
            // 4a. Set the user's alliance and role
            $this->userRepo->setAlliance($targetUser->id, $inviterUser->alliance_id, $recruitRole->id);

            // 4b. Delete ALL other pending applications for this user
            $this->appRepo->deleteByUser($targetUser->id);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            error_log('Invite User Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred while sending the invite.');
            return false;
        }
        
        $this->session->setFlash('success', $targetUser->characterName . ' has accepted your invite and joined the alliance!');
        return true;
    }

    
    // --- METHOD MODIFIED FOR FILE UPLOADS ---

    /**
     * Updates an alliance's public profile.
     *
     * @param int $adminId
     * @param int $allianceId
     * @param string $description
     * @param array $file The $_FILES['profile_picture'] array
     * @param bool $removePhoto True if the "Remove Photo" checkbox was checked
     * @return bool
     */
    public function updateProfile(int $adminId, int $allianceId, string $description, array $file, bool $removePhoto): bool
    {
        if (!$this->checkPermission($adminId, $allianceId, 'can_edit_profile')) {
            $this->session->setFlash('error', 'You do not have permission to edit the profile.');
            return false;
        }
        
        $alliance = $this->allianceRepo->findById($allianceId);
        if (!$alliance) {
            $this->session->setFlash('error', 'Alliance not found.');
            return false;
        }

        $currentPfpFilename = $alliance->profile_picture_url;
        $newPfpFilename = $currentPfpFilename;

        // --- Logic Branch 1: Remove Photo ---
        if ($removePhoto) {
            $this->deleteAvatarFile($currentPfpFilename);
            $newPfpFilename = null; // Set to NULL in DB
        }
        
        // --- Logic Branch 2: New File Uploaded ---
        elseif (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            // A new file is present, process it
            $validatedFilename = $this->processUploadedAvatar($file, $allianceId, $currentPfpFilename);
            
            if ($validatedFilename === null) {
                // processUploadedAvatar sets its own flash error
                return false; 
            }
            $newPfpFilename = $validatedFilename;
        }

        // --- Logic Branch 3: No change ---
        // (Handled by defaults)

        $this->allianceRepo->updateProfile($allianceId, $description, $newPfpFilename);
        $this->session->setFlash('success', 'Alliance profile updated.');
        return true;
    }
    
    // --- NEW FILE UPLOAD METHODS (Adapted from SettingsService) ---

    /**
     * Validates and saves a new alliance avatar, and deletes the old one.
     *
     * @param array $file The file array from $_FILES
     * @param int $allianceId The alliance ID for naming
     * @param string|null $currentPfpFilename The filename of the old avatar to delete
     * @return string|null The new *filename* on success, or null on failure
     */
    private function processUploadedAvatar(array $file, int $allianceId, ?string $currentPfpFilename): ?string
    {
        // 1. Validate Upload Error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->session->setFlash('error', 'An error occurred during file upload. Code: ' . $file['error']);
            return null;
        }

        // 2. Validate Size (2MB limit)
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->session->setFlash('error', 'File is too large. Maximum size is 2MB.');
            return null;
        }

        // 3. Validate Type
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            $this->session->setFlash('error', 'Uploaded file is not a valid image.');
            return null;
        }

        $mime = $imageInfo['mime'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        if (!in_array($mime, $allowedMimes)) {
            $this->session->setFlash('error', 'Invalid file type. Allowed types: JPEG, PNG, GIF, WebP, AVIF.');
            return null;
        }
        
        // 4. Generate New Path
        $extension = image_type_to_extension($imageInfo[2]); // e.g., '.jpeg'
        $filename = "alliance_{$allianceId}_" . time() . $extension;
        
        // Save to the private 'storage/alliance_avatars' directory
        $uploadDir = $this->storageRoot . '/alliance_avatars/';
        $filePath = $uploadDir . $filename; // Full server path

        // 5. Move File
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->session->setFlash('error', 'Failed to save uploaded file. Check server permissions.');
            return null;
        }
        
        // 6. Delete old file (only after new one is successfully moved)
        $this->deleteAvatarFile($currentPfpFilename);

        return $filename;
    }

    /**
     * Safely deletes an old alliance avatar file from the server.
     *
     * @param string|null $filename The filename from the DB
     */
    private function deleteAvatarFile(?string $filename): void
    {
        if (empty($filename)) {
            return; // No file to delete
        }
        
        $filePath = $this->storageRoot . '/alliance_avatars/' . $filename;
        
        if (file_exists($filePath) && is_writable($filePath)) {
            @unlink($filePath);
        }
    }

    // --- END NEW FILE UPLOAD METHODS ---

    /**
     * Kicks a member from an alliance.
     */
    public function kickMember(int $adminId, int $targetUserId): bool
    {
        // --- 1. GET DATA ---
        $adminUser = $this->userRepo->findById($adminId);
        $targetUser = $this->userRepo->findById($targetUserId);
        
        if (!$adminUser || !$targetUser) {
            $this->session->setFlash('error', 'Invalid operation.');
            return false;
        }

        $adminRole = $this->roleRepo->findById($adminUser->alliance_role_id);
        $targetRole = $this->roleRepo->findById($targetUser->alliance_role_id); // findById handles null correctly

        if (!$adminRole) {
            $this->session->setFlash('error', 'You do not have an administrative role.');
            return false;
        }
        
        // --- 2. AUTHORIZATION ---
        $authError = $this->policyService->canKick($adminUser, $adminRole, $targetUser, $targetRole);
        if ($authError !== null) {
            $this->session->setFlash('error', $authError);
            return false;
        }

        // --- 3. EXECUTION ---
        $this->userRepo->leaveAlliance($targetUserId);
        $this->session->setFlash('success', $targetUser->characterName . ' has been kicked from the alliance.');
        return true;
    }

    /**
     * Changes a member's role.
     */
    public function changeMemberRole(int $adminId, int $targetUserId, int $newRoleId): bool
    {
        // --- 1. GET DATA ---
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

        // --- 2. AUTHORIZATION ---
        $authError = $this->policyService->canAssignRole($adminUser, $adminRole, $targetUser, $targetRole, $newRole);
        if ($authError !== null) {
            $this->session->setFlash('error', $authError);
            return false;
        }

        // --- 3. EXECUTION ---
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
        
        // TODO: Add more validation (name empty, etc.)
        
        // Use a high sort_order to ensure custom roles are at the bottom
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
        
        // Prevent editing default roles
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
            $this->session->setFlash('error', 'You do not have permission to delete roles. Maybe you should ask the leader nicely ;)');
            return false;
        }

        // Prevent deleting default roles
        if (in_array($role->name, ['Leader', 'Recruit', 'Member'])) {
            $this->session->setFlash('error', 'You cannot delete default roles.');
            return false;
        }
        
        // Find the "Recruit" role to reassign members to
        $recruitRole = $this->roleRepo->findDefaultRole($role->alliance_id, 'Recruit');
        if (!$recruitRole) {
            $this->session->setFlash('error', 'Critical error: Default "Recruit" role not found.');
            return false;
        }

        $this->db->beginTransaction();
        try {
            // 1. Reassign all members of this role to the "Recruit" role
            $this->roleRepo->reassignRoleMembers($roleId, $recruitRole->id);
            
            // 2. Delete the role
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
     * Helper function to check if a user has a specific permission.
     */
    private function checkPermission(int $userId, int $allianceId, string $permissionName): bool
    {
        $user = $this->userRepo->findById($userId);
        
        if (!$user || $user->alliance_id !== $allianceId) {
            return false;
        }
        
        $role = $this->roleRepo->findById($user->alliance_role_id);

        // Check if the role exists and the permission property is true
        return $role && property_exists($role, $permissionName) && $role->{$permissionName} === true;
    }
}