<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Database; 
use App\Core\Config; // --- NEW ---
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

class BaseController
{
    protected Session $session;
    protected CSRFService $csrfService;
    protected Config $config; // --- NEW ---
    
    protected LevelCalculatorService $levelCalculator;
    protected StatsRepository $statsRepo;

    public function __construct()
    {
        $this->session = new Session();
        $this->csrfService = new CSRFService();
        $this->config = new Config(); // --- NEW: Instantiate Config ---
        
        // Initialize services needed for global UI data
        $this->levelCalculator = new LevelCalculatorService();
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
        
        // --- Inject XP Data if Logged In ---
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

        // --- NEW: SEO Logic Processing ---
        $data['meta'] = $this->buildMeta($data);

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
     * Builds the SEO metadata array by merging defaults with page-specific data.
     * * @param array $data The data array passed from the child controller
     * @return array The processed meta tags
     */
    private function buildMeta(array $data): array
    {
        // 1. Load Defaults
        $defaults = $this->config->get('seo');
        $pageSeo = $data['seo'] ?? [];

        // 2. Determine Base URL (for Canonical/OG links)
        // Prefer APP_URL from env, fallback to calculated, then config default
        $baseUrl = $_ENV['APP_URL'] ?? $defaults['base_url_fallback'];
        
        // If running locally without ENV, try to detect
        if (empty($_ENV['APP_URL']) && isset($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $baseUrl = $protocol . $_SERVER['HTTP_HOST'];
        }
        
        // Clean trailing slash
        $baseUrl = rtrim($baseUrl, '/');
        $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
        $fullUrl = $baseUrl . $currentPath;

        // 3. Construct Title
        // Format: "Page Title | Site Name" or just "Site Name" if home
        $pageTitle = $data['title'] ?? null;
        $siteName = $defaults['site_name'];
        $separator = $defaults['separator'];
        
        if ($pageTitle) {
            $finalTitle = $pageTitle . $separator . $siteName;
        } else {
            $finalTitle = $siteName;
        }

        // 4. Merge Description & Keywords
        $description = $pageSeo['description'] ?? $defaults['description'];
        
        // Handle keywords (convert array to comma-string if needed)
        $keywords = $pageSeo['keywords'] ?? $defaults['keywords'];
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }

        // 5. Handle Image (Resolve relative paths to absolute)
        $image = $pageSeo['image'] ?? $defaults['image'];
        if (!filter_var($image, FILTER_VALIDATE_URL)) {
            $image = $baseUrl . '/' . ltrim($image, '/');
        }

        // 6. Return Structure
        return [
            'title' => $finalTitle,
            'description' => $description,
            'keywords' => $keywords,
            'url' => $fullUrl,
            'image' => $image,
            'site_name' => $siteName,
            'type' => $pageSeo['type'] ?? 'website',
            'twitter' => [
                'card' => $defaults['twitter']['card'],
                'site' => $defaults['twitter']['site'],
            ]
        ];
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