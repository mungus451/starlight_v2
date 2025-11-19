<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Database; // Needed for Repo
use App\Models\Services\LevelCalculatorService; // --- NEW ---
use App\Models\Repositories\StatsRepository;    // --- NEW ---

class BaseController
{
    protected Session $session;
    protected CSRFService $csrfService;
    
    // --- NEW PROPERTIES ---
    protected LevelCalculatorService $levelCalculator;
    protected StatsRepository $statsRepo;

    public function __construct()
    {
        $this->session = new Session();
        $this->csrfService = new CSRFService();
        
        // --- NEW: Initialize services needed for global UI data ---
        $this->levelCalculator = new LevelCalculatorService();
        // We need to instantiate the DB for the repo
        $db = Database::getInstance();
        $this->statsRepo = new StatsRepository($db);
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

        // Auto-generate and pass CSRF token to all views
        $data['csrf_token'] = $this->csrfService->generateToken();
        
        // --- NEW: Inject XP Data if Logged In ---
        if ($this->session->has('user_id')) {
            $userId = $this->session->get('user_id');
            $stats = $this->statsRepo->findByUserId($userId);
            
            if ($stats) {
                // Calculate progress
                $xpData = $this->levelCalculator->getLevelProgress($stats->experience, $stats->level);
                
                // Inject into data array
                $data['global_xp_data'] = $xpData;
                $data['global_user_level'] = $stats->level;
            }
        }
        // --- END NEW ---
        
        // Extract the data array into local variables
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