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
     * Displays the main spy page (conduct operation).
     */
    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = (int)($vars['page'] ?? 1);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ($this->session->get('is_mobile') ? 5 : null);
        
        $data = $this->spyService->getSpyData($userId, $page, $limit);

        $data['layoutMode'] = 'full';
        $data['csrf_token'] = $this->csrfService->generateToken();

        if ($this->session->get('is_mobile')) {
            $this->render('spy/mobile_show.php', $data + ['title' => 'Espionage']);
        } else {
            $this->render('spy/show.php', $data + ['title' => 'Espionage']);
        }
    }

    /**
     * Handles the "all-in" spy operation form.
     */
    public function handleSpy(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_name' => 'required|string'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/spy');
            return;
        }
        
        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        
        $response = $this->spyService->conductOperation($userId, $data['target_name']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
            $this->redirect('/spy/reports');
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/spy');
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

        // 3. Pagination Logic
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ($this->session->get('is_mobile') ? 5 : 25);
        $totalItems = count($reportsFormatted);
        $totalPages = ceil($totalItems / $limit);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;
        
        $reportsPaginated = array_slice($reportsFormatted, $offset, $limit);

        $viewData = [
            'title' => 'Spy Reports',
            'reports' => $reportsPaginated,
            'userId' => $userId,
            'layoutMode' => 'full',
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'limit' => $limit
            ]
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('spy/mobile_reports.php', $viewData);
        } else {
            // Desktop view might ignore pagination or need update, passing full list for now to avoid regression if view doesn't support pagination
            // Actually, better to paginate desktop too if easy, but sticking to request scope: mobile pagination.
            // But I modified $reportsPaginated. I should pass formatted array if not mobile?
            // Existing view loop over 'reports'. If I pass sliced, desktop gets sliced. That's fine/better performance.
            $this->render('spy/reports.php', $viewData);
        }
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

        $viewData = [
            'title' => 'Spy Report #' . $reportFormatted['id'],
            'report' => $reportFormatted,
            'layoutMode' => 'full'
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('spy/mobile_report_view.php', $viewData);
        } else {
            $this->render('spy/report_view.php', $viewData);
        }
    }
}