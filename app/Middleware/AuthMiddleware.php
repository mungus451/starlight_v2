<?php

namespace App\Middleware;

use App\Core\Session;
use App\Models\Services\AuthService;

/**
 * Checks if a user is authenticated before allowing access to a route.
 * * Refactored for Strict Dependency Injection.
 */
class AuthMiddleware
{
    private AuthService $authService;
    private Session $session;

    /**
     * DI Constructor.
     *
     * @param AuthService $authService Injected by the container
     * @param Session $session Injected by the container
     */
    public function __construct(AuthService $authService, Session $session)
    {
        $this->authService = $authService;
        $this->session = $session;
    }

    /**
     * Runs the authentication check.
     * If the user is logged in, it does nothing.
     * If not, it redirects to /login and stops script execution.
     */
    public function handle(): void
    {
        if ($this->authService->isLoggedIn()) {
            // User is authenticated, allow request to continue
            return;
        }

        // User is not authenticated. Redirect to login.
        $this->session->setFlash('error', 'You must be logged in to view that page.');
        
        // Since this is not a controller, we use the raw PHP functions
        header('Location: /login');
        exit;
    }
}