<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AuthService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles Authentication (Login, Register, Logout).
 * * Refactored for Strict Dependency Injection.
 * * Decoupled: Consumes ServiceResponse.
 * * RESPONSIBILITY SHIFT: This controller now manages Session persistence.
 */
class AuthController extends BaseController
{
    private AuthService $authService;

    /**
     * Strict DI Constructor.
     *
     * @param AuthService $authService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AuthService $authService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->authService = $authService;
    }

    /**
     * Displays the login page.
     */
    public function showLogin(): void
    {
        // Direct Session Check (Service no longer handles state)
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/login.php', ['title' => 'Login']);
    }

    /**
     * Handles the login form submission.
     */
    public function handleLogin(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/login');
            return;
        }

        // 3. Execute Service Logic
        $response = $this->authService->login($data['email'], $data['password']);

        // 4. Handle Response & Manage State
        if ($response->isSuccess()) {
            $user = $response->data['user'];

            // Security: Prevent Session Fixation
            session_regenerate_id(true);
            
            // Set Authentication State
            $this->session->set('user_id', $user->id);
            $this->session->set('alliance_id', $user->alliance_id);

            $this->redirect('/dashboard');
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/login');
        }
    }

    /**
     * Displays the registration page.
     */
    public function showRegister(): void
    {
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/register.php', ['title' => 'Register']);
    }

    /**
     * Handles the registration form submission.
     */
    public function handleRegister(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'email' => 'required|email',
            'character_name' => 'required|alphanumeric|min:3|max:20',
            'password' => 'required|min:6',
            'confirm_password' => 'required|match:password'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/register');
            return;
        }

        // 3. Execute Service Logic
        $response = $this->authService->register(
            $data['email'], 
            $data['character_name'], 
            $data['password'], 
            $data['confirm_password']
        );

        // 4. Handle Response & Manage State
        if ($response->isSuccess()) {
            $newUserId = $response->data['user_id'];

            // Security: Prevent Session Fixation
            session_regenerate_id(true);

            // Set Authentication State (New users have no alliance)
            $this->session->set('user_id', $newUserId);
            $this->session->set('alliance_id', null);

            $this->redirect('/dashboard');
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/register');
        }
    }

    /**
     * Logs the user out.
     */
    public function handleLogout(): void
    {
        // Directly destroy the session
        $this->session->destroy();
        
        // Rotate CSRF token on logout for safety
        $this->csrfService->rotateToken();
        
        // Note: We can't set a flash message here because the session was just destroyed.
        // If a message is required, we'd need to start a fresh session immediately.
        // For now, clean redirect.
        $this->redirect('/login');
    }
}