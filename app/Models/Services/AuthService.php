<?php

namespace App\Models\Services;

use App\Core\ServiceResponse; // --- NEW IMPORT ---
use App\Models\Repositories\UserRepository;
use App\Models\Repositories\ResourceRepository;
use App\Models\Repositories\StatsRepository;
use App\Models\Repositories\StructureRepository;
use PDO;
use Throwable;

/**
 * Handles user registration and login logic.
 * * Refactored for Strict Dependency Injection.
 * * Decoupled from Session: Returns ServiceResponse containing User entity.
 */
class AuthService
{
    private PDO $db;
    
    private UserRepository $userRepository;
    private ResourceRepository $resourceRepository;
    private StatsRepository $statsRepository;
    private StructureRepository $structureRepository;

    /**
     * DI Constructor.
     * REMOVED: Session dependency.
     *
     * @param PDO $db
     * @param UserRepository $userRepository
     * @param ResourceRepository $resourceRepository
     * @param StatsRepository $statsRepository
     * @param StructureRepository $structureRepository
     */
    public function __construct(
        PDO $db,
        UserRepository $userRepository,
        ResourceRepository $resourceRepository,
        StatsRepository $statsRepository,
        StructureRepository $structureRepository
    ) {
        $this->db = $db;
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
     * @return ServiceResponse Returns success with 'user_id' or error message.
     */
    public function register(string $email, string $characterName, string $password, string $confirmPassword): ServiceResponse
    {
        // 1. Validate input (Business Logic)
        if ($password !== $confirmPassword) {
            return ServiceResponse::error('Passwords do not match.');
        }

        if (strlen($password) < 3) {
            return ServiceResponse::error('Password must be at least 3 characters long.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ServiceResponse::error('Invalid email address.');
        }

        if ($this->userRepository->findByEmail($email)) {
            return ServiceResponse::error('Email address is already in use.');
        }

        if ($this->userRepository->findByCharacterName($characterName)) {
            return ServiceResponse::error('Character name is already taken.');
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
            
            // Return success with ID so controller can handle session
            return ServiceResponse::success('Registration successful.', ['user_id' => $newUserId]);
            
        } catch (Throwable $e) {
            // 6. Rollback on failure
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Registration Error: ' . $e->getMessage());
            return ServiceResponse::error('A database error occurred during registration. Please try again.');
        }
    }

    /**
     * Attempts to authenticate a user.
     *
     * @param string $email
     * @param string $password
     * @return ServiceResponse Returns success with 'user' entity or error message.
     */
    public function login(string $email, string $password): ServiceResponse
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !password_verify($password, $user->passwordHash)) {
            return ServiceResponse::error('Invalid email or password.');
        }

        // Return success with the User entity so controller can read ID and Alliance info
        return ServiceResponse::success('Login successful.', ['user' => $user]);
    }

    /**
     * Checks if a user is currently logged in.
     * Note: This is a stateless check helper, or effectively deprecated in Service layer
     * since Services shouldn't know about Session. 
     * We keep it but it needs Session context passed in, OR we remove it.
     * 
     * DECISION: Remove it. Authentication state checking belongs in Middleware/Controller.
     * However, to avoid breaking the AuthController before it is updated, we will 
     * temporarily remove it from here and rely on the Controller/Middleware to check Session directly.
     */
}