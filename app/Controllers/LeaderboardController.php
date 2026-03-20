<?php

namespace App\Controllers;

use App\Core\Config;
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
    private Config $config;
    private LeaderboardService $leaderboardService;

    public function __construct(
        Config $config,
        LeaderboardService $leaderboardService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->config = $config;
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
        $response = $this->loadLeaderboardData(
            $vars['type'] ?? 'players',
            (int)($vars['page'] ?? 1),
            (string)($_GET['sort'] ?? 'net_worth')
        );

        if (!$response->isSuccess()) {
            $this->session->setFlash('error', $response->message);
            $this->redirect('/dashboard');
            return;
        }

        // 4. Render View
        $viewData = $response->data;
        $viewData['layoutMode'] = 'full';
        $viewData['title'] = 'Leaderboard - ' . ucfirst($viewData['type']);
        $viewData['spa_leaderboard_enabled'] = (bool)$this->config->get('spa.leaderboard_enabled', false);

        $this->render('leaderboard/show.php', $viewData);
    }

    /**
     * API endpoint: return leaderboard data as JSON for the SPA island.
     */
    public function apiData(): void
    {
        $response = $this->loadLeaderboardData(
            (string)($_GET['type'] ?? 'players'),
            (int)($_GET['page'] ?? 1),
            (string)($_GET['sort'] ?? 'net_worth')
        );

        if (!$response->isSuccess()) {
            $this->jsonResponse(['error' => $response->message], 400);
            return;
        }

        $this->jsonResponse($response->data);
    }

    private function loadLeaderboardData(string $type, int $page, string $sort)
    {
        if (!in_array($type, ['players', 'alliances'], true)) {
            $type = 'players';
        }

        return $this->leaderboardService->getLeaderboardData($type, $page, $sort);
    }
}
