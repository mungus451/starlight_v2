<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\SpyService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Espionage feature.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Decoupled: Consumes ServiceResponse.
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
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        SpyService $spyService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
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