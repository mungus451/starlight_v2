<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\WarService;
use App\Services\WarMetricsService;
use App\Core\JsonResponse;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all HTTP requests for the Alliance War page.
 * * Refactored Phase 1.3: Strict MVC Compliance.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class WarController extends BaseController
{
    private WarService $warService;
    private WarMetricsService $warMetricsService;

    public function __construct(
        WarService $warService,
        WarMetricsService $warMetricsService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->warService = $warService;
        $this->warMetricsService = $warMetricsService;
    }

    /**
     * Displays the main war page (active wars, history, declare).
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        $response = $this->warService->getWarPageData($userId);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/list');
            return;
        }

        $data = $response->data;
        $data['layoutMode'] = 'full';
        $data['title'] = 'Alliance War Room';

        $this->render('alliance/war.php', $data);
    }

    /**
     * Handles declaring a war.
     */
    public function handleDeclareWar(): void
    {
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'target_alliance_id' => 'required|int',
            'casus_belli' => 'nullable|string|max:1000'
        ]);

        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/war');
            return;
        }
        
        $userId = $this->session->get('user_id');

        $response = $this->warService->declareWar(
            $userId,
            $data['target_alliance_id'],
            $data['casus_belli'] ?? ''
        );
        
        if ($response->isSuccess()) {
            $this->session->setFlash('success', $response->message);
        } else {
            $this->session->setFlash('error', $response->message);
        }
        
        $this->redirect('/alliance/war');
    }

    /**
     * Displays the War Dashboard for a specific active war.
     *
     * @param array $params Contains 'warId' from the route.
     * @return void
     */
    public function showDashboard(array $params): void
    {
        $warId = (int)($params['warId'] ?? 0);
        $viewerAllianceId = $this->session->get('alliance_id');

        if ($warId <= 0 || !$viewerAllianceId) {
            $this->session->setFlash('error', 'You must be in an alliance to view a war dashboard.');
            $this->redirect('/alliance/war');
            return;
        }
        
        $response = $this->warService->getWarDashboardData($warId, $viewerAllianceId);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/war'); // Redirect to main war page if dashboard fails
            return;
        }

        $data = $response->data;
        $data['layoutMode'] = 'full';
        $data['title'] = 'War Dashboard'; // Name was removed, use a generic title

        $this->render('alliance/war_dashboard.php', $data);
    }

    /**
     * Fetches and returns the war score data for a given war.
     *
     * @param array $params Contains 'warId' from the route.
     * @return void
     */
    public function getWarScoreData(array $params): void
    {
        $warId = (int)($params['warId'] ?? 0);

        if ($warId <= 0) {
            JsonResponse::sendError('Invalid war ID.', 400);
            return;
        }

        $war = $this->warService->findWarById($warId);
        if (!$war) {
            JsonResponse::sendError('War not found.', 404);
            return;
        }

        $warScoreDTO = $this->warMetricsService->getWarScore($war);

        JsonResponse::send($warScoreDTO);
    }
}