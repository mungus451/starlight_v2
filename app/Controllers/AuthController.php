<?php

namespace App\Controllers;

use App\Models\Services\AuthService;
use App\Core\Validator; // --- NEW IMPORT ---

class AuthController extends BaseController
{
    private AuthService $authService;
    private Validator $validator; // --- NEW PROPERTY ---

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        $this->validator = new Validator(); // --- NEW INSTANTIATION ---
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
        // 1. CSRF Check
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/login');
            return;
        }
        
        // 2. Input Validation
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        
        $errors = $this->validator->validate($_POST, $rules);
        
        if (!empty($errors)) {
            // Flashing the first error is usually sufficient for UX, or flash all
            $this->session->setFlash('error', array_values($errors)[0]);
            $this->redirect('/login');
            return;
        }
        
        // 3. Business Logic
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
        // 1. CSRF Check
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token. Please try again.');
            $this->redirect('/register');
            return;
        }
        
        // 2. Input Validation
        $rules = [
            'email' => 'required|email',
            'character_name' => 'required|min:3|max:50|alphanumeric', // Added alphanumeric for safety
            'password' => 'required|min:3',
            'confirm_password' => 'required|match:password'
        ];
        
        $errors = $this->validator->validate($_POST, $rules);
        
        if (!empty($errors)) {
            $this->session->setFlash('error', array_values($errors)[0]);
            $this->redirect('/register');
            return;
        }
        
        // 3. Business Logic
        $email = $_POST['email'] ?? '';
        $characterName = $_POST['character_name'] ?? '';
        $password = $_POST['password'] ?? '';
        // confirm_password is no longer passed to the service

        if ($this->authService->register($email, $characterName, $password)) {
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