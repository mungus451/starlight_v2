<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\SpyService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Espionage feature.
 * * Refactored for Strict Dependency Injection.
 */
class SpyController extends BaseController
{
    private SpyService $spyService;

    /**
     * DI Constructor.
     *
     * @param SpyService $spyService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        SpyService $spyService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->spyService = $spyService;
    }

    /**
     * Displays the main spy page (conduct operation).
     */
    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = (int)($vars['page'] ?? 1);
        
        $data = $this->spyService->getSpyData($userId, $page);

        $data['layoutMode'] = 'full';

        $this->render('spy/show.php', $data + ['title' => 'Espionage']);
    }

    /**
     * Handles the "all-in" spy operation form.
     */
    public function handleSpy(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/spy');
            return;
        }
        
        $userId = $this->session->get('user_id');
        $targetName = (string)($_POST['target_name'] ?? '');
        
        $this->spyService->conductOperation($userId, $targetName);
        
        $this->redirect('/spy/reports');
    }

    /**
     * Displays the list of past spy reports.
     */
    public function showReports(): void
    {
        $userId = $this->session->get('user_id');
        $reports = $this->spyService->getSpyReports($userId);

        $this->render('spy/reports.php', [
            'title' => 'Spy Reports',
            'reports' => $reports,
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
        
        $report = $this->spyService->getSpyReport($reportId, $userId);

        if (is_null($report)) {
            $this->session->setFlash('error', 'Spy report not found.');
            $this->redirect('/spy/reports');
            return;
        }

        $this->render('spy/report_view.php', [
            'title' => 'Spy Report #' . $report->id,
            'report' => $report,
            'userId' => $userId,
            'layoutMode' => 'full'
        ]);
    }
}