<?php

namespace App\Middleware;

use App\Core\Session;

/**
 * Checks if a user is authenticated before allowing access to a route.
 * * Refactored: Removed AuthService dependency. Checks Session directly.
 */
class AuthMiddleware
{
    private Session $session;

    /**
     * DI Constructor.
     *
     * @param Session $session Injected by the container
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Runs the authentication check.
     * If the user is logged in, it does nothing.
     * If not, it redirects to /login and stops script execution.
     */
    public function handle(): void
    {
        // Direct check for the primary authentication key
        if ($this->session->has('user_id')) {
            // User is authenticated, allow request to continue
            return;
        }

        // User is not authenticated. Redirect to login.
        $this->session->setFlash('error', 'You must be logged in to view that page.');
        
        header('Location: /login');
        exit;
    }
}