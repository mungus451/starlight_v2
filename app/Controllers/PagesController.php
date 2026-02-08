<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all public-facing static pages like Home and Contact.
 * * Refactored for Strict Dependency Injection.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class PagesController extends BaseController
{
    /**
     * DI Constructor.
     *
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
    }

    /**
     * Displays the public homepage (currently under construction).
     */
    public function showHome(): void
    {
        // If user is logged in, redirect to dashboard
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        // Otherwise, show the "Under Construction" page as the landing page
        $this->render('pages/under_construction.php', ['title' => 'Under Construction', 'layoutMode' => 'blank']);
    }

    /**
     * Displays the public contact page.
     */
    public function showContact(): void
    {
        // Check if user is logged in
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('pages/contact.php', [
            'title' => 'Contact Us',
            'layoutMode' => 'full'
        ]);
    }
}