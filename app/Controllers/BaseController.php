<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * BaseController
 * * Acts as the parent for all HTTP controllers.
 * * STRICT DEPENDENCY INJECTION IMPLEMENTATION.
 */
class BaseController
{
    protected Session $session;
    protected CSRFService $csrfService;
    protected LevelCalculatorService $levelCalculator;
    protected StatsRepository $statsRepo;

    /**
     * Strict Constructor.
     * All dependencies must be injected. No manual instantiation allowed.
     *
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        $this->session = $session;
        $this->csrfService = $csrfService;
        $this->levelCalculator = $levelCalculator;
        $this->statsRepo = $statsRepo;
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
        
        // Inject XP Data if Logged In
        if ($this->session->has('user_id')) {
            $userId = $this->session->get('user_id');
            $stats = $this->statsRepo->findByUserId($userId);
            
            if ($stats) {
                // Calculate progress using the injected service
                $xpData = $this->levelCalculator->getLevelProgress($stats->experience, $stats->level);
                
                // Inject into data array for the navbar
                $data['global_xp_data'] = $xpData;
                $data['global_user_level'] = $stats->level;
            }
        }
        
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