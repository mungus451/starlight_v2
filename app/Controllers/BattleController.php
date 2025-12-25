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

    public function show(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $page = (int)($vars['page'] ?? 1);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ($this->session->get('is_mobile') ? 5 : null);
        
        $data = $this->attackService->getAttackPageData($userId, $page, $limit);

        $data['layoutMode'] = 'full';
        $data['csrf_token'] = $this->csrfService->generateToken();

        if ($this->session->get('is_mobile')) {
            $this->render('battle/mobile_show.php', $data + ['title' => 'War Room']);
        } else {
            $this->render('battle/show.php', $data + ['title' => 'War Room']);
        }
    }

    public function handleAttack(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_name' => 'required|string',
            'attack_type' => 'required|string'
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

    public function showReports(): void
    {
        $userId = $this->session->get('user_id');
        
        $reportsRaw = $this->attackService->getBattleReports($userId);
        $reportsFormatted = $this->presenter->presentAll($reportsRaw, $userId);

        // Pagination Logic
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ($this->session->get('is_mobile') ? 5 : 25);
        $totalItems = count($reportsFormatted);
        $totalPages = ceil($totalItems / $limit);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));
        $offset = ($page - 1) * $limit;
        
        $reportsPaginated = array_slice($reportsFormatted, $offset, $limit);

        $viewData = [
            'title' => 'Battle Reports',
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
            $this->render('battle/mobile_reports.php', $viewData);
        } else {
            $this->render('battle/reports.php', $viewData);
        }
    }

    public function showReport(array $vars): void
    {
        $userId = $this->session->get('user_id');
        $reportId = (int)($vars['id'] ?? 0);
        
        $reportRaw = $this->attackService->getBattleReport($reportId, $userId);

        if (is_null($reportRaw)) {
            $this->session->setFlash('error', 'Battle report not found.');
            $this->redirect('/battle/reports');
            return;
        }

        $reportFormatted = $this->presenter->present($reportRaw, $userId);

        $viewData = [
            'title' => 'Battle Report #' . $reportFormatted['id'],
            'report' => $reportFormatted,
            'layoutMode' => 'full'
        ];

        if ($this->session->get('is_mobile')) {
            $this->render('battle/mobile_report_view.php', $viewData);
        } else {
            $this->render('battle/report_view.php', $viewData);
        }
    }
}