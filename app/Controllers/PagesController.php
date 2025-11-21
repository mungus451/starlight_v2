<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all public-facing static pages like Home and Contact.
 * * Refactored for Strict Dependency Injection.
 */
class PagesController extends BaseController
{
    /**
     * DI Constructor.
     *
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
    }

    /**
     * Displays the public homepage.
     */
    public function showHome(): void
    {
        // Check if user is logged in
        if ($this->session->has('user_id')) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('pages/home.php', ['title' => 'Starlight Dominion']);
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