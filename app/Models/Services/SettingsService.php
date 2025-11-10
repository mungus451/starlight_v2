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

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        // This service needs both repositories
        $this->userRepo = new UserRepository($this->db);
        $this->securityRepo = new SecurityRepository($this->db);
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
     * Updates non-sensitive user profile information.
     *
     * @param int $userId
     * @param string $bio
     * @param string $pfpUrl
     * @param string $phone
     * @return bool True on success
     */
    public function updateProfile(int $userId, string $bio, string $pfpUrl, string $phone): bool
    {
        // Simple validation (can be expanded)
        if (mb_strlen($bio) > 500) { // Using mb_strlen for multi-byte characters
            $this->session->setFlash('error', 'Bio must be 500 characters or less.');
            return false;
        }

        if (!empty($pfpUrl) && !filter_var($pfpUrl, FILTER_VALIDATE_URL)) {
            $this->session->setFlash('error', 'Profile picture must be a valid URL.');
            return false;
        }
        
        // TODO: Add phone number validation if desired

        if ($this->userRepo->updateProfile($userId, $bio, $pfpUrl, $phone)) {
            $this->session->setFlash('success', 'Profile updated successfully.');
            return true;
        }

        $this->session->setFlash('error', 'A database error occurred.');
        return false;
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