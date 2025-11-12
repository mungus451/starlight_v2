<?php

namespace App\Controllers;

use App\Models\Services\AllianceService;

/**
 * Handles all "read" GET requests for the Alliance feature.
 */
class AllianceController extends BaseController
{
    private AllianceService $allianceService;

    public function __construct()
    {
        parent::__construct();
        $this->allianceService = new AllianceService();
    }

    /**
     * Displays the paginated list of all alliances.
     */
    public function showList(array $vars): void
    {
        $page = (int)($vars['page'] ?? 1);
        $data = $this->allianceService->getAlliancePageData($page);

        $this->render('alliance/list.php', $data + ['title' => 'Alliances']);
    }

    /**
     * Displays the public profile for a single alliance.
     */
    public function showProfile(array $vars): void
    {
        $allianceId = (int)($vars['id'] ?? 0);
        $viewerId = $this->session->get('user_id'); // Get the current user
        
        // Pass the viewer's ID to the service to get context-aware data
        $data = $this->allianceService->getPublicProfileData($allianceId, $viewerId);

        if (is_null($data)) {
            $this->session->setFlash('error', 'That alliance does not exist.');
            $this->redirect('/alliance/list');
            return;
        }

        $this->render('alliance/profile.php', $data + ['title' => $data['alliance']->name]);
    }

    /**
     * Displays the form to create a new alliance.
     */
    public function showCreateForm(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->allianceService->getCreateAllianceData($userId);

        // If user is already in an alliance, redirect them to their alliance profile
        if ($data['user'] && $data['user']->alliance_id !== null) {
            $this->session->setFlash('error', 'You are already in an alliance.');
            $this->redirect('/alliance/profile/' . $data['user']->alliance_id);
            return;
        }

        $this->render('alliance/create.php', $data + ['title' => 'Create Alliance']);
    }

    /**
     * Handles the submission of the "Create Alliance" form.
     */
    public function handleCreate(): void
    {
        // 1. Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->csrfService->validateToken($token)) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/create');
            return;
        }

        // 2. Get data from form
        $userId = $this->session->get('user_id');
        $name = (string)($_POST['alliance_name'] ?? '');
        $tag = (string)($_POST['alliance_tag'] ?? '');

        // 3. Call the service
        $newAllianceId = $this->allianceService->createAlliance($userId, $name, $tag);

        if ($newAllianceId !== null) {
            // Success! Redirect to the new alliance's profile.
            $this->redirect('/alliance/profile/' . $newAllianceId);
        } else {
            // Failure. Redirect back to the create form.
            $this->redirect('/alliance/create');
        }
    }
}