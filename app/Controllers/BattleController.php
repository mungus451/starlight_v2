<?php

namespace App\Controllers;

use App\Models\Services\AttackService;

/**
 * Handles all HTTP requests for the Battle feature.
 */
class BattleController extends BaseController
{
    private AttackService $attackService;

    public function __construct()
    {
        parent::__construct();
        $this->attackService = new AttackService();
    }

    /**
     * Displays the main battle page (attack form + target list).
     * The {page} is passed in from the router.
     */
    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = (int)($vars['page'] ?? 1);
        
        // Get all data (attacker stats, player list, pagination) from the service
        $data = $this->attackService->getAttackPageData($userId, $page);

        // --- THIS IS THE FIX ---
        $this->render('battle/show.php', $data + ['title' => 'Battle']);
    }

    /**
     * Handles the "all-in" attack form submission.
     */
    public function handleAttack(): void
    {
        // 1. Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            // --- THIS IS THE FIX ---
            $this->redirect('/battle');
            return;
        }
        
        // 2. Get data from form
        $userId = $this->session->get('user_id');
        $targetName = (string)($_POST['target_name'] ?? '');
        $attackType = (string)($_POST['attack_type'] ?? 'plunder');
        
        // 3. Call the service (it handles all logic and flash messages)
        $this->attackService->conductAttack($userId, $targetName, $attackType);
        
        // 4. Redirect to the reports page to see the new report
        // --- THIS IS THE FIX ---
        $this->redirect('/battle/reports');
    }

    /**
     * Displays the list of past battle reports.
     */
    public function showReports(): void
    {
        $userId = $this->session->get('user_id');
        $reports = $this->attackService->getBattleReports($userId);

        // --- THIS IS THE FIX ---
        $this->render('battle/reports.php', [
            'title' => 'Battle Reports',
            'reports' => $reports
        ]);
    }

    /**
     * Displays a single, detailed battle report.
     * The {id} is passed in from the router.
     */
    public function showReport(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $reportId = (int)($vars['id'] ?? 0);
        
        $report = $this->attackService->getBattleReport($reportId, $userId);

        if (is_null($report)) {
            // Report not found or doesn't belong to the user
            $this->session->setFlash('error', 'Battle report not found.');
            // --- THIS IS THE FIX ---
            $this->redirect('/battle/reports');
            return;
        }

        // --- THIS IS THE FIX ---
        $this->render('battle/report_view.php', [
            'title' => 'Battle Report #' . $report->id,
            'report' => $report
        ]);
    }
}