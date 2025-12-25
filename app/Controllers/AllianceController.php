<?php

namespace App\Controllers;

use App\Core\Session;
use App\Core\CSRFService;
use App\Core\Validator;
use App\Models\Services\AllianceService;
use App\Models\Services\AllianceManagementService;
use App\Presenters\AllianceProfilePresenter;
use App\Models\Services\ViewContextService; // --- NEW DEPENDENCY ---

/**
 * Handles all "read" GET requests for the Alliance feature.
 * * Refactored Phase 1: View Logic Decoupling (Presenter).
 * * Refactored Phase 2: BaseController Integrity (ViewContextService).
 */
class AllianceController extends BaseController
{
    private AllianceService $allianceService;
    private AllianceManagementService $mgmtService;
    private AllianceProfilePresenter $profilePresenter;

    /**
     * DI Constructor.
     *
     * @param AllianceService $allianceService
     * @param AllianceManagementService $mgmtService
     * @param AllianceProfilePresenter $profilePresenter
     * @param Session $session
     * @param CSRFService $csrfService
     * @param Validator $validator
     * @param ViewContextService $viewContextService // --- REPLACES LevelCalculator & StatsRepo ---
     */
    public function __construct(
        AllianceService $allianceService,
        AllianceManagementService $mgmtService,
        AllianceProfilePresenter $profilePresenter,
        Session $session,
        CSRFService $csrfService,
        Validator $validator,
        ViewContextService $viewContextService
    ) {
        parent::__construct($session, $csrfService, $validator, $viewContextService);
        $this->allianceService = $allianceService;
        $this->mgmtService = $mgmtService;
        $this->profilePresenter = $profilePresenter;
    }

    /**
     * Displays the paginated list of all alliances.
     */
    public function showList(array $vars): void
    {
        $page = (int)($vars['page'] ?? 1);
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ($this->session->get('is_mobile') ? 5 : null);
        
        $data = $this->allianceService->getAlliancePageData($page, $limit);

        $data['layoutMode'] = 'full';

        if ($this->session->get('is_mobile')) {
            $this->render('alliance/mobile_list.php', $data + ['title' => 'Alliances']);
        } else {
            $this->render('alliance/list.php', $data + ['title' => 'Alliances']);
        }
    }

    /**
     * Displays the public profile for a single alliance.
     * Uses Presenter to prepare ViewModel.
     */
    public function showProfile(array $vars): void
    {
        $allianceId = (int)($vars['id'] ?? 0);
        $viewerId = $this->session->get('user_id');
        
        // 1. Get Raw Data (Service Layer)
        $rawData = $this->allianceService->getPublicProfileData($allianceId, $viewerId);

        if (is_null($rawData)) {
            $this->session->setFlash('error', 'That alliance does not exist.');
            $this->redirect('/alliance/list');
            return;
        }

        // 2. Format Data (Presentation Layer)
        $viewModel = $this->profilePresenter->present($rawData);

        // 3. Render View
        if ($this->session->get('is_mobile')) {
            $this->render('alliance/mobile_profile.php', $viewModel);
        } else {
            $this->render('alliance/profile.php', $viewModel);
        }
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
        
        $response = $this->mgmtService->createAlliance(
            $userId, 
            $data['alliance_name'], 
            $data['alliance_tag']
        );

        if ($response->isSuccess()) {
            // 4a. Success: Update Session & Redirect
            $this->session->setFlash('success', $response->message);
            
            if (isset($response->data['alliance_id'])) {
                $this->session->set('alliance_id', $response->data['alliance_id']);
                $this->redirect('/alliance/profile/' . $response->data['alliance_id']);
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            // 4b. Failure: Flash Error & Back
            $this->session->setFlash('error', $response->message);
            $this->redirect('/alliance/create');
        }
    }
}