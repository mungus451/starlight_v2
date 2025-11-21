<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Models\Services\ProfileService;
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all HTTP requests for the public Player Profile page.
 * * Refactored for Strict Dependency Injection.
 */
class ProfileController extends BaseController
{
    private ProfileService $profileService;

    /**
     * DI Constructor.
     *
     * @param ProfileService $profileService
     * @param Session $session
     * @param CSRFService $csrfService
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        ProfileService $profileService,
        Session $session,
        CSRFService $csrfService,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $levelCalculator, $statsRepo);
        $this->profileService = $profileService;
    }

    /**
     * Displays the public profile for a single user.
     * The {id} is passed in from the router.
     */
    public function show(array $vars): void
    {
        $targetUserId = (int)($vars['id'] ?? 0);
        $viewerUserId = $this->session->get('user_id');

        if ($targetUserId === $viewerUserId) {
            // Don't view your own public profile, redirect to dashboard
            $this->redirect('/dashboard');
            return;
        }

        // Get all data (profile, stats, alliance, viewer perms) from the service
        $data = $this->profileService->getProfileData($targetUserId, $viewerUserId);

        if ($data === null) {
            $this->session->setFlash('error', 'Player profile not found.');
            $this->redirect('/battle'); // Redirect to battle page if not found
            return;
        }

        $data['layoutMode'] = 'full';
        $data['title'] = $data['profile']['character_name'] . "'s Profile";

        $this->render('profile/show.php', $data);
    }
}