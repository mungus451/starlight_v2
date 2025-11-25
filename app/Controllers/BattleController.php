<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AttackService;
use App\Presenters\BattleReportPresenter;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Battle feature.
 * * Refactored to use BattleReportPresenter for View Logic.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class BattleController extends BaseController
{
    private AttackService $attackService;
    private BattleReportPresenter $presenter;

    /**
     * DI Constructor.
     *
     * @param AttackService $attackService
     * @param BattleReportPresenter $presenter
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        AttackService $attackService,
        BattleReportPresenter $presenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->attackService = $attackService;
        $this->presenter = $presenter;
    }

    /**
     * Displays the main battle page (attack form + target list).
     */
    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = (int)($vars['page'] ?? 1);
        
        $data = $this->attackService->getAttackPageData($userId, $page);

        $data['layoutMode'] = 'full';

        $this->render('battle/show.php', $data + ['title' => 'Battle']);
    }

    /**
     * Handles the "all-in" attack form submission.
     */
    public function handleAttack(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_name' => 'required|string',
            'attack_type' => 'required|string|in:plunder'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/battle');
            return;
        }
        
        // 3. Execute Logic
        $userId = $this->session->get('user_id');
        $response = $this->attackService->conductAttack($userId, $data['target_name'], $data['attack_type']);
        
        // 4. Handle Response
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
            $this->redirect('/battle/reports');
        } else {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/battle');
        }
    }

    /**
     * Displays the list of past battle reports.
     */
    public function showReports(): void
    {
        $userId = $this->session->get('user_id');
        
        // 1. Get Entities
        $reportsRaw = $this->attackService->getBattleReports($userId);
        
        // 2. Transform to ViewModel
        $reportsFormatted = $this->presenter->presentAll($reportsRaw, $userId);

        $this->render('battle/reports.php', [
            'title' => 'Battle Reports',
            'reports' => $reportsFormatted, // Passed as array, not entities
            'userId' => $userId,
            'layoutMode' => 'full'
        ]);
    }

    /**
     * Displays a single, detailed battle report.
     */
    public function showReport(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $reportId = (int)($vars['id'] ?? 0);
        
        // 1. Get Entity
        $reportRaw = $this->attackService->getBattleReport($reportId, $userId);

        if (is_null($reportRaw)) {
            $this->session->setFlash('error', 'Battle report not found.');
            $this->redirect('/battle/reports');
            return;
        }

        // 2. Transform to ViewModel
        $reportFormatted = $this->presenter->present($reportRaw, $userId);

        $this->render('battle/report_view.php', [
            'title' => 'Battle Report #' . $reportFormatted['id'],
            'report' => $reportFormatted,
            'layoutMode' => 'full'
        ]);
    }
}