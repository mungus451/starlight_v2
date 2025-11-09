<?php

namespace App\Controllers;

use App\Core\Session;

class BaseController
{
    protected Session $session;

    public function __construct()
    {
        $this->session = new Session();
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

        // Extract the data array into local variables
        // e.g., ['user' => $user] becomes $user
        extract($data);

        // Start output buffering
        ob_start();

        // --- THIS IS THE FIX ---
        // Include the specific page content
        // e.g., /views/auth/login.php
        require __DIR__ . '/../../views/' . $view;

        // Get the buffered content
        $content = ob_get_clean();

        // --- THIS IS ALSO THE FIX ---
        // Now, load the main layout, which will use the $content variable
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