<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\DashboardService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles displaying the user's main dashboard.
 * * Refactored for Strict Dependency Injection & Centralized Validation support.
 */
class DashboardController extends BaseController
{
    private DashboardService $dashboardService;

    /**
     * DI Constructor.
     *
     * @param DashboardService $dashboardService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        DashboardService $dashboardService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->dashboardService = $dashboardService;
    }

    /**
     * Shows the main dashboard.
     * The route for this is protected by AuthMiddleware.
     */
    public function show(): void
    {
        // We can safely assume the user is logged in
        $userId = $this->session->get('user_id');

        if (is_null($userId)) {
            $this->session->setFlash('error', 'Session error. Please log in again.');
            $this->redirect('/login');
            return;
        }

        // 1. Call the service to get all our data in one array
        $data = $this->dashboardService->getDashboardData((int)$userId);

        // Tell the layout to render in full-width mode
        $data['layoutMode'] = 'full';

        // 2. Render the view
        $this->render('dashboard/show.php', $data + ['title' => 'Dashboard']);
    }
}