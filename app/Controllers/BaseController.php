<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * BaseController
 * * Acts as the parent for all HTTP controllers.
 * * STRICT DEPENDENCY INJECTION IMPLEMENTATION.
 * * Refactored Phase 2: Decoupled from Domain Logic (Level/Stats).
 */
class BaseController
{
    protected Session $session;
    protected CSRFService $csrfService;
    protected Validator $validator;
    protected ViewContextService $viewContextService; // --- REPLACES Domain Services ---

    /**
     * Strict Constructor.
     * All dependencies must be injected.
     *
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        $this->session = $session;
        $this->csrfService = $csrfService;
        $this->validator = $validator;
        $this->viewContextService = $viewContextService;
    }

    /**
     * Centralized Input Validation.
     * 
     * Validates and sanitizes input. If validation fails, it automatically
     * sets flash errors and redirects back to the previous page.
     *
     * @param array $data Input data (usually $_POST)
     * @param array $rules Validation rules (e.g. ['email' => 'required|email'])
     * @return array The clean, sanitized data (only fields defined in rules)
     */
    protected function validate(array $data, array $rules): array
    {
        // Create validation instance
        $val = $this->validator->make($data, $rules);

        // Check for failures
        if ($val->fails()) {
            // Combine all errors into a single string for the simple Flash system
            $errorMessages = implode(' ', $val->errors());
            
            $this->session->setFlash('error', $errorMessages);
            
            // Redirect back to the form
            $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard';
            header("Location: $referer");
            exit; // Stop execution immediately
        }

        // Return only the safe, sanitized data
        return $val->validated();
    }

    /**
     * Renders a view file, wrapping it in the main layout.
     * Handles extraction of Global View Data (Session, XP, CSRF) to keep Views pure.
     *
     * @param string $view The view file to render (e.g., 'auth/login.php')
     * @param array $data Data to extract into variables for the view
     */
    protected function render(string $view, array $data = []): void
    {
        // 1. Global: CSRF Token
        $data['csrf_token'] = $this->csrfService->generateToken();
        
        // 2. Global: Authentication State
        $data['isLoggedIn'] = $this->session->has('user_id');
        $data['currentUserAllianceId'] = $this->session->get('alliance_id');
        
        // 3. Global: Flash Messages (Extract and Remove)
        $data['flashError'] = $this->session->getFlash('error');
        $data['flashSuccess'] = $this->session->getFlash('success');

        // 4. Global: View Context (XP, Level, etc.) - REFACTORED
        if ($data['isLoggedIn']) {
            $userId = $this->session->get('user_id');
            
            // Delegate to service, keep Controller clean
            $globalContext = $this->viewContextService->getGlobalLayoutData($userId);
            
            // Merge context into data
            $data = array_merge($data, $globalContext);
        }
        
        // Extract the data array into local variables for the view
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