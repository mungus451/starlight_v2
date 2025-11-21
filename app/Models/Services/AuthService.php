<?php

namespace App\Models\Services;

use App\Core\Session;
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

/**
 * Handles user registration and login logic.
 * * Refactored for Strict Dependency Injection.
 */
class AuthService
{
    private PDO $db;
    private Session $session;
    
    private UserRepository $userRepository;
    private ResourceRepository $resourceRepository;
    private StatsRepository $statsRepository;
    private StructureRepository $structureRepository;

    /**
     * DI Constructor.
     * All dependencies are injected by the container.
     *
     * @param PDO $db
     * @param Session $session
     * @param UserRepository $userRepository
     * @param ResourceRepository $resourceRepository
     * @param StatsRepository $statsRepository
     * @param StructureRepository $structureRepository
     */
    public function __construct(
        PDO $db,
        Session $session,
        UserRepository $userRepository,
        ResourceRepository $resourceRepository,
        StatsRepository $statsRepository,
        StructureRepository $structureRepository
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->userRepository = $userRepository;
        $this->resourceRepository = $resourceRepository;
        $this->statsRepository = $statsRepository;
        $this->structureRepository = $structureRepository;
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
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Registration Error: ' . $e->getMessage());
            $this->session->setFlash('error', 'A database error occurred during registration. Please try again.');
            return false;
        }

        // 7. Log the new user in
        $this->session->set('user_id', $newUserId);
        // A new user has no alliance, so we explicitly set it to null
        $this->session->set('alliance_id', null);
        
        session_regenerate_id(true); // Protect against session fixation

        return true;
    }

    /**
     * Attempts to log a user in.
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
        $this->session->set('alliance_id', $user->alliance_id);

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