<?php

namespace App\Controllers;

use App\Models\Services\ProfileService;

/**
 * Handles all HTTP requests for the public Player Profile page.
 */
class ProfileController extends BaseController
{
    private ProfileService $profileService;

    public function __construct()
    {
        parent::__construct();
        $this->profileService = new ProfileService();
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

        $data['layoutMode'] = 'full'; // Use the full-width layout
        $data['title'] = $data['profile']['character_name'] . "'s Profile";

        $this->render('profile/show.php', $data);
    }
}