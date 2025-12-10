<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\DashboardService;
use App\Models\Services\ViewContextService;
use App\Presenters\DashboardPresenter;

/**
 * Handles displaying the user's main dashboard.
 * * Refactored for Strict Dependency Injection & Centralized Validation support.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class DashboardController extends BaseController
{
    private DashboardService $dashboardService;
    private DashboardPresenter $dashboardPresenter;

    /**
     * DI Constructor.
     *
     * @param DashboardService $dashboardService
     * @param DashboardPresenter $dashboardPresenter
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService
     */
    public function __construct(
        DashboardService $dashboardService,
        DashboardPresenter $dashboardPresenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->dashboardService = $dashboardService;
        $this->dashboardPresenter = $dashboardPresenter;
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
        
        // --- Presenter Logic ---
        // Delegate presentation logic to the Presenter
        $data = $this->dashboardPresenter->present($data);

        // 2. Render the view
        $this->render('dashboard/show.php', $data + ['title' => 'Dashboard']);
    }
}