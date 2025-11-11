<?php

namespace App\Models\Services;

use App\Core\Database;
use App\Core\Session;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

class AuthService
{
    private PDO $db;
    private Session $session;
    
    // We now hold all repositories as properties
    private UserRepository $userRepository;
    private ResourceRepository $resourceRepository;
    private StatsRepository $statsRepository;
    private StructureRepository $structureRepository;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->session = new Session();
        
        // We instantiate all repositories, passing them the *same* DB connection
        $this->userRepository = new UserRepository($this->db);
        $this->resourceRepository = new ResourceRepository($this->db);
        $this->statsRepository = new StatsRepository($this->db);
        $this->structureRepository = new StructureRepository($this->db);
    }

    /**
     * Attempts to register a new user.
     * Creates rows in users, user_resources, user_stats, and user_structures.
     *
     * @param string $email
     * @param string $characterName
     * @param string $password
     * @param string $confirmPassword
     * @return bool
     */
    public function register(string $email, string $characterName, string $password, string $confirmPassword): bool
    {
        // 1. Validate input (no transaction needed yet)
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

        // 2. Begin Transaction
        $this->db->beginTransaction();

        try {
            // 3. Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // 4. Create all user data rows
            // 4a. Create the 'users' row and get the new ID
            $newUserId = $this->userRepository->createUser($email, $characterName, $passwordHash);
            
            // 4b. Create the default rows in the other tables
            $this->resourceRepository->createDefaults($newUserId);
            $this->statsRepository->createDefaults($newUserId);
            $this->structureRepository->createDefaults($newUserId);

            // 5. Commit Transaction
            $this->db->commit();
            
        } catch (Throwable $e) {
            // 6. Rollback on failure
            $this->db->rollBack();
            error_log('Registration Error: ' . $e->getMessage()); // Log for debugging
            $this->session->setFlash('error', 'A database error occurred during registration. Please try again.');
            return false;
        }

        // 7. Log the new user in
        $this->session->set('user_id', $newUserId);
        session_regenerate_id(true); // Protect against session fixation

        return true;
    }

    /**
     * Attempts to log a user in.
     * (No changes needed, it already uses the injected repository)
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login(string $email, string $password): bool
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->passwordHash)) {
            $this->session->setFlash('error', 'Invalid email or password.');
            return false;
        }

        session_regenerate_id(true);
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