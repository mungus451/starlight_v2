<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\LeaderboardService;
use App\Models\Services\ViewContextService;

/**
 * Handles HTTP requests for the Leaderboard.
 */
class LeaderboardController extends BaseController
{
    private LeaderboardService $leaderboardService;

    public function __construct(
        LeaderboardService $leaderboardService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Displays the leaderboard.
     * Route: /leaderboard[/{type}[/{page}]]
     * query param: ?sort=army
     *
     * @param array $vars
     */
    public function show(array $vars): void
    {
        // 1. Parse Path Variables
        $type = $vars['type'] ?? 'players';
        $page = (int)($vars['page'] ?? 1);

        // 2. Parse Query Parameters (Sorting)
        // We use $_GET directly here as the Validator class is built for POST payloads
        // and simple string extraction is safe for whitelisting in Service.
        $sort = $_GET['sort'] ?? 'net_worth';

        // Sanitize type
        if (!in_array($type, ['players', 'alliances'])) {
            $type = 'players';
        }

        // 3. Call Service
        $response = $this->leaderboardService->getLeaderboardData($type, $page, $sort);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/dashboard');
            return;
        }

        // 4. Render View
        $viewData = $response->data;
        $viewData['layoutMode'] = 'full';
        $viewData['title'] = 'Leaderboard - ' . ucfirst($type);

        $this->render('leaderboard/show.php', $viewData);
    }
}