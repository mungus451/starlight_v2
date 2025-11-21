<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\AuthService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles Authentication (Login, Register, Logout).
 * * Refactored for Strict Dependency Injection.
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
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AuthService $authService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->authService = $authService;
    }

    /**
     * Displays the login page.
     */
    public function showLogin(): void
    {
        if ($this->authService->isLoggedIn()) {
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
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/login');
            return;
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($this->authService->login($email, $password)) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }

    /**
     * Displays the registration page.
     */
    public function showRegister(): void
    {
        if ($this->authService->isLoggedIn()) {
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
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/register');
            return;
        }
        
        $email = $_POST['email'] ?? '';
        $characterName = $_POST['character_name'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($this->authService->register($email, $characterName, $password, $confirmPassword)) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/register');
        }
    }

    /**
     * Logs the user out.
     */
    public function handleLogout(): void
    {
        $this->authService->logout();
        $this->session->setFlash('success', 'You have been logged out.');
        $this->redirect('/login');
    }
}