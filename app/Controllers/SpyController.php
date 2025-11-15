<?php

namespace App\Controllers;

use App\Models\Services\SpyService;

/**
 * Handles all HTTP requests for the Espionage feature.
 */
class SpyController extends BaseController
{
    private SpyService $spyService;

    public function __construct()
    {
        parent::__construct();
        $this->spyService = new SpyService();
    }

    /**
     * Displays the main spy page (conduct operation).
     * --- THIS METHOD IS UPDATED ---
     */
    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        // --- NEW: Get page from router ---
        $page = (int)($vars['page'] ?? 1);
        
        // --- NEW: Pass page to service ---
        $data = $this->spyService->getSpyData($userId, $page);

        $data['layoutMode'] = 'full';

        $this->render('spy/show.php', $data + ['title' => 'Espionage']);
    }

    /**
     * Handles the "all-in" spy operation form.
     */
    public function handleSpy(): void
    {
        // 1. Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/spy');
            return;
        }
        
        // 2. Get data from form
        $userId = $this->session->get('user_id');
        $targetName = (string)($_POST['target_name'] ?? '');
        
        // 3. Call the service (it handles all logic and flash messages)
        // We do not pass an amount, as it's an "all-in" operation.
        $this->spyService->conductOperation($userId, $targetName);
        
        // 4. Redirect to the reports page to see the new report
        $this->redirect('/spy/reports');
    }

    /**
     * --- MODIFIED METHOD ---
     * Displays the list of past spy reports.
     */
    public function showReports(): void
    {
        $userId = $this->session->get('user_id');
        $reports = $this->spyService->getSpyReports($userId);

        $this->render('spy/reports.php', [
            'title' => 'Spy Reports',
            'reports' => $reports,
            'userId' => $userId, // Pass the user's ID to the view
            'layoutMode' => 'full' // Add full-width layout
        ]);
    }

    /**
     * --- MODIFIED METHOD ---
     * Displays a single, detailed spy report.
     * The {id} is passed in from the router.
     */
    public function showReport(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $reportId = (int)($vars['id'] ?? 0);
        
        $report = $this->spyService->getSpyReport($reportId, $userId);

        if (is_null($report)) {
            // Report not found or doesn't belong to the user
            $this->session->setFlash('error', 'Spy report not found.');
            $this->redirect('/spy/reports');
            return;
        }

        $this->render('spy/report_view.php', [
            'title' => 'Spy Report #' . $report->id,
            'report' => $report,
            'userId' => $userId, // Pass the user's ID to the view
            'layoutMode' => 'full' // Add full-width layout
        ]);
    }
}