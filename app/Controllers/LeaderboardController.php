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
     *
     * @param array $vars
     */
    public function show(array $vars): void
    {
        // 1. Parse Input
        $type = $vars['type'] ?? 'players';
        $page = (int)($vars['page'] ?? 1);

        // Sanitize type
        if (!in_array($type, ['players', 'alliances'])) {
            $type = 'players';
        }

        // 2. Call Service
        $response = $this->leaderboardService->getLeaderboardData($type, $page);

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/dashboard');
            return;
        }

        // 3. Render View
        $viewData = $response->data;
        $viewData['layoutMode'] = 'full';
        $viewData['title'] = 'Leaderboard - ' . ucfirst($type);

        $this->render('leaderboard/show.php', $viewData);
    }
}