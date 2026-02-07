<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\SpyService;
use App\Presenters\SpyReportPresenter;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Espionage feature.
 * * Refactored to use SpyReportPresenter for View Logic.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class SpyController extends BaseController
{
    private SpyService $spyService;
    private SpyReportPresenter $presenter;

    /**
     * DI Constructor.
     *
     * @param SpyService $spyService
     * @param SpyReportPresenter $presenter
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        SpyService $spyService,
        SpyReportPresenter $presenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->spyService = $spyService;
        $this->presenter = $presenter;
    }

    /**
     * Handles the "all-in" spy operation form.
     */
    public function handleSpy(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_id' => 'required|int'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/profile/' . $data['target_id']);
            return;
        }
        
        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        
        $response = $this->spyService->conductOperation($userId, $data['target_id']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
            $this->redirect('/spy/reports');
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/profile/' . $data['target_id']);
        }
    }

    /**
     * Displays the list of past spy reports.
     */
    public function showReports(): void
    {
        $userId = $this->session->get('user_id');
        
        // 1. Get Entities
        $reportsRaw = $this->spyService->getSpyReports($userId);
        
        // 2. Transform to ViewModel
        $reportsFormatted = $this->presenter->presentAll($reportsRaw, $userId);

        $this->render('spy/reports.php', [
            'title' => 'Spy Reports',
            'reports' => $reportsFormatted,
            'userId' => $userId,
            'layoutMode' => 'full'
        ]);
    }

    /**
     * Displays a single, detailed spy report.
     */
    public function showReport(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $reportId = (int)($vars['id'] ?? 0);
        
        // 1. Get Entity
        $reportRaw = $this->spyService->getSpyReport($reportId, $userId);

        if (is_null($reportRaw)) {
            $this->session->setFlash('error', 'Spy report not found.');
            $this->redirect('/spy/reports');
            return;
        }

        // 2. Transform to ViewModel
        $reportFormatted = $this->presenter->present($reportRaw, $userId);

        $this->render('spy/report_view.php', [
            'title' => 'Spy Report #' . $reportFormatted['id'],
            'report' => $reportFormatted,
            'layoutMode' => 'full'
        ]);
    }
}