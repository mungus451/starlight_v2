<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\DashboardService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Services\NotificationService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles the main dashboard view.
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
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     * @param NotificationService $notificationService
     */
    public function __construct(
        DashboardService $dashboardService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo,
        NotificationService $notificationService
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo, $notificationService);
        $this->dashboardService = $dashboardService;
    }

    /**
     * Displays the dashboard.
     */
    public function show(): void
    {
        $userId = $this->session->get('user_id');
        
        // Get dashboard data (stats, resources, recent activity)
        $data = $this->dashboardService->getDashboardData($userId);

        // Render the view
        $this->render('dashboard/show.php', $data + [
            'title' => 'Dashboard',
            'layoutMode' => 'full'
        ]);
    }
}