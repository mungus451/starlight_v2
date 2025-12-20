<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Exceptions\RedirectException;
use App\Models\Repositories\UserRepository;

/**
 * Checks if authenticated users have selected a race.
 * If not, redirects them to the race selection page.
 */
class RaceSelectionMiddleware
{
    private Session $session;
    private UserRepository $userRepository;

    /**
     * DI Constructor.
     */
    public function __construct(Session $session, UserRepository $userRepository)
    {
        $this->session = $session;
        $this->userRepository = $userRepository;
    }

    /**
     * Runs the race selection check.
     * If the user doesn't have a race, redirect to race selection.
     */
    public function handle(): void
    {
        // Only check if user is logged in
        $userId = $this->session->get('user_id');
        if (!$userId) {
            return;
        }

        // Get the user from database
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            return;
        }

        // Check if user has a race selected
        if ($user->race === null) {
            $this->session->setFlash('info', 'Please select your race to continue.');
            throw new RedirectException('/race/select');
        }
    }
}
