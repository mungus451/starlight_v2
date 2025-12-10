<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\ProfileService;
use App\Models\Services\ViewContextService;
use App\Presenters\ProfilePresenter;

/**
 * Handles all HTTP requests for the public Player Profile page.
 * * Refactored for Strict Dependency Injection & Centralized Validation support.
 * * Fixed: Updated parent constructor call to use ViewContextService.
 */
class ProfileController extends BaseController
{
    private ProfileService $profileService;
    private ProfilePresenter $profilePresenter;

    /**
     * DI Constructor.
     *
     * @param ProfileService $profileService
     * @param ProfilePresenter $profilePresenter
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService
     */
    public function __construct(
        ProfileService $profileService,
        ProfilePresenter $profilePresenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->profileService = $profileService;
        $this->profilePresenter = $profilePresenter;
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

        $data = $this->profilePresenter->present($data);

        $data['layoutMode'] = 'full';
        $data['title'] = $data['profile']['character_name'] . "'s Profile";

        $this->render('profile/show.php', $data);
    }
}