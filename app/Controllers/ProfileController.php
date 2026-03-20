<?php

namespace App\Controllers;

use App\Core\Config;
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
    private Config $config;
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
        Config $config,
        ProfileService $profileService,
        ProfilePresenter $profilePresenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->config = $config;
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
        $data['spa_profile_enabled'] = (bool)$this->config->get('spa.profile_enabled', false);

        $this->render('profile/show.php', $data);
    }

    public function apiData(array $vars): void
    {
        $viewerUserId = (int)$this->session->get('user_id', 0);
        if ($viewerUserId <= 0) {
            $this->jsonResponse(['error' => 'Not authenticated'], 401);
            return;
        }

        $targetUserId = (int)($vars['id'] ?? 0);
        if ($targetUserId <= 0 || $targetUserId === $viewerUserId) {
            $this->jsonResponse(['error' => 'Invalid target user.'], 400);
            return;
        }

        $data = $this->profileService->getProfileData($targetUserId, $viewerUserId);
        if ($data === null) {
            $this->jsonResponse(['error' => 'Player profile not found.'], 404);
            return;
        }

        $data = $this->profilePresenter->present($data);

        $stats = $data['stats'];
        $alliance = $data['alliance'] ?? null;

        $this->jsonResponse([
            'profile' => [
                'id' => (int)$data['profile']['id'],
                'character_name' => (string)$data['profile']['character_name'],
                'bio' => (string)($data['profile']['bio'] ?? ''),
                'profile_picture_url' => $data['profile']['profile_picture_url'] !== null
                    ? (string)$data['profile']['profile_picture_url']
                    : null,
                'formatted_created_at' => (string)$data['profile']['formatted_created_at'],
            ],
            'stats' => [
                'level' => (int)$stats->level,
                'net_worth' => (int)$stats->net_worth,
                'war_prestige' => (int)$stats->war_prestige,
            ],
            'alliance' => $alliance !== null ? [
                'id' => (int)$alliance->id,
                'name' => (string)$alliance->name,
                'tag' => (string)$alliance->tag,
            ] : null,
            'viewer' => [
                'can_invite' => (bool)($data['viewer']['can_invite'] ?? false),
            ],
        ]);
    }
}
