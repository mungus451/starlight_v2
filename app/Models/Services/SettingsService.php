<?php

namespace App\Models\Services;

use App\Core\ServiceResponse;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\SecurityRepository;
use PDO;

/**
 * Handles all business logic for the Settings page.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse.
 */
class SettingsService
{
    private PDO $db;
    private UserRepository $userRepo;
    private SecurityRepository $securityRepo;
    private NotificationService $notificationService;
    private string $storageRoot;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param UserRepository $userRepo
     * @param SecurityRepository $securityRepo
     * @param NotificationService $notificationService
     */
    public function __construct(
        PDO $db,
        UserRepository $userRepo,
        SecurityRepository $securityRepo,
        NotificationService $notificationService
    ) {
        $this->db = $db;
        $this->userRepo = $userRepo;
        $this->securityRepo = $securityRepo;
        $this->notificationService = $notificationService;

        // Define storage root relative to this file
        $this->storageRoot = realpath(__DIR__ . '/../../../storage');
    }

    /**
     * Gets all data needed to render the settings page.
     *
     * @param int $userId
     * @return array Contains 'user' (User entity), 'security' (SecurityEntity or null), and 'notification_prefs' (UserNotificationPreferences entity)
     */
    public function getSettingsData(int $userId): array
    {
        $user = $this->userRepo->findById($userId);
        $security = $this->securityRepo->findByUserId($userId);
        $notificationPrefs = $this->notificationService->getPreferences($userId);

        return [
            'user' => $user,
            'security' => $security,
            'notification_prefs' => $notificationPrefs
        ];
    }

    /**
     * Updates user profile information, including file uploads.
     *
     * @param int $userId
     * @param string $bio
     * @param array $file The $_FILES['profile_picture'] array
     * @param string $phone
     * @param bool $removePhoto True if the "Remove Photo" checkbox was checked
     * @return ServiceResponse
     */
    public function updateProfile(int $userId, string $bio, array $file, string $phone, bool $removePhoto): ServiceResponse
    {
        // Validation
        if (mb_strlen($bio) > 500) {
            return ServiceResponse::error('Bio must be 500 characters or less.');
        }

        // Get current user data
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return ServiceResponse::error('User not found.');
        }
        
        $currentPfpFilename = $user->profile_picture_url;
        $newPfpFilename = $currentPfpFilename; // Default to old filename

        // Logic Branch 1: Remove Photo
        if ($removePhoto) {
            $this->deleteAvatarFile($currentPfpFilename);
            $newPfpFilename = null;
        }
        // Logic Branch 2: New File Uploaded
        elseif (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            // Helper now returns array [success (bool), result (string|null)]
            $uploadResult = $this->processUploadedAvatar($file, $userId, $currentPfpFilename);
            
            if (!$uploadResult['success']) {
                return ServiceResponse::error($uploadResult['message']);
            }
            $newPfpFilename = $uploadResult['filename'];
        }
        // Check for upload errors other than "no file"
        elseif (isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            return ServiceResponse::error('An error occurred during file upload. Code: ' . $file['error']);
        }

        // Update DB
        if ($this->userRepo->updateProfile($userId, $bio, $newPfpFilename, $phone)) {
            return ServiceResponse::success('Profile updated successfully.');
        }

        return ServiceResponse::error('A database error occurred.');
    }

    /**
     * Updates a user's email after verifying their password.
     */
    public function updateEmail(int $userId, string $newEmail, string $password): ServiceResponse
    {
        $verification = $this->verifyPassword($userId, $password);
        if (!$verification['success']) {
            return ServiceResponse::error($verification['message']);
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return ServiceResponse::error('Invalid email format.');
        }

        $existing = $this->userRepo->findByEmail($newEmail);
        if ($existing && $existing->id !== $userId) {
            return ServiceResponse::error('That email address is already in use.');
        }

        if ($this->userRepo->updateEmail($userId, $newEmail)) {
            return ServiceResponse::success('Email updated successfully.');
        }

        return ServiceResponse::error('A database error occurred.');
    }

    /**
     * Updates a user's password after verifying their old one.
     */
    public function updatePassword(int $userId, string $oldPass, string $newPass, string $confirmPass): ServiceResponse
    {
        $verification = $this->verifyPassword($userId, $oldPass);
        if (!$verification['success']) {
            return ServiceResponse::error($verification['message']);
        }

        if (strlen($newPass) < 3) {
            return ServiceResponse::error('New password must be at least 3 characters long.');
        }

        if ($newPass !== $confirmPass) {
            return ServiceResponse::error('New passwords do not match.');
        }

        $newPasswordHash = password_hash($newPass, PASSWORD_DEFAULT);

        if ($this->userRepo->updatePassword($userId, $newPasswordHash)) {
            return ServiceResponse::success('Password updated successfully.');
        }

        return ServiceResponse::error('A database error occurred.');
    }

    /**
     * Updates a user's security questions after verifying their password.
     */
    public function updateSecurityQuestions(int $userId, string $q1, string $a1, string $q2, string $a2, string $password): ServiceResponse
    {
        $verification = $this->verifyPassword($userId, $password);
        if (!$verification['success']) {
            return ServiceResponse::error($verification['message']);
        }

        if (empty($q1) || empty($a1) || empty($q2) || empty($a2)) {
            return ServiceResponse::error('All questions and answers must be filled out.');
        }

        $a1_hash = password_hash($a1, PASSWORD_DEFAULT);
        $a2_hash = password_hash($a2, PASSWORD_DEFAULT);

        if ($this->securityRepo->createOrUpdate($userId, $q1, $a1_hash, $q2, $a2_hash)) {
            return ServiceResponse::success('Security questions updated successfully.');
        }

        return ServiceResponse::error('A database error occurred.');
    }

    // --- Helpers ---

    /**
     * Validates and saves a new avatar, and deletes the old one.
     * Returns an array structure to indicate status without Session side-effects.
     * @return array{success: bool, message: string|null, filename: string|null}
     */
    private function processUploadedAvatar(array $file, int $userId, ?string $currentPfpFilename): array
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
        
        // 3. Generate New Path
        $extension = image_type_to_extension($imageInfo[2]);
        $filename = "user_{$userId}_" . time() . $extension;
        
        $uploadDir = $this->storageRoot . '/avatars/';
        $filePath = $uploadDir . $filename;

        // 4. Move File
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file. Check server permissions.', 'filename' => null];
        }
        
        // 5. Delete old file
        $this->deleteAvatarFile($currentPfpFilename);

        return ['success' => true, 'message' => null, 'filename' => $filename];
    }

    /**
     * Safely deletes an old avatar file from the server.
     */
    private function deleteAvatarFile(?string $filename): void
    {
        if (empty($filename)) {
            return;
        }
        
        $filePath = $this->storageRoot . '/avatars/' . $filename;
        
        if (file_exists($filePath) && is_writable($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Security Gate: Verifies a user's password.
     * @return array{success: bool, message: string|null, user: User|null}
     */
    private function verifyPassword(int $userId, string $password): array
    {
        if (empty($password)) {
            return ['success' => false, 'message' => 'Please enter your current password to make this change.', 'user' => null];
        }

        $user = $this->userRepo->findById($userId);

        if (!$user || !password_verify($password, $user->passwordHash)) {
            return ['success' => false, 'message' => 'Incorrect current password.', 'user' => null];
        }

        return ['success' => true, 'message' => null, 'user' => $user];
    }

    /**
     * Updates the user's notification preferences.
     *
     * @param int $userId
     * @param bool $attackEnabled
     * @param bool $spyEnabled
     * @param bool $allianceEnabled
     * @param bool $systemEnabled
     * @param bool $pushEnabled
     * @return ServiceResponse
     */
    public function updateNotificationPreferences(
        int $userId,
        bool $attackEnabled,
        bool $spyEnabled,
        bool $allianceEnabled,
        bool $systemEnabled,
        bool $pushEnabled
    ): ServiceResponse {
        return $this->notificationService->updatePreferences(
            $userId,
            $attackEnabled,
            $spyEnabled,
            $allianceEnabled,
            $systemEnabled,
            $pushEnabled
        );
    }
}