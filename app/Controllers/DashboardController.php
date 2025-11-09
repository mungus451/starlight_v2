<?php

namespace App\Controllers;

use App\Models\Services\DashboardService;

/**
 * Handles displaying the user's main dashboard.
 */
class DashboardController extends BaseController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    /**
     * Shows the main dashboard.
     * The route for this is protected by AuthMiddleware.
     */
    public function show(): void
    {
        // We can safely assume the user is logged in
        // because the AuthMiddleware will run before this method.
        $userId = $this->session->get('user_id');

        if (is_null($userId)) {
            // This should theoretically never be reached, but as a safeguard:
            $this->session->setFlash('error', 'Session error. Please log in again.');
            $this->redirect('/login');
            return;
        }

        // 1. Call the service to get all our data in one array
        $data = $this->dashboardService->getDashboardData((int)$userId);

        // 2. Render the "dumb" view, passing in the data
        // BaseController::render() will extract the array keys
        // into $user, $resources, $stats, and $structures variables
        // for the view file to use.
        $this->render('dashboard/show.php', $data + ['title' => 'Dashboard']);
    }
}