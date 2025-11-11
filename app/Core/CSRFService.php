<?php

namespace App\Core;

use App\Core\Session;

/**
 * Handles Cross-Site Request Forgery (CSRF) protection.
 * Uses single-use tokens stored in the session.
 */
class CSRFService
{
    private Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    /**
     * Generates a new, unique CSRF token, stores it in the session, and returns it.
     *
     * @return string The generated token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->session->set('csrf_token', $token);
        return $token;
    }

    /**
     * Validates a given token against the one stored in the session.
     * This is a single-use token; it is cleared after a successful check.
     *
     * @param string $token The token submitted from a form
     * @return bool True if valid, false otherwise
     */
    public function validateToken(string $token): bool
    {
        $sessionToken = $this->session->get('csrf_token');

        if (empty($token) || empty($sessionToken)) {
            return false;
        }

        // Use hash_equals for timing-attack-safe comparison
        $isValid = hash_equals($sessionToken, $token);

        // Token is single-use. Clear it after checking.
        $this->session->remove('csrf_token');

        return $isValid;
    }
}