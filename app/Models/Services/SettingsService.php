<?php

namespace App\Models\Services;

use App\Core\Session;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\SecurityRepository;
use PDO;

/**
 * Handles all business logic for the Settings page.
 * * Refactored for Strict Dependency Injection.
 */
class SettingsService
{
    private PDO $db;
    private Session $session;
    private UserRepository $userRepo;
    private SecurityRepository $securityRepo;
    private string $storageRoot;

    /**
     * DI Constructor.
     *
     * @param PDO $db
     * @param Session $session
     * @param UserRepository $userRepo
     * @param SecurityRepository $securityRepo
     */
    public function __construct(
        PDO $db,
        Session $session,
        UserRepository $userRepo,
        SecurityRepository $securityRepo
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->userRepo = $userRepo;
        $this->securityRepo = $securityRepo;

        // Define storage root relative to this file
        // __DIR__ is /app/Models/Services, so ../../../ is the project root
        $this->storageRoot = realpath(__DIR__ . '/../../../storage');
    }

    /**
     * Gets all data needed to render the settings page.
     *
     * @param int $userId
     * @return array Contains 'user' (entity) and 'security' (entity or null)
     */
    public function getSettingsData(int $userId): array
    {
        $user = $this->userRepo->findById($userId);
        $security = $this->securityRepo->findByUserId($userId);

        return [
            'user' => $user,
            'security' => $security
        ];
    }

    /**
     * Updates user profile information, now handling file uploads.
     *
     * @param int $userId
     * @param string $bio
     * @param array $file The $_FILES['profile_picture'] array
     * @param string $phone
     * @param bool $removePhoto True if the "Remove Photo" checkbox was checked
     * @return bool True on success
     */
    public function updateProfile(int $userId, string $bio, array $file, string $phone, bool $removePhoto): bool
    {
        // Simple validation
        if (mb_strlen($bio) > 500) {
            $this->session->setFlash('error', 'Bio must be 500 characters or less.');
            return false;
        }

        // Get the current user data *before* making changes
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            $this->session->setFlash('error', 'User not found.');
            return false;
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
            $validatedFilename = $this->processUploadedAvatar($file, $userId, $currentPfpFilename);
            
            if ($validatedFilename === null) {
                return false; // processUploadedAvatar sets its own flash error
            }
            $newPfpFilename = $validatedFilename;
        }

        // Update DB
        if ($this->userRepo->updateProfile($userId, $bio, $newPfpFilename, $phone)) {
            $this->session->setFlash('success', 'Profile updated successfully.');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * Validates and saves a new avatar, and deletes the old one.
     */
    private function processUploadedAvatar(array $file, int $userId, ?string $currentPfpFilename): ?string
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
        $extension = image_type_to_extension($imageInfo[2]);
        $filename = "user_{$userId}_" . time() . $extension;
        
        $uploadDir = $this->storageRoot . '/avatars/';
        $filePath = $uploadDir . $filename;

        // 5. Move File
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->session->setFlash('error', 'Failed to save uploaded file. Check server permissions.');
            return null;
        }
        
        // 6. Delete old file
        $this->deleteAvatarFile($currentPfpFilename);

        return $filename;
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
     * Updates a user's email after verifying their password.
     */
    public function updateEmail(int $userId, string $newEmail, string $password): bool
    {
        $user = $this->verifyPassword($userId, $password);
        if (is_null($user)) {
            return false;
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('error', 'Invalid email format.');
            return false;
        }

        $existing = $this->userRepo->findByEmail($newEmail);
        if ($existing && $existing->id !== $userId) {
            $this->session->setFlash('error', 'That email address is already in use.');
            return false;
        }

        if ($this->userRepo->updateEmail($userId, $newEmail)) {
            $this->session->setFlash('success', 'Email updated successfully.');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * Updates a user's password after verifying their old one.
     */
    public function updatePassword(int $userId, string $oldPass, string $newPass, string $confirmPass): bool
    {
        $user = $this->verifyPassword($userId, $oldPass);
        if (is_null($user)) {
            return false;
        }

        if (strlen($newPass) < 3) {
            $this->session->setFlash('error', 'New password must be at least 3 characters long.');
            return false;
        }

        if ($newPass !== $confirmPass) {
            $this->session->setFlash('error', 'New passwords do not match.');
            return false;
        }

        $newPasswordHash = password_hash($newPass, PASSWORD_DEFAULT);

        if ($this->userRepo->updatePassword($userId, $newPasswordHash)) {
            $this->session->setFlash('success', 'Password updated successfully.');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * Updates a user's security questions after verifying their password.
     */
    public function updateSecurityQuestions(int $userId, string $q1, string $a1, string $q2, string $a2, string $password): bool
    {
        $user = $this->verifyPassword($userId, $password);
        if (is_null($user)) {
            return false;
        }

        if (empty($q1) || empty($a1) || empty($q2) || empty($a2)) {
            $this->session->setFlash('error', 'All questions and answers must be filled out.');
            return false;
        }

        $a1_hash = password_hash($a1, PASSWORD_DEFAULT);
        $a2_hash = password_hash($a2, PASSWORD_DEFAULT);

        if ($this->securityRepo->createOrUpdate($userId, $q1, $a1_hash, $q2, $a2_hash)) {
            $this->session->setFlash('success', 'Security questions updated successfully.');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * Security Gate: Verifies a user's password.
     */
    private function verifyPassword(int $userId, string $password): ?User
    {
        if (empty($password)) {
            $this->session->setFlash('error', 'Please enter your current password to make this change.');
            return null;
        }

        $user = $this->userRepo->findById($userId);

        if (!$user || !password_verify($password, $user->passwordHash)) {
            $this->session->setFlash('error', 'Incorrect current password.');
            return null;
        }

        return $user;
    }
}