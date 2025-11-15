<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\SecurityRepository;
use PDO;

/**
 * Handles all business logic for the Settings page.
 */
class SettingsService
{
    private PDO $db;
    private Session $session;
    private UserRepository $userRepo;
    private SecurityRepository $securityRepo;

    /**
     * --- CHANGED ---
     * Defines the server-side base path for the private 'storage' directory.
     * e.g., /usr/local/var/www/starlight_v2/storage
     */
    private string $storageRoot;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        // This service needs both repositories
        $this->userRepo = new UserRepository($this->db);
        $this->securityRepo = new SecurityRepository($this->db);

        // --- CHANGED ---
        // Define the storage root relative to this file
        // __DIR__ is /app/Models/Services, so ../../../ is /
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
        // Simple validation (can be expanded)
        if (mb_strlen($bio) > 500) { // Using mb_strlen for multi-byte characters
            $this->session->setFlash('error', 'Bio must be 500 characters or less.');
            return false;
        }

        // Get the current user data *before* making changes
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            $this->session->setFlash('error', 'User not found.');
            return false;
        }
        
        // --- CHANGED ---
        // The URL is now just the filename (e.g., 'user_123.jpg')
        $currentPfpFilename = $user->profile_picture_url;
        $newPfpFilename = $currentPfpFilename; // Default to old filename

        // --- Logic Branch 1: Remove Photo ---
        if ($removePhoto) {
            $this->deleteAvatarFile($currentPfpFilename);
            $newPfpFilename = null; // Set to NULL in DB
        }
        
        // --- Logic Branch 2: New File Uploaded ---
        // Check if a file was *actually* uploaded, not just an empty form field
        elseif (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
            // A new file is present, process it
            // --- CHANGED ---
            // This now returns a filename or null
            $validatedFilename = $this->processUploadedAvatar($file, $userId, $currentPfpFilename);
            
            if ($validatedFilename === null) {
                // processUploadedAvatar sets its own flash error
                return false; 
            }
            $newPfpFilename = $validatedFilename;
        }

        // --- Logic Branch 3: No change ---
        // If $removePhoto is false AND no new file was uploaded,
        // $newPfpFilename remains $currentPfpFilename, so only bio/phone will be updated.

        // --- CHANGED ---
        // Pass the new filename (or null) to the repository
        if ($this->userRepo->updateProfile($userId, $bio, $newPfpFilename, $phone)) {
            $this->session->setFlash('success', 'Profile updated successfully.');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
    }

    /**
     * Validates and saves a new avatar, and deletes the old one.
     *
     * @param array $file The file array from $_FILES
     * @param int $userId The user ID for naming
     * @param string|null $currentPfpFilename The filename of the old avatar to delete
     * @return string|null The new *filename* on success, or null on failure
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
        
        // 4. Generate New Path and URL
        $extension = image_type_to_extension($imageInfo[2]); // e.g., '.jpeg'
        $filename = "user_{$userId}_" . time() . $extension;
        
        // --- CHANGED ---
        // Save to the private 'storage' directory
        $uploadDir = $this->storageRoot . '/avatars/';
        $filePath = $uploadDir . $filename; // Full server path

        // 5. Move File
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            $this->session->setFlash('error', 'Failed to save uploaded file. Check server permissions.');
            return null;
        }
        
        // 6. Delete old file (only after new one is successfully moved)
        $this->deleteAvatarFile($currentPfpFilename);

        // --- CHANGED ---
        // Return only the filename
        return $filename;
    }

    /**
     * Safely deletes an old avatar file from the server.
     *
     * @param string|null $filename The filename from the DB (e.g., 'user_123.jpg')
     */
    private function deleteAvatarFile(?string $filename): void
    {
        if (empty($filename)) {
            return; // No file to delete
        }
        
        // Create the full, absolute server path from the filename
        $filePath = $this->storageRoot . '/avatars/' . $filename;
        
        // Check that the file exists and is writable before trying to delete
        if (file_exists($filePath) && is_writable($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Updates a user's email after verifying their password.
     *
     * @param int $userId
     * @param string $newEmail
     * @param string $password
     * @return bool True on success
     */
    public function updateEmail(int $userId, string $newEmail, string $password): bool
    {
        // Security Gate: Verify password before proceeding
        $user = $this->verifyPassword($userId, $password);
        if (is_null($user)) {
            return false; // verifyPassword sets the flash message
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('error', 'Invalid email format.');
            return false;
        }

        // Check if the new email is already in use by another user
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
     *
     * @param int $userId
     * @param string $oldPass
     * @param string $newPass
     * @param string $confirmPass
     * @return bool True on success
     */
    public function updatePassword(int $userId, string $oldPass, string $newPass, string $confirmPass): bool
    {
        // Security Gate: Verify password before proceeding
        $user = $this->verifyPassword($userId, $oldPass);
        if (is_null($user)) {
            return false; // verifyPassword sets the flash message
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
     *
     * @param int $userId
     * @param string $q1
     * @param string $a1
     * @param string $q2
     * @param string $a2
     * @param string $password
     * @return bool True on success
     */
    public function updateSecurityQuestions(int $userId, string $q1, string $a1, string $q2, string $a2, string $password): bool
    {
        // Security Gate: Verify password before proceeding
        $user = $this->verifyPassword($userId, $password);
        if (is_null($user)) {
            return false; // verifyPassword sets the flash message
        }

        if (empty($q1) || empty($a1) || empty($q2) || empty($a2)) {
            $this->session->setFlash('error', 'All questions and answers must be filled out.');
            return false;
        }

        // Hash the new answers
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
     * Security Gate.
     * Verifies a user's password.
     *
     * @param int $userId
     * @param string $password
     * @return User|null The User entity if successful, null on failure.
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

        return $user; // Success
    }
}