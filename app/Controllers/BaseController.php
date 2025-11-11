<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService; // Import the new service

class BaseController
{
    protected Session $session;
    protected CSRFService $csrfService; // Add CSRF service property

    public function __construct()
    {
        $this->session = new Session();
        $this->csrfService = new CSRFService(); // Initialize the service
    }

    /**
     * Renders a view file, wrapping it in the main layout.
     *
     * @param string $view The view file to render (e.g., 'auth/login.php')
     * @param array $data Data to extract into variables for the view
     */
    protected function render(string $view, array $data = []): void
    {
        // Make $session available in all views
        $session = $this->session;

        // --- NEW: Auto-generate and pass CSRF token to all views ---
        // We generate a new token every time a page with a form is loaded.
        $data['csrf_token'] = $this->csrfService->generateToken();
        
        // Extract the data array into local variables
        // e.g., ['user' => $user] becomes $user
        // and ['csrf_token' => '...'] becomes $csrf_token
        extract($data);

        // Start output buffering
        ob_start();

        // Include the specific page content
        require __DIR__ . '/../../views/' . $view;

        // Get the buffered content
        $content = ob_get_clean();

        // Now, load the main layout
        require __DIR__ . '/../../views/layouts/main.php';
    }

    /**
     * Redirects the user to a new path.
     *
     * @param string $path The path to redirect to (e.g., '/dashboard')
     */
    protected function redirect(string $path): void
    {
        header("Location: $path");
        exit;
    }
}