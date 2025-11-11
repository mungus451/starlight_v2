<?php

namespace App\Controllers;

use App\Models\Services\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
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
        // --- NEW: CSRF Token Validation ---
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/login');
            return;
        }
        // --- End CSRF Check ---
        
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
        // --- NEW: CSRF Token Validation ---
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/register');
            return;
        }
        // --- End CSRF Check ---
        
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