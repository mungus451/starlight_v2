<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceService;
use App\Models\Services\AllianceManagementService; // --- NEW IMPORT ---
use App\Models\Services\LevelCalculatorService;
use App\Models\Repositories\StatsRepository;

/**
 * Handles all "read" GET requests for the Alliance feature.
 * * Refactored for Strict Dependency Injection.
 * * Now uses AllianceManagementService for creation logic.
 */
class AllianceController extends BaseController
{
    private AllianceService $allianceService;
    private AllianceManagementService $mgmtService; // --- NEW PROP ---

    /**
     * DI Constructor.
     *
     * @param AllianceService $allianceService
     * @param AllianceManagementService $mgmtService // --- NEW PARAM ---
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param LevelCalculatorService $levelCalculator
     * @param StatsRepository $statsRepo
     */
    public function __construct(
        AllianceService $allianceService,
        AllianceManagementService $mgmtService, // --- NEW INJECTION ---
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        LevelCalculatorService $levelCalculator,
        StatsRepository $statsRepo
    ) {
        parent::__construct($session, $csrfService, $validator, $levelCalculator, $statsRepo);
        $this->allianceService = $allianceService;
        $this->mgmtService = $mgmtService;
    }

    /**
     * Displays the paginated list of all alliances.
     */
    public function showList(array $vars): void
    {
        $page = (int)($vars['page'] ?? 1);
        $data = $this->allianceService->getAlliancePageData($page);

        $data['layoutMode'] = 'full';

        $this->render('alliance/list.php', $data + ['title' => 'Alliances']);
    }

    /**
     * Displays the public profile for a single alliance.
     */
    public function showProfile(array $vars): void
    {
        $allianceId = (int)($vars['id'] ?? 0);
        $viewerId = $this->session->get('user_id');
        
        $data = $this->allianceService->getPublicProfileData($allianceId, $viewerId);

        if (is_null($data)) {
            $this->session->setFlash('error', 'That alliance does not exist.');
            $this->redirect('/alliance/list');
            return;
        }

        $data['layoutMode'] = 'full';

        $this->render('alliance/profile.php', $data + ['title' => $data['alliance']->name]);
    }

    /**
     * Displays the form to create a new alliance.
     */
    public function showCreateForm(): void
    {
        $userId = $this->session->get('user_id');
        $data = $this->allianceService->getCreateAllianceData($userId);

        // If user is already in an alliance, redirect them
        if ($data['user'] && $data['user']->alliance_id !== null) {
            $this->session->setFlash('error', 'You are already in an alliance.');
            $this->redirect('/alliance/profile/'. $data['user']->alliance_id);
            return;
        }

        $data['layoutMode'] = 'full';

        $this->render('alliance/create.php', $data + ['title' => 'Create Alliance']);
    }

    /**
     * Handles the submission of the "Create Alliance" form.
     */
    public function handleCreate(): void
    {
        // 1. Validate Input
        $data = $this->validate($_POST, [
            'csrf_token' => 'required',
            'alliance_name' => 'required|string|min:3|max:100',
            'alliance_tag' => 'required|string|min:3|max:5'
        ]);

        // 2. Validate CSRF
        if (!$this->csrfService->validateToken($data['csrf_token'])) {
            $this->session->setFlash('error', 'Invalid security token.');
            $this->redirect('/alliance/create');
            return;
        }

        // 3. Call the service
        $userId = $this->session->get('user_id');
        
        // Use the Write Service
        $response = $this->mgmtService->createAlliance(
            $userId, 
            $data['alliance_name'], 
            $data['alliance_tag']
        );

        if ($response->isSuccess()) {
            // 4a. Success: Update Session & Redirect
            $this->session->setFlash('success', $response->message);
            
            // Important: Update session to reflect new alliance
            if (isset($response->data['alliance_id'])) {
                $this->session->set('alliance_id', $response->data['alliance_id']);
                $this->redirect('/alliance/profile/' . $response->data['alliance_id']);
            } else {
                // Fallback if ID missing (shouldn't happen)
                $this->redirect('/dashboard');
            }
        } else {
            // 4b. Failure: Flash Error & Back
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/create');
        }
    }
}