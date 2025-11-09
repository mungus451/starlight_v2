<?php

namespace App\Models\Services;

use App\Core\Session;
use App\Models\Entities\User;
use App\Models\Repositories\UserRepository;

class AuthService
{
    private UserRepository $userRepository;
    private Session $session;

    public function __construct()
    {
        // Our service new-ups its dependencies
        $this->userRepository = new UserRepository();
        $this->session = new Session();
    }

    /**
     * Attempts to register a new user.
     * Returns true on success, false on validation failure.
     *
     * @param string $email
     * @param string $characterName
     * @param string $password
     * @param string $confirmPassword
     * @return bool
     */
    public function register(string $email, string $characterName, string $password, string $confirmPassword): bool
    {
        // 1. Validate input
        if ($password !== $confirmPassword) {
            $this->session->setFlash('error', 'Passwords do not match.');
            return false;
        }

        if (strlen($password) < 3) {
            $this->session->setFlash('error', 'Password must be at least 3 characters long.');
            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->setFlash('error', 'Invalid email address.');
            return false;
        }

        if ($this->userRepository->findByEmail($email)) {
            $this->session->setFlash('error', 'Email address is already in use.');
            return false;
        }

        if ($this->userRepository->findByCharacterName($characterName)) {
            $this->session->setFlash('error', 'Character name is already taken.');
            return false;
        }

        // 2. Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // 3. Create user
        $newUserId = $this->userRepository->createUser($email, $characterName, $passwordHash);

        // 4. Log the user in
        $this->session->set('user_id', $newUserId);
        session_regenerate_id(true); // Protect against session fixation

        return true;
    }

    /**
     * Attempts to log a user in.
     * Returns true on success, false on failure.
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);

        // 1. Check if user exists and password is correct
        if (!$user || !password_verify($password, $user->passwordHash)) {
            $this->session->setFlash('error', 'Invalid email or password.');
            return false;
        }

        // 2. Log them in
        session_regenerate_id(true); // Protect against session fixation
        $this->session->set('user_id', $user->id);

        return true;
    }

    /**
     * Logs the user out.
     */
    public function logout(): void
    {
        $this->session->destroy();
    }

    /**
     * Checks if a user is currently logged in.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->session->has('user_id');
    }
}