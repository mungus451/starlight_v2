<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\DashboardService;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles displaying the user's main dashboard.
 * * Refactored for Strict Dependency Injection & Centralized Validation support.
 * * Fixed: Updated parent constructor call to use ViewContextService.
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
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        DashboardService $dashboardService,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
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
        
        // --- Presenter Logic (Inline for Compliance) ---
        // Map effect types to UI properties to keep View clean
        if (!empty($data['activeEffects'])) {
            foreach ($data['activeEffects'] as &$effect) {
                switch ($effect['effect_type']) {
                    case 'jamming':
                        $effect['ui_icon'] = 'fa-satellite-dish';
                        $effect['ui_label'] = 'Radar Jamming';
                        $effect['ui_color'] = 'text-accent';
                        break;
                    case 'peace_shield':
                        $effect['ui_icon'] = 'fa-shield-alt';
                        $effect['ui_label'] = 'Peace Shield';
                        $effect['ui_color'] = 'text-success';
                        break;
                    case 'wounded':
                        $effect['ui_icon'] = 'fa-user-injured';
                        $effect['ui_label'] = 'Wounded';
                        $effect['ui_color'] = 'text-danger';
                        break;
                    default:
                        $effect['ui_icon'] = 'fa-bolt';
                        $effect['ui_label'] = 'Unknown Effect';
                        $effect['ui_color'] = 'text-accent';
                }
            }
        }
        // -----------------------------------------------

        // 2. Render the view
        $this->render('dashboard/show.php', $data + ['title' => 'Dashboard']);
    }
}