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
        // If user is already logged in, send them to the dashboard
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
        // Get data from the form
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Call the service to attempt login
        if ($this->authService->login($email, $password)) {
            // Success: Redirect to the dashboard
            $this->redirect('/dashboard');
        } else {
            // Failure: Redirect back to the login page
            // The AuthService has already set the flash message
            $this->redirect('/login');
        }
    }

    /**
     * Displays the registration page.
     */
    public function showRegister(): void
    {
        // If user is already logged in, send them to the dashboard
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
        // Get data from the form
        $email = $_POST['email'] ?? '';
        $characterName = $_POST['character_name'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Call the service to attempt registration
        if ($this->authService->register($email, $characterName, $password, $confirmPassword)) {
            // Success: Redirect to the dashboard (register logs them in)
            $this->redirect('/dashboard');
        } else {
            // Failure: Redirect back to the register page
            // The AuthService has already set the flash message
            $this->redirect('/register');
        }
    }

    /**
     * Logs the user out.
     */
    // --- THIS IS THE FIX ---
    public function handleLogout(): void
    {
        $this->authService->logout();
        $this->session->setFlash('success', 'You have been logged out.');
        $this->redirect('/login');
    }
}