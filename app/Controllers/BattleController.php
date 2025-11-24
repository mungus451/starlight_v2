<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AttackService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the Battle feature.
 * * Refactored for Strict Dependency Injection & Centralized Validation.
 * * Decoupled: Consumes ServiceResponse.
 */
class BattleController extends BaseController
{
    private AttackService $attackService;

    /**
     * DI Constructor.
     *
     * @param AttackService $attackService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AttackService $attackService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->attackService = $attackService;
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
        $reports = $this->attackService->getBattleReports($userId);

        $this->render('battle/reports.php', [
            'title' => 'Battle Reports',
            'reports' => $reports,
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
        
        $report = $this->attackService->getBattleReport($reportId, $userId);

        if (is_null($report)) {
            $this->session->setFlash('error', 'Battle report not found.');
            $this->redirect('/battle/reports');
            return;
        }

        $this->render('battle/report_view.php', [
            'title' => 'Battle Report #' . $report->id,
            'report' => $report,
            'userId' => $userId,
            'layoutMode' => 'full'
        ]);
    }
}